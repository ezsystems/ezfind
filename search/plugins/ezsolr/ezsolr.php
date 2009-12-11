<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
// EXTENDED COPYRIGHT NOTICE :
//      Part of this class was inspired from the following contributors' work :
//      * Kristof Coomans <kristof[dot]coomans[at]telenet[dot]be>
//      * Paul Borgermans <pb[at]ez[dot]no> ( when not employed yet by eZ Systems )
//      * SCK-CEN as a legal entity <http://www.sckcen.be/>
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//



/**
 * Solr search plugin for eZ publish
 */
class eZSolr
{
    /**
     * Constructor
     */
    function __construct()
    {
        eZDebug::createAccumulatorGroup( 'solr', 'Solr search plugin' );
        $this->SolrINI = eZINI::instance( 'solr.ini' );
        $this->FindINI = eZINI::instance( 'ezfind.ini' );
        $this->SiteINI = eZINI::instance( 'site.ini' );
        $this->Solr = self::solrBaseFactory();
    }

    /**
     * Get list of meta attributes and their field types.
     *
     * @return array List of meta attributes and the field types.
     */
    static function metaAttributes()
    {
        return array(
            'id' => 'sint',
            'class_name' => 'text',
            'section_id' => 'sint',
            'owner_id' => 'sint',
            'contentclass_id' => 'sint',
            'current_version' => 'sint',
            'remote_id' => 'string',
            'class_identifier' => 'string',
            'main_node_id' => 'sint',
            'modified' => 'date',
            'published' => 'date',
            'main_parent_node_id' => 'sint' );
    }

    /**
     * Fetches the meta attributes for a given content object
     * and fill the structure described below.
     *
     * @param eZContentObject $object
     * @return array
     * <code>
     *    array(
     *          array( 'name'     => 'id'
     *                 'value'     => 82
     *                 'fieldType' => 'sint' ),
     *                 ...
     *         )
     * </code>
     *
     * @see eZSolr::metaAttributes()
     */
    public static function getMetaAttributesForObject( eZContentObject $object )
    {
        $metaAttributeValues = array();
        foreach( self::metaAttributes() as $attributeName => $fieldType )
        {
            $metaAttributeValues[] = array( 'name' => $attributeName,
                                            'value' => $object->attribute( $attributeName ),
                                            'fieldType' => $fieldType );
        }
        return $metaAttributeValues;
    }

    /**
     * Get list of Node attributes and their field types
     *
     * @return array List of node attributes and field types.
     */
    static function nodeAttributes()
    {
        return array( 'node_id' => 'sint',
                      'path_string' => 'string',
                      'url_alias' => 'string',
                      'is_hidden' => 'boolean',
                      'is_invisible' => 'boolean',
                      'sort_field' => 'string',
                      'sort_order' => 'string' );
    }

    /**
     * Get meta attribute Solr document field type
     *
     * @param string Meta attribute name
     *
     * @return string Solr document field type. Null if meta attribute type does not exists.
     */
    static function getMetaAttributeType( $name )
    {
        $attributeList = array_merge( array( 'guid' => 'string',
                                             'installation_id' => 'string',
                                             'installation_url' => 'string',
                                             'name' => 'text',
                                             'anon_access' => 'boolean',
                                             'language_code' => 'string',
                                             'available_language_codes' => 'string',
                                             'main_url_alias' => 'string',
                                             'owner_name' => 'text',
                                             'owner_group_id' => 'sint',
                                             'path' => 'sint',
                                             'object_states' => 'string'),
                                      self::metaAttributes(),
                                      self::nodeAttributes() );
        if ( empty( $attributeList[$name] ) )
        {
            return null;
        }
        return $attributeList[$name];
    }

    /**
     * Get solr field name, from base name. The base name may either be a
     * meta data name, or an eZ Publish content class attribute, specified by
     * <class identifier>/<attribute identifier>[/<option>]
     *
     * @param string $baseName Base field name.
     * @param boolean $includingClassID conditions the structure of the answer. See return value explanation.
     *
     * @return mixed Internal base name. Returns null if no valid base name was provided.
     *               If $includingClassID is true, an associative array will be returned, as shown below :
     *               <code>
     *               array( 'fieldName'      => 'attr_title_t',
     *                      'contentClassId' => 16 );
     *               </code>
     */
    static function getFieldName( $baseName, $includingClassID = false )
    {
        // If the base name is a meta field, get the correct field name.
        if ( eZSolr::hasMetaAttributeType( $baseName ) )
        {
            return eZSolr::getMetaFieldName( $baseName );
        }
        else
        {
            // Get class and attribute identifiers + optional option.
            $subattribute = null;
            $fieldDef = explode( '/', $baseName );
            // Check if content class attribute ID is provided.
            if ( is_numeric( $fieldDef[0] ) )
            {
                if ( count( $fieldDef ) == 1 )
                {
                    $contectClassAttributeID = $fieldDef[0];
                }
                else if ( count( $fieldDef ) == 2 )
                {
                    list( $contectClassAttributeID, $subattribute ) = $fieldDef;
                }
            }
            else
            {
                switch( count( $fieldDef ) )
                {
                    case 1:
                    {
                        // Return fieldname as is.
                        return $baseName;
                    } break;

                    case 2:
                    {
                        // Field def contains class indentifier and class attribute identifier.
                        list( $classIdentifier, $attributeIdentifier ) = $fieldDef;
                    } break;

                    case 3:
                    {
                        // Field def contains class indentifier, class attribute identifier and optional specification.
                        list( $classIdentifier, $attributeIdentifier, $subattribute ) = $fieldDef;
                    } break;
                }
                $contectClassAttributeID = eZContentObjectTreeNode::classAttributeIDByIdentifier( $classIdentifier . '/' . $attributeIdentifier );
            }
            if ( !$contectClassAttributeID )
            {
                eZDebug::writeNotice( 'Could not get content class from base name: ' . $baseName,
                                      'eZSolr::getFieldName()' );
                return null;
            }
            $contectClassAttribute = eZContentClassAttribute::fetch( $contectClassAttributeID );
            $fieldName = ezfSolrDocumentFieldBase::getFieldName( $contectClassAttribute, $subattribute );

            if ( $includingClassID )
            {
                return array( 'fieldName'      => $fieldName,
                              'contentClassId' => $contectClassAttribute->attribute( 'contentclass_id' ) );
            }
            else
                return $fieldName;
        }
    }

    /**
     * Check if eZSolr has meta attribute type.
     *
     * @param string Meta attribute name
     *
     * @return string Solr document field type
     */
    static function hasMetaAttributeType( $name )
    {
        return self::getMetaAttributeType( $name ) !== null;
    }

    /**
     * @deprecated since eZ Find 2.1
     *
     * Get meta attribute field name
     *
     * @param string Meta attribute field name ( base )
     *
     * @return string Solr doc field name
     */
    static function getMetaFieldName( $baseName )
    {
        /*
        return self::$SolrDocumentFieldName->lookupSchemaName( 'meta_' . $baseName,
                                                               eZSolr::getMetaAttributeType( $baseName ) );
        */
        return ezfSolrDocumentFieldBase::generateMetaFieldName( $baseName );
    }

    /**
     * Get attribute field name base. Extracts the base name from
     * attribute field name
     *
     * @param string Full field name, example: meta_owner_id_s
     *
     * @return string Base name, example: owner_id . Returns null if no matches are found.
     */
    static function getFieldBaseName( $fieldName )
    {
        $matches = array();
        if ( preg_match( '/w*_(.*)_w*/', $fieldName, $matches ) )
        {
            return $matches[1];
        }

        return null;
    }

    /**
     * @since eZ Find 2.1
     *
     * Returns the relative URL Alias for a given search result,
     * depending on whether a subtree filter was applied or not.
     *
     * @param array $doc The search result, directly received from Solr.
     * @return string The URL Alias corresponding the the search result
     */
    protected function getUrlAlias( $doc )
    {
        if ( isset( $this->postSearchProcessingData['subtree_array'] ) and !empty( $this->postSearchProcessingData['subtree_array'] ) )
        {
            foreach ( $this->postSearchProcessingData['subtree_array'] as $subtree )
            {
                foreach ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'path_string' )] as $pathString )
                {
                    if ( substr_count( $pathString, '/' . $subtree . '/' ) > 0 )
                    {
                        $nodeArray = explode( '/', rtrim( $pathString, '/' ));
                        $nodeID = array_pop( $nodeArray );

                        if ( ( $node = eZContentObjectTreeNode::fetch( $nodeID ) ) !== null )
                            return $node->attribute( 'url_alias' );
                    }
                }
            }
        }
        return $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' )];
    }

    /**
     * Adds a content object to the Solr search server.
     *
     * @param eZContentObject $contentObject object to add to search engine.
     * @param boolean $commit commit flag. Set if commit should be run after
     *        adding object. If commit flag is set, run optimize() as well every
     *        1000nd time this function is run.
     * @param bool
    */
    function addObject( $contentObject, $commit = true )
    {
        // Add all translations to the document list
        $docList = array();

        // Check if we need to index this object after all
        // Exclude if class identifier is in the exclude list for classes
        $excludeClasses = $this->FindINI->variable( 'IndexExclude', 'ClassIdentifierList' );
        if ( $excludeClasses && in_array( $contentObject->attribute( 'class_identifier' ), $excludeClasses ) )
        {
            return true;
        }
        // Get global object values
        $mainNode = $contentObject->attribute( 'main_node' );
        // initialize array of parent node path ids, needed for multivalued path field and subtree filters
        $nodePathArray = array();
        if ( !$mainNode )
        {
            eZDebug::writeError( 'Unable to fetch main node for object: ' . $contentObject->attribute( 'id' ), 'eZSolr::addObject()' );
            return false;
        }
        //included in $nodePathArray
        //$pathArray = $mainNode->attribute( 'path_array' );
        $currentVersion = $contentObject->currentVersion();

        // Get object meta attributes.
        $metaAttributeValues = self::getMetaAttributesForObject( $contentObject );

        // Get node attributes.
        $nodeAttributeValues = array();
        foreach( $contentObject->attribute( 'assigned_nodes' ) as $contentNode )
        {
            foreach( eZSolr::nodeAttributes() as $attributeName => $fieldType )
            {
                $nodeAttributeValues[] = array( 'name' => $attributeName,
                                                'value' => $contentNode->attribute( $attributeName ),
                                                'fieldType' => $fieldType );
            }
            $nodePathArray[] = $contentNode->attribute( 'path_array' );

        }

        // Check anonymous user access.
        if ( $this->FindINI->variable( 'SiteSettings', 'IndexPubliclyAvailable' ) == 'enabled' )
        {
            $anonymousUserID = $this->SiteINI->variable( 'UserSettings', 'AnonymousUserID' );
            $currentUserID = eZUser::currentUserID();
            $user = eZUser::instance( $anonymousUserID );
            eZUser::setCurrentlyLoggedInUser( $user, $anonymousUserID );
            $anonymousAccess = $contentObject->attribute( 'can_read' );
            $user = eZUser::instance( $currentUserID );
            eZUser::setCurrentlyLoggedInUser( $user, $currentUserID );
            $anonymousAccess = $anonymousAccess ? 'true' : 'false';
        }
        else
        {
            $anonymousAccess = 'false';
        }

        // Load index time boost factors if any
        //$boostMetaFields = $this->FindINI->variable( "IndexBoost", "MetaField" );
        $boostClasses = $this->FindINI->variable( 'IndexBoost', 'Class' );
        $boostAttributes = $this->FindINI->variable( 'IndexBoost', 'Attribute' );
        $boostDatatypes = $this->FindINI->variable( 'IndexBoost', 'Datatype' );
        $reverseRelatedScale = $this->FindINI->variable( 'IndexBoost', 'ReverseRelatedScale' );

        // Initialise default doc boost
        $docBoost = 1.0;
        $contentClassIdentifier = $contentObject->attribute( 'class_identifier' );
        // Just test if the boost factor is defined by checking if it has a numeric value
        if ( isset( $boostClasses[$contentClassIdentifier] ) && is_numeric( $boostClasses[$contentClassIdentifier] ) )
        {
            $docBoost += $boostClasses[$contentClassIdentifier];
        }
        // Google like boosting, using eZ Publish reverseRelatedObjectCount
        $reverseRelatedObjectCount = $contentObject->reverseRelatedObjectCount();
        $docBoost += $reverseRelatedScale * $reverseRelatedObjectCount;

        //  Create the list of available languages for this version :
        $availableLanguages = $currentVersion->translationList( false, false );

        // Loop over each language version and create an eZSolrDoc for it
        foreach( $availableLanguages as $languageCode )
        {
            $doc = new eZSolrDoc( $docBoost, $languageCode );
            // Set global unique object ID
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' ), $this->guid( $contentObject, $languageCode ) );

            // Set installation identifier
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' ), self::installationID() );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_url' ),
                            $this->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $this->SiteINI->variable( 'SiteSettings', 'SiteURL' ) . '/' );

            // Set Object attributes
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'anon_access' ), $anonymousAccess );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' ), $languageCode );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'available_language_codes' ), $availableLanguages );

            if ( $owner = $contentObject->attribute( 'owner' ) )
            {
                // Set owner name
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_name' ),
                                $owner->name( false, $languageCode ) );

                // Set owner group ID
                foreach( $owner->attribute( 'parent_nodes' ) as $groupID )
                {
                    $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_group_id' ), $groupID );
                }
            }

            // from eZ Publish 4.1 only: object states
            // so let's check if the content object has it
            if (method_exists( $contentObject, 'stateIDArray'))
            {
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'object_states' ),
                                $contentObject->stateIDArray() );
            }

            // Set content object meta attribute values.
            foreach ( $metaAttributeValues as $metaInfo )
            {
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Set content node meta attribute values.
            foreach ( $nodeAttributeValues as $metaInfo )
            {
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Add main url_alias
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' ), $mainNode->attribute( 'url_alias' ) );

            // add nodeid of all parent nodes path elements
            foreach ( $nodePathArray as $pathArray )
            {
                foreach ( $pathArray as $pathNodeID)
                {
                    $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'path' ), $pathNodeID );
                }
            }

            eZContentObject::recursionProtectionStart();

            // Loop through all eZContentObjectAttributes and add them to the Solr document.
            foreach ( $currentVersion->contentObjectAttributes( $languageCode ) as $attribute )
            {
                $metaDataText = '';
                $classAttribute = $attribute->contentClassAttribute();
                $attributeIdentifier = $classAttribute->attribute( 'identifier' );
                $combinedIdentifier = $contentClassIdentifier . '/' . $attributeIdentifier;
                $boostAttribute = false;
                if ( isset( $boostAttributes[$attributeIdentifier]) && is_numeric( $boostAttributes[$attributeIdentifier]))
                {
                    $boostAttribute = $boostAttributes[$attributeIdentifier];
                }
                if ( isset( $boostAttributes[$combinedIdentifier]) && is_numeric( $boostAttributes[$combinedIdentifier]))
                {
                    $boostAttribute += $boostAttributes[$combinedIdentifier];
                }
                if ( $classAttribute->attribute( 'is_searchable' ) == 1 )
                {
                    $documentFieldBase = ezfSolrDocumentFieldBase::getInstance( $attribute );
                    $this->addFieldBaseToDoc( $documentFieldBase, $doc, $boostAttribute );
                }
            }
            eZContentObject::recursionProtectionEnd();

            $docList[] = $doc;
        }

        $optimize = false;
        if ( $this->FindINI->variable( 'IndexOptions', 'DisableDirectCommits' ) === 'true' )
        {
            $commit = false;
        }
        $commitWithin = 0;
        if ( $this->FindINI->variable( 'IndexOptions', 'CommitWithin' ) > 0 )
        {
            $commitWithin = $this->FindINI->variable( 'IndexOptions', 'CommitWithin' );
        }
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }
        return $this->Solr->addDocs( $docList, $commit, $optimize, $commitWithin );

    }

    /**
     * Add instance of ezfSolrDocumentFieldBase to Solr document.
     *
     * @param ezfSolrDocumentFieldBase Instance of ezfSolrDocumentFieldBase
     * @param eZSolrDoc Solr document
     */
    function addFieldBaseToDoc( ezfSolrDocumentFieldBase $fieldBase, eZSolrDoc $doc, $boost = false )
    {
        $fieldBaseData = $fieldBase->getData();
        if ( empty( $fieldBaseData ) )
        {
            if (is_object($fieldBase) )
            {
                $contentClassAttribute = $fieldBase->ContentObjectAttribute->attribute( 'contentclass_attribute' );
                $fieldName = $fieldBase->getFieldName( $contentClassAttribute );
                $errorMessage = 'empty array for ' . $fieldName;
            }
            else
            {
                $errorMessage = '$fieldBase not an object';
            }
            eZDebug::writeNotice( $errorMessage , 'eZSolr::addFieldBaseToDoc' );
            return false;
        }
        else
        {
           foreach( $fieldBaseData as $key => $value )
           {
                $doc->addField( $key, $value, $boost );
           }
           return true;
        }

    }

    /**
     * Performs a solr COMMIT
     */
    function commit()
    {
        //$updateURI = $this->SearchServerURI . '/update';
        $this->Solr->commit();
    }

    /**
     * Performs a solr OPTIMIZE call
     */
    function optimize( $withCommit = false )
    {
        $this->Solr->optimize( $withCommit );
    }

    /**
     * Removes an object from the Solr search server
     * 
     * @param eZContentObject $contentObject the content object to remove
     * @param bool $commit wether or not to commit after removing the object
     * 
     * @return bool true if removal was successful
     */
    function removeObject( $contentObject, $commit = true )
    {
        // 1: remove the assciated "elevate" configuration
        eZFindElevateConfiguration::purge( '', $contentObject->attribute( 'id' ) );
        eZFindElevateConfiguration::synchronizeWithSolr();

        // @todo Remove if accepted. Optimize is bad on runtime.
        $optimize = false;
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }

        // 2: create a delete array with all the required infos, groupable by language
        $languages = eZContentLanguage::fetchList();
        foreach( $languages as $language )
        {
            $languageCode = $language->attribute( 'locale' );
            $docs[$languageCode] = $this->guid( $contentObject, $languageCode );
        }
        return $this->Solr->deleteDocs( $docs, false, $commit, $optimize );
    }

    /**
     * Search on the Solr search server
     * @todo: add functionality not to call the DB to recreate objects : $asObjects == false
     *
     * @param string search term
     * @param array parameters. @see ezfeZPSolrQueryBuilder::buildSearch()
     * @see ezfeZPSolrQueryBuilder::buildSearch()
     * @param array search types. Reserved.
     *
     * @return array List of eZFindResultNode objects.
     */
    function search( $searchText, $params = array(), $searchTypes = array() )
    {
        eZDebug::createAccumulator( 'Search', 'eZ Find' );
        eZDebug::accumulatorStart( 'Search' );
        $error = 'Server not running';
        $searchCount = 0;

        if ( $this->SiteINI->variable( 'SearchSettings', 'AllowEmptySearch' ) == 'disabled' &&
             trim( $searchText ) == '' )
        {
            $error = 'Empty search is not allowed.';
            eZDebug::writeNotice( $error,
                                  'eZSolr::search()' );
            $resultArray = null;
        }
        else
        {
            eZDebug::createAccumulator( 'Query build', 'eZ Find' );
            eZDebug::accumulatorStart( 'Query build' );
            $queryBuilder = new ezfeZPSolrQueryBuilder( $this );
            $queryParams = $queryBuilder->buildSearch( $searchText, $params, $searchTypes );
            eZDebug::accumulatorStop( 'Query build' );

            eZDebug::createAccumulator( 'Engine time', 'eZ Find' );
            eZDebug::accumulatorStart( 'Engine time' );
            $resultArray = $this->Solr->rawSearch( $queryParams );
            eZDebug::accumulatorStop( 'Engine time' );
        }

        if (! $resultArray )
        {
            eZDebug::accumulatorStop( 'Search' );
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => new ezfSearchResultInfo( array( 'error' => ezi18n( 'ezfind', $error ) ) ) );
        }

        $highLights = array();
        if ( !empty( $resultArray['highlighting'] ) )
        {
            foreach ( $resultArray['highlighting'] as $id => $highlight )
            {
                $highLightStrings = array();
                //implode apparently does not work on associative arrays that contain arrays
                //$element being an array as well
                foreach ($highlight as $key => $element)
                {
                    $highLightStrings[] = implode(' ', $element);
                }
                $highLights[$id] = implode(' ...  ', $highLightStrings);

            }
        }
        if ( count($resultArray) > 0 )
        {
            $result = $resultArray['response'];
            $searchCount = $result['numFound'];
            $maxScore = $result['maxScore'];
            $docs = $result['docs'];
            $localNodeIDList = array();
            $objectRes = array();
            $nodeRowList = array();

            // Loop through result, and get eZContentObjectTreeNode ID
            foreach ( $docs as $idx => $doc )
            {
                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    $localNodeIDList[] = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0];
                }
            }

            if ( count( $localNodeIDList ) )
            {
                $tmpNodeRowList = eZContentObjectTreeNode::fetch( $localNodeIDList, false, false );
                // Workaround for eZContentObjectTreeNode::fetch behaviour
                if ( count( $localNodeIDList ) === 1 )
                {
                    $tmpNodeRowList = array( $tmpNodeRowList );
                }
                if ( $tmpNodeRowList )
                {
                    foreach( $tmpNodeRowList as $nodeRow )
                    {
                        $nodeRowList[$nodeRow['node_id']] = $nodeRow;
                    }
                }
                unset( $tmpNodeRowList );
            }

            foreach ( $docs as $idx => $doc )
            {
                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    // Search result document is from current installation
//                    var_dump( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' ), $doc, $nodeRowList );die();
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    if ( !$resultTree->attribute( 'can_read' ) )
                    {
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0],
                                              'eZSolr::search()' );
                        continue;
                    }

                    $urlAlias = $this->getUrlAlias( $doc );
                    $globalURL = $urlAlias . '/(language)/' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )];
                    eZURI::transformURI( $globalURL );
                }
                else
                {
                    $resultTree = new eZFindResultNode();
                    $resultTree->setAttribute( 'is_local_installation', false );
                    $globalURL = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_url' )] .
                        $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )];
                }

                $resultTree->setAttribute( 'name', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'name' )] );
                $resultTree->setAttribute( 'published', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'published' )] );
                $resultTree->setAttribute( 'global_url_alias', $globalURL );
                $resultTree->setAttribute( 'highlight', isset( $highLights[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' )]] ) ?
                                           $highLights[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' )]] : null );
                /**
                 * $maxScore may be equal to 0 when the QueryElevationComponent is used.
                 * It returns as first results the elevated documents, with a score equal to 0. In case no
                 * other document than the elevated ones are returned, maxScore is then 0 and the
                 * division below raises a warning. If maxScore is equal to zero, we can safely assume
                 * that only elevated documents were returned. The latter have an articifial relevancy of 100%,
                 * which must be reflected in the 'score_percent' attribute of the result node.
                 */
                $maxScore != 0 ? $resultTree->setAttribute( 'score_percent', (int) ( ( $doc['score'] / $maxScore ) * 100 ) ) : $resultTree->setAttribute( 'score_percent', 100 );
                $resultTree->setAttribute( 'language_code', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )] );
                $objectRes[] = $resultTree;
            }
        }

        $stopWordArray = array();

        eZDebug::accumulatorStop( 'Search' );
        return array(
            'SearchResult' => $objectRes,
            'SearchCount' => $searchCount,
            'StopWordArray' => $stopWordArray,
            'SearchExtras' => new ezfSearchResultInfo( $resultArray ) );
    }

    /**
     * More like this is pretty similar to normal search, but usually only the object or node id are sent to Solr
     * However, streams or a search text body can also be passed .. Solr will extract the important terms and build a
     * query for us
     * 
     * @param string $queryType is one of 'noid', 'oid', 'url', 'text'
     * @param $queryValue the node id, object id, url or text body to use
     * @param array parameters. @see ezfeZPSolrQueryBuilder::buildMoreLikeThis()
     *
     * @return array List of eZFindResultNode objects.
     * 
     * @todo: add functionality not to call the DB to recreate objects : $asObjects == false
     */
    function moreLikeThis( $queryType, $queryValue, $params = array() )
    {
        eZDebug::createAccumulator( 'MoreLikeThis', 'eZ Find' );
        eZDebug::accumulatorStart( 'MoreLikeThis' );
        $error = 'Server not running';
        $searchCount = 0;

        if ( trim( $queryType ) == '' || trim( $queryValue ) == '' )
        {
            $error = 'Missing query arguments for More Like This: ' . 'querytype = ' . $queryType . ', Query Value = ' . $queryValue;
            eZDebug::writeNotice( $error, __METHOD__ );
            $resultArray = null;
        }
        else
        {
            eZDebug::createAccumulator( 'Query build', 'eZ Find' );
            eZDebug::accumulatorStart( 'Query build' );
            $queryBuilder = new ezfeZPSolrQueryBuilder( $this );
            $queryParams = $queryBuilder->buildMoreLikeThis( $queryType, $queryValue, $params );
            eZDebug::accumulatorStop( 'Query build' );

            eZDebug::createAccumulator( 'Engine time', 'eZ Find' );
            eZDebug::accumulatorStart( 'Engine time' );
            $resultArray = $this->Solr->rawSolrRequest( '/mlt', $queryParams );
            eZDebug::accumulatorStop( 'Engine time' );
        }

        if (! $resultArray )
        {
            eZDebug::accumulatorStop( 'Search' );
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => new ezfSearchResultInfo( array( 'error' => ezi18n( 'ezfind', $error ) ) ) );
        }

        if ( count($resultArray) > 0 )
        {
            $result = $resultArray['response'];
            $searchCount = $result['numFound'];
            $maxScore = $result['maxScore'];
            $docs = $result['docs'];
            $localNodeIDList = array();
            $objectRes = array();
            $nodeRowList = array();

            // Loop through result, and get eZContentObjectTreeNode ID
            foreach ( $docs as $idx => $doc )
            {
                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    $localNodeIDList[] = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0];
                }
            }

            if ( count( $localNodeIDList ) )
            {
                $tmpNodeRowList = eZContentObjectTreeNode::fetch( $localNodeIDList, false, false );
                // Workaround for eZContentObjectTreeNode::fetch behaviour
                if ( count( $localNodeIDList ) === 1 )
                {
                    $tmpNodeRowList = array( $tmpNodeRowList );
                }
                if ( $tmpNodeRowList )
                {
                    foreach( $tmpNodeRowList as $nodeRow )
                    {
                        $nodeRowList[$nodeRow['node_id']] = $nodeRow;
                    }
                }
                unset( $tmpNodeRowList );
            }

            foreach ( $docs as $idx => $doc )
            {
                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    // Search result document is from current installation
//                    var_dump( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' ), $doc, $nodeRowList );die();
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    if ( !$resultTree->attribute( 'can_read' ) )
                    {
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )][0],
                                              'eZSolr::search()' );
                        continue;
                    }


                    $globalURL = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )];
                    eZURI::transformURI( $globalURL );
                }
                else
                {
                    $resultTree = new eZFindResultNode();
                    $resultTree->setAttribute( 'is_local_installation', false );
                    $globalURL = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_url' )] .
                        $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )];
                }

                $resultTree->setAttribute( 'name', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'name' )] );
                $resultTree->setAttribute( 'published', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'published' )] );
                $resultTree->setAttribute( 'global_url_alias', $globalURL );
                $resultTree->setAttribute( 'highlight', isset( $highLights[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' )]] ) ?
                                           $highLights[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' )]] : null );
                $resultTree->setAttribute( 'score_percent', (int) ( ( $doc['score'] / $maxScore ) * 100 ) );
                $resultTree->setAttribute( 'language_code', $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' )] );
                $objectRes[] = $resultTree;
            }
        }

        $stopWordArray = array();

        eZDebug::accumulatorStop( 'Search' );
        eZDebug::writeDebug( $resultArray['interestingTerms'], 'MoreLikeThis terms' );
        return array(
            'SearchResult' => $objectRes,
            'SearchCount' => $searchCount,
            'StopWordArray' => $stopWordArray,
            'SearchExtras' => new ezfSearchResultInfo( $resultArray ) );
    }

    /**
     * Initialise / rebuild the Spell checker
     * not needed in current implementation with an index based spell checker
     * and autorebuilds upon commits
     *
     * @package unfinished
     * @return array Solr result set.
     */
    function initSpellChecker()
    {

        $return = $this->Solr->rawSearch( array( 'q' => 'solr', 'qt' => 'spellchecker', 'wt' => 'php', 'cmd' => 'rebuild') );

    }

    /**
     * Experimental: search independent spell check
     * use spellcheck option in search for spellchecking search results
     *
     * @package unfinished
     * @return array Solr result set.
     * @todo: configure different spell check handlers
     *
     */
    function spellCheck ( $string, $onlyMorePopular = false, $suggestionCount = 1, $accuracy=0.5 )
    {
        $onlyMorePopularString = $onlyMorePopular ? 'true' : 'false';
        return $this->Solr->rawSearch( array( 'q' => $string, 'qt' => 'spellchecker',
                             'suggestionCount' => $suggestionCount, 'wt' => 'php',
                             'accuracy' => $accuracy, 'onlyMorePopular' => $onlyMorePopularString ) );

    }

    /**
     * Returns the eZ publish installation ID, used by eZ find to identify sites
     * @return string installaiton ID.
     */
    static function installationID()
    {
        if ( !empty( self::$InstallationID ) )
        {
            return self::$InstallationID;
        }
        $db = eZDB::instance();

        $resultSet = $db->arrayQuery( 'SELECT value FROM ezsite_data WHERE name=\'ezfind_site_id\'' );

        if ( count( $resultSet ) >= 1 )
        {
            self::$InstallationID = $resultSet[0]['value'];
        }
        else
        {
            self::$InstallationID = md5( time() . '-' . mt_rand() );
            $db->query( 'INSERT INTO ezsite_data ( name, value ) values( \'ezfind_site_id\', \'' . self::$InstallationID . '\' )' );
        }

        return self::$InstallationID;
    }

    /**
     * Computes the unique ID of a content object language version
     *
     * @param eZContentObject $contentObject The content object
     * @param string $languageCode
     * @return string guid
     */
    function guid( $contentObject, $languageCode = '' )
    {
        return md5( self::installationID() . '-' . $contentObject->attribute( 'id' ) . '-' . $languageCode );
    }

    /**
     * Clean up search index for current installation.
     * @return bool true if cleanup was successful
    **/
    function cleanup( $allInstallations = false, $optimize = false )
    {
        if ( $allInstallations === true )
        {
            return $this->Solr->deleteDocs( array(), '*:*', true, $optimize );
        }
        else
        {
            return $this->Solr->deleteDocs( array(), ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' ) . ':' . self::installationID(), true );
        }

    }

    /**
     * For advanced search
     */
    public function supportedSearchTypes()
    {
        $searchTypes = array( array( 'type' => 'attribute',
                                     'subtype' =>  'fulltext',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' =>  'patterntext',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' =>  'integer',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' =>  'integers',
                                     'params' => array( 'classattribute_id', 'values' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' =>  'byrange',
                                     'params' => array( 'classattribute_id' , 'from' , 'to'  ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'byidentifier',
                                     'params' => array( 'classattribute_id', 'identifier', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'byidentifierrange',
                                     'params' => array( 'classattribute_id', 'identifier', 'from', 'to' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'integersbyidentifier',
                                     'params' => array( 'classattribute_id', 'identifier', 'values' ) ),
                              array( 'type' => 'fulltext',
                                     'subtype' => 'text',
                                     'params' => array( 'value' ) ) );
        $generalSearchFilter = array( array( 'type' => 'general',
                                             'subtype' => 'class',
                                             'params' => array( array( 'type' => 'array',
                                                                       'value' => 'value'),
                                                                'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'publishdate',
                                             'params'  => array( 'value', 'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'subtree',
                                             'params'  => array( array( 'type' => 'array',
                                                                        'value' => 'value'),
                                                                 'operator' ) ) );
        return array( 'types' => $searchTypes,
                      'general_filter' => $generalSearchFilter );
    }

    /**
     * Gets engine text
     *
     * @return string engine text
     */
    static function engineText()
    {
        return ezi18n( 'ezfind', 'eZ Find 2.1 search plugin &copy; 2009 eZ Systems AS, powered by Apache Solr 1.4dev' );
    }

    /**
     * @see eZSearch::needCommit()
     * @return boolean
     */
    public function needCommit()
    {
        return true;
    }

    /**
     * @see eZSearch::needRemoveWithUpdate()
     * @return boolean
     */
    public function needRemoveWithUpdate()
    {
        return false;
    }

    /**
     * Called when a new section is assigned to an object, trough a node.
     * Simply re-index for now
     *
     * @todo: defer to cron if there are children involved and re-index these too
     * @todo when Solr supports it: update fields only
     *
     * @return void
     * @see eZSearch::updateNodeSection()
     */
    public function updateNodeSection( $nodeID, $sectionID )
    {
        $contentObject = eZContentObject::fetchByNodeID( $nodeID );
        $this->addObject( $contentObject );
    }

    /**
     * Called when a node's visibility is modified.
     * Simply re-index for now.
     *
     * @todo: defer to cron if there are children involved and re-index these too
     * @todo when Solr supports it: update fields only
     *
     * @param $nodeID
     * @param $action
     * @return void
     * @see eZSearch::updateNodeVisibility()
     */
    public function updateNodeVisibility( $nodeID, $action )
    {
        $contentObject = eZContentObject::fetchByNodeID( $nodeID );
        $this->addObject( $contentObject );
    }

    /**
     * Called when a node assignement is added to an object.
     * Simply re-index for now.
     *
     * @todo: defer to cron if there are children involved and re-index these too
     * @todo when Solr supports it: update fields only
     *
     * @param $mainNodeID
     * @param $objectID
     * @param $nodeAssignmentIDList
     * @return unknown_type
     * @see eZSearch::addNodeAssignment()
     */
    public function addNodeAssignment( $mainNodeID, $objectID, $nodeAssignmentIDList )
    {
        $contentObject = eZContentObject::fetch( $objectID );
        $this->addObject( $contentObject );
    }

    /**
     * Called when a node assignement is removed of an object's.
     * Simply re-index for now.
     *
     * @todo: defer to cron if there are children involved and re-index these too
     * @todo when Solr supports it: update fields only
     *
     * @param $mainNodeID
     * @param $objectID
     * @param $nodeAssignmentIDList
     * @return unknown_type
     * @see eZSearch::removeNodeAssignment()
     */
    public function removeNodeAssignment( $mainNodeID, $newMainNodeID, $objectID, $nodeAssigmentIDList )
    {
        $contentObject = eZContentObject::fetch( $objectID );
        $this->addObject( $contentObject );
    }

    /**
     * Called when two nodes are swapped.
     * Simply re-index for now.
     *
     * @todo when Solr supports it: update fields only
     *
     * @param $nodeID
     * @param $selectedNodeID
     * @param $nodeIdList
     * @return void
     */
    public function swapNode( $nodeID, $selectedNodeID, $nodeIdList = array() )
    {
        $contentObject1 = eZContentObject::fetchByNodeID( $nodeID );
        $contentObject2 = eZContentObject::fetchByNodeID( $selectedNodeID );
        $this->addObject( $contentObject1 );
        $this->addObject( $contentObject2 );
    }

    /**
     * update search index upon object state changes:
     * simply re-index for now
     * @todo: defer to cron if there are children involved and re-index these too
     * @todo when Solr supports it: update fields only
     */
    public function updateObjectState( $objectID, $objectStateList )
    {
        $contentObject = eZContentObject::fetch( $objectID );
        $this->addObject( $contentObject );
    }
    
    /**
     * Returns the relevant eZSolrBase, depending if MultiCore is enabled or not
     * 
     * @return eZSolrBase
     */
    public static function solrBaseFactory()
    {
        $ini = eZINI::instance( 'ezfind.ini' );
        if ( $ini->variable( 'LanguageSearch', 'MultiCore' ) == 'enabled' )
            return new eZSolrMultiCoreBase();
        else
            return new eZSolrBase();
    }

    /**
     * eZSolrBase instance used for interaction with the solr server
     * @var eZSolrBase
     */
    var $Solr;
    
    /// Object vars
    var $SolrINI;
    var $FindINI;
    var $SiteINI;
    var $SolrDocumentFieldBase;

    /**
     * @since eZ Find 2.1
     *
     * Used to store useful data/metadata for post search processing.
     * Will mostly be updated by the query builder. Keys should be named after the fetch function parameters,
     * when applicable.
     *
     * Example : Knowing which subtress were used as filters allows
     *           for picking the right URL for a potentially multi-located
     *           search result.
     *
     * Example :
     * <code>
     *     array( 'subtree_array' => array( 2, 43 ) );
     * </code>
     *
     * @see ezfeZPSolrQueryBuilder::searchPluginInstance
     * @var array
     */
    public $postSearchProcessingData = array();

    static $InstallationID;
    static $SolrDocumentFieldName;
}

eZSolr::$SolrDocumentFieldName = new ezfSolrDocumentFieldName();

?>
<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
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



/*!
  eZSolr is a search plugin to eZ Publish.
*/
class eZSolr
{
    /*!
     \brief Constructor
    */
    function eZSolr()
    {
        eZDebug::createAccumulatorGroup( 'solr', 'Solr search plugin' );
        $this->SolrINI = eZINI::instance( 'solr.ini' );
        $this->FindINI = eZINI::instance( 'ezfind.ini' );
        $this->SiteINI = eZINI::instance( 'site.ini' );
        $this->SearchServerURI = $this->SolrINI->variable( 'SolrBase', 'SearchServerURI' );
        $this->Solr = new eZSolrBase( $this->SearchServerURI );
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
                                             'main_url_alias' => 'string',
                                             'owner_name' => 'text',
                                             'owner_group_id' => 'sint',
                                             'path' => 'sint' ),
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
     * @param string Base field name.
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
            $options = null;
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
                    list( $contectClassAttributeID, $options ) = $fieldDef;
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
                        list( $classIdentifier, $attributeIdentifier, $options ) = $fieldDef;
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
            $fieldName = ezfSolrDocumentFieldBase::getFieldName( $contectClassAttribute, $options );

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
     * Get meta attribute field name
     *
     * @param string Meta attribute field name ( base )
     *
     * @return string Solr doc field name
     */
    static function getMetaFieldName( $baseName )
    {
        return self::$SolrDocumentFieldName->lookupSchemaName( 'meta_' . $baseName,
                                                               eZSolr::getMetaAttributeType( $baseName ) );
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

    /*!
     \brief Adds a content object to the Solr search server.

     Adds object to eZFind search engine.

     \param eZContentObject object to add to search engine.
     \param boolean commit flag. Set if commit should be run after adding object.
            If commit flag is set, run optimize() as well every 1000nd time this function is run.
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
            return;
        }
        // Get global object values
        $mainNode = $contentObject->attribute( 'main_node' );
        if ( !$mainNode )
        {
            eZDebug::writeError( 'Unable to fetch main node for object: ' . $contentObject->attribute( 'id' ), 'eZSolr::addObject()' );
            return;
        }
        $pathArray = $mainNode->attribute( 'path_array' );
        $currentVersion = $contentObject->currentVersion();

        // Get object meta attributes.
        $metaAttributeValues = array();
        foreach( eZSolr::metaAttributes() as $attributeName => $fieldType )
        {
            $metaAttributeValues[] = array( 'name' => $attributeName,
                                            'value' => $contentObject->attribute( $attributeName ),
                                            'fieldType' => $fieldType );
        }

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

        foreach( $currentVersion->translationList( false, false ) as $languageCode )
        {
            $doc = new eZSolrDoc( $docBoost );
            // Set global unique object ID
            $doc->addField( self::getMetaFieldName( 'guid' ), $this->guid( $contentObject, $languageCode ) );

            // Set installation identifier
            $doc->addField( self::getMetaFieldName( 'installation_id' ), self::installationID() );
            $doc->addField( self::getMetaFieldName( 'installation_url' ),
                            $this->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $this->SiteINI->variable( 'SiteSettings', 'SiteURL' ) . '/' );

            // Set Object attributes
            $doc->addField( self::getMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
            $doc->addField( self::getMetaFieldName( 'anon_access' ), $anonymousAccess );
            $doc->addField( self::getMetaFieldName( 'language_code' ), $languageCode );
            if ( $owner = $contentObject->attribute( 'owner' ) )
            {
                // Set owner name
                $doc->addField( self::getMetaFieldName( 'owner_name' ),
                                $owner->name( false, $languageCode ) );

                // Set owner group ID
                foreach( $owner->attribute( 'parent_nodes' ) as $groupID )
                {
                    $doc->addField( self::getMetaFieldName( 'owner_group_id' ), $groupID );
                }
            }

            // Set content object meta attribute values.
            foreach ( $metaAttributeValues as $metaInfo )
            {
                $doc->addField( self::getMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Set content node meta attribute values.
            foreach ( $nodeAttributeValues as $metaInfo )
            {
                $doc->addField( self::getMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Add main url_alias
            $doc->addField( self::getMetaFieldName( 'main_url_alias' ), $mainNode->attribute( 'url_alias' ) );

            foreach ( $pathArray as $pathNodeID )
            {
                $doc->addField( self::getMetaFieldName( 'path' ), $pathNodeID );
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
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }
        $this->Solr->addDocs( $docList, $commit, $optimize );

    }

    /**
     * Add instance of ezfSolrDocumentFieldBase to Solr document.
     *
     * @param ezfSolrDocumentFieldBase Instance of ezfSolrDocumentFieldBase
     * @param eZSolrDoc Solr document
     */
    function addFieldBaseToDoc( ezfSolrDocumentFieldBase $fieldBase, eZSolrDoc $doc, $boost = false )
    {

        foreach( $fieldBase->getData() as $key => $value )
        {
                $doc->addField( $key, $value, $boost );
        }
    }

    /*!
     Send commit message to eZ Find engine
    */
    function commit()
    {
        //$updateURI = $this->SearchServerURI . '/update';
        $this->Solr->commit();
    }

    /*!
     Send optimize message to eZ Find engine
     */
    function optimize( $withCommit = false )
    {
        $this->Solr->optimize( $withCommit );
    }

    /**
     * Removes an object from the Solr search server
     */
    function removeObject( $contentObject, $commit = true )
    {
        $optimize = false;
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }
        $this->Solr->deleteDocs( array(),
                                 self::getMetaFieldName( 'id' ) . ':' . $contentObject->attribute( 'id' ) . ' AND '.
                                 self::getMetaFieldName( 'installation_id' ) . ':' . self::installationID(),
                                 $commit, $optimize );
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
            $queryBuilder = new ezfeZPSolrQueryBuilder();
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
                if ( $doc[self::getMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    $localNodeIDList[] = $doc[self::getMetaFieldName( 'main_node_id' )][0];
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
                if ( $doc[self::getMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    // Search result document is from current installation
//                    var_dump( self::getMetaFieldName( 'main_node_id' ), $doc, $nodeRowList );die();
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[self::getMetaFieldName( 'main_node_id' )][0]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[self::getMetaFieldName( 'main_node_id' )][0]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    if ( !$resultTree->attribute( 'can_read' ) )
                    {
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[self::getMetaFieldName( 'main_node_id' )][0],
                                              'eZSolr::search()' );
                        continue;
                    }


                    $globalURL = $doc[self::getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[self::getMetaFieldName( 'language_code' )];
                    eZURI::transformURI( $globalURL );
                }
                else
                {
                    $resultTree = new eZFindResultNode();
                    $resultTree->setAttribute( 'is_local_installation', false );
                    $globalURL = $doc[self::getMetaFieldName( 'installation_url' )] .
                        $doc[self::getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[self::getMetaFieldName( 'language_code' )];
                }

                $resultTree->setAttribute( 'name', $doc[self::getMetaFieldName( 'name' )] );
                $resultTree->setAttribute( 'published', $doc[self::getMetaFieldName( 'published' )] );
                $resultTree->setAttribute( 'global_url_alias', $globalURL );
                $resultTree->setAttribute( 'highlight', isset( $highLights[$doc[self::getMetaFieldName( 'guid' )]] ) ?
                                           $highLights[$doc[self::getMetaFieldName( 'guid' )]] : null );
                $resultTree->setAttribute( 'score_percent', (int) ( ( $doc['score'] / $maxScore ) * 100 ) );
                $resultTree->setAttribute( 'language_code', $doc[self::getMetaFieldName( 'language_code' )] );
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
     * @todo: add functionality not to call the DB to recreate objects : $asObjects == false
     * @param string $queryType is one of 'noid', 'oid', 'url', 'text'
     * @param $queryValue the node id, object id, url or text body to use
     * @param array parameters. @see ezfeZPSolrQueryBuilder::buildMoreLikeThis()
     *
     * @return array List of eZFindResultNode objects.
     */
    function moreLikeThis( $queryType, $queryValue, $params = array() )
    {
        eZDebug::createAccumulator( 'MoreLikeThis', 'eZ Find' );
        eZDebug::accumulatorStart( 'MoreLikeThis' );
        $error = 'Server not running';
        $searchCount = 0;

        if ( trim( $queryType ) == '' || trim( $queryValue ) == '' )
        {
            $error = 'Missing query arguments for More Like This' . 'querytype = ' . $queryType . ', Query Value = ' . $queryValue;
            eZDebug::writeNotice( $error,
                                  'eZSolr::moreLikeThis()' );
            $resultArray = null;
        }
        else
        {
            eZDebug::createAccumulator( 'Query build', 'eZ Find' );
            eZDebug::accumulatorStart( 'Query build' );
            $queryBuilder = new ezfeZPSolrQueryBuilder();
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
                if ( $doc[self::getMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    $localNodeIDList[] = $doc[self::getMetaFieldName( 'main_node_id' )][0];
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
                if ( $doc[self::getMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    // Search result document is from current installation
//                    var_dump( self::getMetaFieldName( 'main_node_id' ), $doc, $nodeRowList );die();
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[self::getMetaFieldName( 'main_node_id' )][0]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[self::getMetaFieldName( 'main_node_id' )][0]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    if ( !$resultTree->attribute( 'can_read' ) )
                    {
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[self::getMetaFieldName( 'main_node_id' )][0],
                                              'eZSolr::search()' );
                        continue;
                    }


                    $globalURL = $doc[self::getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[self::getMetaFieldName( 'language_code' )];
                    eZURI::transformURI( $globalURL );
                }
                else
                {
                    $resultTree = new eZFindResultNode();
                    $resultTree->setAttribute( 'is_local_installation', false );
                    $globalURL = $doc[self::getMetaFieldName( 'installation_url' )] .
                        $doc[self::getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[self::getMetaFieldName( 'language_code' )];
                }

                $resultTree->setAttribute( 'name', $doc[self::getMetaFieldName( 'name' )] );
                $resultTree->setAttribute( 'published', $doc[self::getMetaFieldName( 'published' )] );
                $resultTree->setAttribute( 'global_url_alias', $globalURL );
                $resultTree->setAttribute( 'highlight', isset( $highLights[$doc[self::getMetaFieldName( 'guid' )]] ) ?
                                           $highLights[$doc[self::getMetaFieldName( 'guid' )]] : null );
                $resultTree->setAttribute( 'score_percent', (int) ( ( $doc['score'] / $maxScore ) * 100 ) );
                $resultTree->setAttribute( 'language_code', $doc[self::getMetaFieldName( 'language_code' )] );
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



    /*!
     Get eZFind installation ID

     \return installaiton ID.
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

    /*!
     Get GlobalID of contentobject

     \param \a $contentObject
     \param \a $languageCode ( optional )

     \return guid
    */
    function guid( $contentObject, $languageCode = '' )
    {
        return md5( self::installationID() . '-' . $contentObject->attribute( 'id' ) . '-' . $languageCode );
    }

    /*!
     Clean up search index for current installation.
    */
    function cleanup()
    {
        $this->Solr->deleteDocs( array(), self::getMetaFieldName( 'installation_id' ) . ':' . self::installationID(), true );
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

    /*!
     Get engine text

     \return engine text
    */
    static function engineText()
    {
        return ezi18n( 'ezfind', 'eZ Find 2.0 search plugin &copy; 2009 eZ Systems AS, powered by Apache Solr 1.4' );
    }

    /// Object vars
    var $SolrINI;
    var $SearchSeverURI;
    var $FindINI;
    var $SiteINI;
    var $SolrDocumentFieldBase;

    static $InstallationID;
    static $SolrDocumentFieldName;
}

eZSolr::$SolrDocumentFieldName = new ezfSolrDocumentFieldName();

?>

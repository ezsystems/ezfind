<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
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
class eZSolr implements ezpSearchEngine
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
        //$this->Solr = self::solrBaseFactory();
        $this->UseMultiLanguageCores = false;
        if ( $this->FindINI->variable( 'LanguageSearch', 'MultiCore' ) == 'enabled' )
        {
            $this->UseMultiLanguageCores = true;
        }
        $this->initLanguageShards();
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
            'class_name' => 'mstring',
            'section_id' => 'sint',
            'owner_id' => 'sint',
            'contentclass_id' => 'sint',
            'current_version' => 'sint',
            'remote_id' => 'mstring',
            'class_identifier' => 'mstring',
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
        foreach ( self::metaAttributes() as $attributeName => $fieldType )
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
                      'path_string' => 'mstring',
                      'url_alias' => 'mstring',
                      'is_hidden' => 'boolean',
                      'is_invisible' => 'boolean',
                      'sort_field' => 'mstring',
                      'sort_order' => 'mstring',
                      'depth' => 'sint',
                      'view_count' => 'sint' );
    }

    /**
     * Get meta attribute Solr document field type
     *
     * @param  string name Meta attribute name
     * @param  string context search, facet, filter, sort
     *
     * @return string Solr document field type. Null if meta attribute type does not exists.
     */
    static function getMetaAttributeType( $name, $context = 'search' )
    {


        $attributeList = array( 'search' => array_merge( array( 'guid' => 'mstring',
                                             'installation_id' => 'mstring',
                                             'installation_url' => 'mstring',
                                             'name' => 'text',
                                             'sort_name' => 'mstring',
                                             'anon_access' => 'boolean',
                                             'language_code' => 'mstring',
                                             'available_language_codes' => 'mstring',
                                             'main_url_alias' => 'mstring',
                                             'main_path_string' => 'mstring',
                                             'owner_name' => 'text',
                                             'owner_group_id' => 'sint',
                                             'path' => 'sint',
                                             'object_states' => 'sint'),
                                      self::metaAttributes(),
                                      self::nodeAttributes() ),
                                'facet' =>  array(
                                             'owner_name' => 'string' ),
                                'filter' => array(),
                                'sort' => array() );
        if ( ! empty( $attributeList[$context][$name] ) )
        {
            return $attributeList[$context][$name];
        }
        elseif ( ! empty( $attributeList['search'][$name] ) )
        {
            return $attributeList['search'][$name];
        }
        else
        {
            return null;
        }
        //return $attributeList[$name];
    }

    /**
     * Get solr field name, from base name. The base name may either be a
     * meta-data name, or an eZ Publish content class attribute, specified by
     * <class identifier>/<attribute identifier>[/<option>]
     *
     * @param string $baseName Base field name.
     * @param boolean $includingClassID conditions the structure of the answer. See return value explanation.
     * @param $context is introduced in ez find 2.2 to allow for more optimal sorting, faceting, filtering
     *
     * @return mixed Internal base name. Returns null if no valid base name was provided.
     *               If $includingClassID is true, an associative array will be returned, as shown below :
     *               <code>
     *               array( 'fieldName'      => 'attr_title_t',
     *                      'contentClassId' => 16 );
     *               </code>
     */
    static function getFieldName( $baseName, $includingClassID = false, $context = 'search' )
    {
        // If the base name is a meta field, get the correct field name.
        if ( eZSolr::hasMetaAttributeType( $baseName, $context ) )
        {
            return eZSolr::getMetaFieldName( $baseName, $context );
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
                eZDebug::writeNotice( 'Could not get content class from base name: ' . $baseName, __METHOD__ );
                return null;
            }
            $contectClassAttribute = eZContentClassAttribute::fetch( $contectClassAttributeID );
            $fieldName = ezfSolrDocumentFieldBase::getFieldName( $contectClassAttribute, $subattribute, $context );

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
    static function hasMetaAttributeType( $name, $context )
    {
        return self::getMetaAttributeType( $name, $context ) !== null;
    }

    /**
     * @todo: cleanup the recursive lookup in ezfSolrDocumentFieldBase::generateMetaFieldName, it is only overhead
     *
     * Get meta attribute field name
     *
     * @param string Meta attribute field name ( base )
     *
     * @return string Solr doc field name
     */
    static function getMetaFieldName( $baseName, $context = 'search' )
    {
        /*
        return self::$SolrDocumentFieldName->lookupSchemaName( 'meta_' . $baseName,
                                                               eZSolr::getMetaAttributeType( $baseName ) );
        */
        return ezfSolrDocumentFieldBase::generateMetaFieldName( $baseName, $context );
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
     * Returns the relative URL Alias for a given search result,
     * depending on whether a subtree filter was applied or not.
     *
     * @param array $doc The search result, directly received from Solr.
     * @return int The NodeID corresponding the search result
     */
    protected function getNodeID( $doc )
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
                        return (int) array_pop( $nodeArray );
                    }
                }
            }
        }
        return (int) $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )];
    }

    /**
     * Adds object $contentObject to the search database.
     *
     * @param eZContentObject $contentObject Object to add to search engine
     * @param bool $commit Whether to commit after adding the object.
     *        If set, run optimize() as well every 1000nd time this function is run.
     * @param $commitWithin Commit within delay (see Solr documentation)
     * @return bool True if the operation succeed.
     */
    function addObject( $contentObject, $commit = true, $commitWithin = 0 )
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
        if ( !$mainNode )
        {
            eZDebug::writeError( 'Unable to fetch main node for object: ' . $contentObject->attribute( 'id' ), __METHOD__ );
            return false;
        }

        $mainNodePathArray = $mainNode->attribute( 'path_array' );
        // initialize array of parent node path ids, needed for multivalued path field and subtree filters
        $nodePathArray = array();

        //included in $nodePathArray
        //$pathArray = $mainNode->attribute( 'path_array' );
        $currentVersion = $contentObject->currentVersion();

        // Get object meta attributes.
        $metaAttributeValues = self::getMetaAttributesForObject( $contentObject );

        // Get node attributes.
        $nodeAttributeValues = array();
        foreach ( $contentObject->attribute( 'assigned_nodes' ) as $contentNode )
        {
            foreach ( eZSolr::nodeAttributes() as $attributeName => $fieldType )
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
        foreach ( $availableLanguages as $languageCode )
        {
            $doc = new eZSolrDoc( $docBoost );
            // Set global unique object ID
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' ), $this->guid( $contentObject, $languageCode ) );

            // Set installation identifier
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' ), self::installationID() );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_url' ),
                            $this->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $this->SiteINI->variable( 'SiteSettings', 'SiteURL' ) . '/' );

            // Set Object attributes
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
            // Also add value to the "sort_name" field as "name" is unsortable, due to Solr limitation (tokenized field)
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'sort_name' ), $contentObject->name( false, $languageCode ) );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'anon_access' ), $anonymousAccess );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' ), $languageCode );
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'available_language_codes' ), $availableLanguages );

            if ( $owner = $contentObject->attribute( 'owner' ) )
            {
                // Set owner name
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_name' ),
                                $owner->name( false, $languageCode ) );

                // Set owner group ID
                foreach ( $owner->attribute( 'parent_nodes' ) as $groupID )
                {
                    $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_group_id' ), $groupID );
                }
            }

            // from eZ Publish 4.1 only: object states
            // so let's check if the content object has it
            if ( method_exists( $contentObject, 'stateIDArray' ) )
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

            // Add main path_string
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_path_string' ), $mainNode->attribute( 'path_string' ) );

            // add nodeid of all parent nodes path elements
            foreach ( $nodePathArray as $pathArray )
            {
                foreach ( $pathArray as $pathNodeID)
                {
                    $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'path' ), $pathNodeID );
                }
            }

            // Since eZ Fnd 2.3
            // cannot call metafield field bame constructor as we are creating multiple fields
            foreach ( $mainNodePathArray as $key => $pathNodeID )
            {
                $doc->addField( 'meta_main_path_element_' . $key . '_si', $pathNodeID );

            }

            eZContentObject::recursionProtectionStart();

            // Loop through all eZContentObjectAttributes and add them to the Solr document.
            // @since eZ Find 2.3: look for the attribute storage setting

            $doAttributeStorage = ( ( $this->FindINI->variable( 'IndexOptions', 'EnableSolrAttributeStorage' ) ) === 'true' ) ? true : false;

            if ( $doAttributeStorage )
            {
                $allAttributeData = array();
            }

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

                if ( $doAttributeStorage )
                {
                    $storageFieldName = ezfSolrStorage::getSolrStorageFieldName( $attributeIdentifier );
                    $attributeData = ezfSolrStorage::getAttributeData( $attribute );
                    $allAttributeData['data_map'][$attributeIdentifier] = $attributeData;
                    $doc->addField( $storageFieldName, ezfSolrStorage::serializeData( $attributeData ) );
                }
            }
            eZContentObject::recursionProtectionEnd();

            if ( $doAttributeStorage )
            {
                $doc->addField( 'as_all_bst', ezfSolrStorage::serializeData( $allAttributeData ) );
            }

            $docList[$languageCode] = $doc;
        }

        $optimize = false;
        if ( $this->FindINI->variable( 'IndexOptions', 'DisableDirectCommits' ) === 'true' )
        {
            $commit = false;
        }
        if ( $commitWithin === 0 && $this->FindINI->variable( 'IndexOptions', 'CommitWithin' ) > 0 )
        {
            $commitWithin = $this->FindINI->variable( 'IndexOptions', 'CommitWithin' );
        }
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }

        if ( $this->UseMultiLanguageCores === true)
        {
            $result = true;
            foreach ( $availableLanguages as $languageCode )
            {
                $languageResult = $this->SolrLanguageShards[$languageCode]->addDocs( array( $docList[$languageCode] ), $commit, $optimize, $commitWithin );
                if ( !$languageResult )
                {
                    $result = false;
                }
            }
            return $result;
        }
        else
        {
            return $this->Solr->addDocs( $docList, $commit, $optimize, $commitWithin );
        }


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
            if ( is_object( $fieldBase ) )
            {
                $contentClassAttribute = $fieldBase->ContentObjectAttribute->attribute( 'contentclass_attribute' );
                $fieldName = $fieldBase->getFieldName( $contentClassAttribute );
                $errorMessage = 'empty array for ' . $fieldName;
            }
            else
            {
                $errorMessage = '$fieldBase not an object';
            }
            eZDebug::writeNotice( $errorMessage , __METHOD__ );
            return false;
        }
        else
        {
           foreach ( $fieldBaseData as $key => $value )
           {
               // since ezfind 2.3, a NULL value returned from $fieldBase in the $value elements is used as a flag not to index
               if ( !is_null( $value ) )
               {
                   $doc->addField( $key, $value, $boost );
               }
           }
           return true;
        }

    }

    /**
     * Performs a solr COMMIT
     */
    function commit()
    {

        if ( $this->UseMultiLanguageCores === true )
        {
            foreach ( $this->SolrLanguageShards as $shard )
            {
                $shard->Solr->commit();
            }
        }
        else
        {
            $this->Solr->commit();
        }

    }

    /**
     * Performs a solr OPTIMIZE call
     */
    function optimize( $withCommit = false )
    {

        if ( $this->UseMultiLanguageCores === true )
        {
            foreach ( $this->SolrLanguageShards as $shard )
            {
                $shard->optimize( $withCommit );
            }
        }
        else
        {
            $this->Solr->optimize( $withCommit );
        }
    }

    /**
     * Removes object $contentObject from the search database.
     *
     * @param eZContentObject $contentObject the content object to remove
     * @param bool $commit Whether to commit after removing the object
     * @return bool True if the operation succeed.
     */
    function removeObject( $contentObject, $commit = null )
    {
        /*
         * @since eZFind 2.2: allow delayed commits if explicitely set as configuration setting and
         * the parameter $commit it is not set
         * Default behaviour is as before
         */
        if ( !isset( $commit ) && ( $this->FindINI->variable( 'IndexOptions', 'DisableDeleteCommits' ) === 'true' ) )
        {
            $commit = false;
        }
        elseif ( !isset( $commit ) )
        {
            $commit = true;
        }

        // 1: remove the assciated "elevate" configuration
        eZFindElevateConfiguration::purge( '', $contentObject->attribute( 'id' ) );
        //eZFindElevateConfiguration::synchronizeWithSolr();
        $this->pushElevateConfiguration();

        // @todo Remove if accepted. Optimize is bad on runtime.
        $optimize = false;
        if ( $commit && ( $this->FindINI->variable( 'IndexOptions', 'OptimizeOnCommit' ) === 'enabled' ) )
        {
            $optimize = true;
        }

        // 2: create a delete array with all the required infos, groupable by language
        $languages = eZContentLanguage::fetchList();
        foreach ( $languages as $language )
        {
            $languageCode = $language->attribute( 'locale' );
            $docs[$languageCode] = $this->guid( $contentObject, $languageCode );
        }
        if ( $this->UseMultiLanguageCores === true )
        {
            foreach ( $docs as $languageCode => $doc )
            {
                $this->SolrLanguageShards[$languageCode]->deleteDocs( array( $doc ), false, $commit, $optimize );
            }
        }
        else
        {
            return $this->Solr->deleteDocs( $docs, false, $commit, $optimize );
        }
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

        $asObjects = isset( $params['AsObjects'] ) ? $params['AsObjects'] : true;

        $coreToUse = null;
        $shardQueryPart = null;
        if ( $this->UseMultiLanguageCores === true )
        {
            $languages = $this->SiteINI->variable( 'RegionalSettings', 'SiteLanguageList' );
            if ( array_key_exists ( $languages[0], $this->SolrLanguageShards ) )
            {
                $coreToUse = $this->SolrLanguageShards[$languages[0]];
                if ( $this->FindINI->variable( 'LanguageSearch', 'SearchMainLanguageOnly' ) <> 'enabled' )
                {
                    $shardQueryPart = array( 'shards' => implode( ',', $this->SolrLanguageShardURIs ) );
                }
            }
            //eZDebug::writeNotice( $languages, __METHOD__ . ' languages' );
            eZDebug::writeNotice( $shardQueryPart, __METHOD__ . ' shards' );
            //eZDebug::writeNotice( $this->SolrLanguageShardURIs, __METHOD__ . ' this languagesharduris' );
        }
        else
        {
            $coreToUse = $this->Solr;
        }


        if ( $this->SiteINI->variable( 'SearchSettings', 'AllowEmptySearch' ) == 'disabled' &&
             trim( $searchText ) == '' )
        {
            $error = 'Empty search is not allowed.';
            eZDebug::writeNotice( $error, __METHOD__ );
            $resultArray = null;
        }

        else
        {
            eZDebug::createAccumulator( 'Query build', 'eZ Find' );
            eZDebug::accumulatorStart( 'Query build' );
            $queryBuilder = new ezfeZPSolrQueryBuilder( $this );
            $queryParams = $queryBuilder->buildSearch( $searchText, $params, $searchTypes );
            if ( !$shardQueryPart == null )
            {
                $queryParams = array_merge( $shardQueryPart, $queryParams );
            }
            eZDebug::accumulatorStop( 'Query build' );
            eZDebug::writeDebug( $queryParams, 'Final query parameters sent to Solr backend' );

            eZDebug::createAccumulator( 'Engine time', 'eZ Find' );
            eZDebug::accumulatorStart( 'Engine time' );
            $resultArray = $coreToUse->rawSearch( $queryParams );
            eZDebug::accumulatorStop( 'Engine time' );
        }

        if ( !$resultArray )
        {
            eZDebug::accumulatorStop( 'Search' );
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => new ezfSearchResultInfo( array( 'error' => ezpI18n::tr( 'ezfind', $error ) ) ) );
        }

        $highLights = array();
        if ( !empty( $resultArray['highlighting'] ) )
        {
            foreach ( $resultArray['highlighting'] as $id => $highlight )
            {
                $highLightStrings = array();
                //implode apparently does not work on associative arrays that contain arrays
                //$element being an array as well
                foreach ( $highlight as $key => $element )
                {
                    $highLightStrings[] = implode( ' ', $element);
                }
                $highLights[$id] = implode( ' ...  ', $highLightStrings);

            }
        }
        if ( !empty( $resultArray ) )
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
                    $localNodeIDList[] = $this->getNodeID( $doc );
                }
            }

            if ( !empty( $localNodeIDList ) )
            {
                $tmpNodeRowList = eZContentObjectTreeNode::fetch( $localNodeIDList, false, false );
                // Workaround for eZContentObjectTreeNode::fetch behaviour
                if ( count( $localNodeIDList ) === 1 )
                {
                    $tmpNodeRowList = array( $tmpNodeRowList );
                }
                if ( $tmpNodeRowList )
                {
                    foreach ( $tmpNodeRowList as $nodeRow )
                    {
                        $nodeRowList[$nodeRow['node_id']] = $nodeRow;
                    }
                }
                unset( $tmpNodeRowList );
            }

            //need refactoring from the moment Solr has globbing in fl parameter
            foreach ( $docs as $idx => $doc )
            {
                if ( !$asObjects )
                {
                    $emit = array();
                    foreach ( $doc as $fieldName => $fieldValue )
                    {
                        list( $prefix, $rest ) = explode( '_', $fieldName, 2 );
                        if ( $prefix === 'meta' )
                        {
                            $emit[$rest] = $fieldValue;
                        }
                        elseif ( $prefix === 'as' )
                        {

                            $emit['data_map'][substr( $rest,0, -4 )] = ezfSolrStorage::unserializeData( $fieldValue );
                        }

                    }
                    $objectRes[] = $emit;
                    unset( $emit );
                    continue;


                }

                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    // Search result document is from current installation
                    $nodeID = $this->getNodeID( $doc );

                    // Invalid $nodeID
                    // This can happen if a content has been deleted while Solr was not running, provoking desynchronization
                    if ( !isset( $nodeRowList[$nodeID] ) )
                    {
                        eZDebug::writeError( "Node #{$nodeID} (/{$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' )]}) returned by Solr cannot be found in the database. Please consider reindexing your content", __METHOD__ );
                        continue;
                    }

                    $resultTree = new eZFindResultNode( $nodeRowList[$nodeID] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$nodeID] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    // can_read permission must be checked as they could be out of sync in Solr, however, when called from template with:
                    // limitation, hash( 'accessWord', ... ) this check should not be performed as it has precedence.
                    // See: http://issues.ez.no/15978
                    if ( !isset( $params['Limitation'], $params['Limitation']['accessWord'] ) && !$resultTree->attribute( 'object' )->attribute( 'can_read' ) )
                    {
                        $searchCount--;
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $nodeID, __METHOD__ );
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
        //mlt does not support distributed search yet, so find out which is
        //the language core to use and qyery only this one
        //search across languages does not make sense here
        $coreToUse = null;
        if ( $this->UseMultiLanguageCores == true )
        {
            $languages = $this->SiteINI->variable( 'RegionalSettings', 'SiteLanguageList' );
            if ( array_key_exists ( $languages[0], $this->SolrLanguageShards ) )
            {
                $coreToUse = $this->SolrLanguageShards[$languages[0]];
            }
        }
        else
        {
            $coreToUse = $this->Solr;
        }

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
            $resultArray = $coreToUse->rawSolrRequest( '/mlt', $queryParams );
            eZDebug::accumulatorStop( 'Engine time' );
        }

        if ( !$resultArray )
        {
            eZDebug::accumulatorStop( 'Search' );
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => new ezfSearchResultInfo( array( 'error' => ezpI18n::tr( 'ezfind', $error ) ) ) );
        }
        $objectRes = array();
        if ( isset( $resultArray['response'] ) && is_array( $resultArray['response'] ) )
        {
            $result = $resultArray['response'];
            $searchCount = $result['numFound'];
            $maxScore = $result['maxScore'];
            $docs = $result['docs'];
            $localNodeIDList = array();
            $nodeRowList = array();

            // Loop through result, and get eZContentObjectTreeNode ID
            foreach ( $docs as $idx => $doc )
            {
                if ( $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' )] == self::installationID() )
                {
                    $localNodeIDList[] = $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )];
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
                    foreach ( $tmpNodeRowList as $nodeRow )
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
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    // can_read permission must be checked as they could be out of sync in Solr, however, when called from template with:
                    // limitation, hash( 'accessWord', ... ) this check should not be performed as it has precedence.
                    // See: http://issues.ez.no/15978
                    if ( !isset( $params['Limitation'], $params['Limitation']['accessWord'] ) && !$resultTree->attribute( 'object' )->attribute( 'can_read' ) )
                    {
                        $searchCount--;
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_node_id' )], __METHOD__ );
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

        if ( $this->UseMultiLanguageCores == true )
        {
            foreach ( $SolrLanguageShards as $shard )
            {
                $shard->rawSearch( array( 'q' => 'solr', 'qt' => 'spellchecker', 'wt' => 'php', 'cmd' => 'rebuild' ) );
                //@TODO: process individual results, implement an ezfind error class or reuse ezp ones
                return true;
            }
        }
        else
        {
            $return = $this->Solr->rawSearch( array( 'q' => 'solr', 'qt' => 'spellchecker', 'wt' => 'php', 'cmd' => 'rebuild' ) );
        }


    }

    /**
     * Experimental: search independent spell check
     * use spellcheck option in search for spellchecking search results
     *
     * @package unfinished
     * @return array Solr result set.
     * @todo: configure different spell check handlers and handle multicore configs (need a parameter for it)
     *
     */
    function spellCheck ( $string, $onlyMorePopular = false, $suggestionCount = 1, $accuracy = 0.5 )
    {
        if ( !$this->UseMultiLanguageCores )
        {
            $onlyMorePopularString = $onlyMorePopular ? 'true' : 'false';
            return $this->Solr->rawSearch( array( 'q' => $string, 'qt' => 'spellchecker',
                                 'suggestionCount' => $suggestionCount, 'wt' => 'php',
                                 'accuracy' => $accuracy, 'onlyMorePopular' => $onlyMorePopularString ) );
        }

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
     * @todo:  handle multicore configs (need a parameter for it) for return values
    **/
    function cleanup( $allInstallations = false, $optimize = false )
    {
        if ( $allInstallations === true )
        {
            $optimize = true;
            $deleteQuery = '*:*';
        }
        else
        {
            $deleteQuery = ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' ) . ':' . self::installationID();
        }
        if ( $this->UseMultiLanguageCores == true )
        {
            foreach ( $this->SolrLanguageShards as $shard )
            {
                $shard->deleteDocs( array(), $deleteQuery, true, $optimize );
            }
            return true;
        }
        else
        {
            return $this->Solr->deleteDocs( array(), $deleteQuery, true );
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
                                                                       'value' => 'value' ),
                                                                'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'publishdate',
                                             'params'  => array( 'value', 'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'subtree',
                                             'params'  => array( array( 'type' => 'array',
                                                                        'value' => 'value' ),
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
        return ezpI18n::tr( 'ezfind', 'eZ Find 2.5 search plugin &copy; 1999-2011 eZ Systems AS, powered by Apache Solr 3.1' );
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
     * Update the section in the search engine
     *
     * @param array $objectID
     * @param int $sectionID
     * @return void
     * @see eZSearch::updateObjectsSection()
     */
    public function updateObjectsSection( array $objectIDs, $sectionID )
    {
        foreach( $objectIDs as $id )
        {
            $object = eZContentObject::fetch( $id );
            // we may be inside a DB transaction running update queries for the
            // section id or the content object may come from the memory cache
            // make sure the section_id is the right one
            $object->setAttribute( 'section_id', $sectionID );
            $this->addObject( $object );
        }
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

    private function initLanguageShards()
    {
        $this->SolrLanguageShards = array();
        if ( $this->UseMultiLanguageCores == true )
        {
            $languageMappingList = $this->FindINI->variable( 'LanguageSearch','LanguagesCoresMap');
            $shardMapping = $this->SolrINI->variable( 'SolrBase', 'Shards' );
            foreach( $languageMappingList as $language => $languageMapping )
            {
                $fullURI = $shardMapping[$languageMapping];
                $this->SolrLanguageShards[$language] = new eZSolrBase($shardMapping[$languageMapping]);
                $parts = explode( '://', $fullURI );
                $this->SolrLanguageShardURIs[$language] = $parts[1];
            }
        }
        else
        //pre-2.2 behaviour
        {
            $this->Solr = new eZSolrBase();
            //$this->SolrLanguageShards[] = new eZSolrBase();
        }


    }


    /**
     * synchronises elevate configuration across language shards in case of
     * multiple lnguage indexes, or the default one
     *
     * @TODO: handle exceptions properly
     */
    public function pushElevateConfiguration()
    {
        if ( $this->UseMultiLanguageCores == true )
        {
            foreach ( $this->SolrLanguageShards as $shard )
            {
                eZFindElevateConfiguration::synchronizeWithSolr( $shard );
            }
            return true;
        }
        else
        {
            return eZFindElevateConfiguration::synchronizeWithSolr( $this->Solr );
        }

    }

    /**
     * eZSolrBase instance used for interaction with the solr server
     * @var eZSolrBase
     */
    var $Solr;
    var $UseMultiLanguageCores;

    /**
    * @since eZ Find2.2, this holds an array of eZSolrBase objects
    * for multilingual indexes served from different shards
    * if this is enabled
    */
    var $SolrLanguageShards;
    var $SolrLanguageShardURIs;
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
    // @since ezfind 2.2, information
    public static $fieldTypeContexts = array( 'search' => 'DatatypeMap', 'facet' => 'DatatypeMapFacet', 'sort' => 'DatatypeMapSort', 'filter' => 'DatatypeMapFilter' );

}

eZSolr::$SolrDocumentFieldName = new ezfSolrDocumentFieldName();

?>

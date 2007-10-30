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
        $this->SearchServerURI = $this->SolrINI->variable( 'SolrBase', 'SearchServerURI' );
        $this->Solr = new eZSolrBase( $this->SearchServerURI );
        $this->SolrDocumentFieldName = new ezfSolrDocumentFieldName();
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
                      'sort_field' => 'string',
                      'sort_order' => 'string' );
    }

    /**
     * Get meta attribute Solr document field type
     *
     * @param string Meta attribute name
     *
     * @return string Solr document field type
     */
    protected function getMetaAttributeType( $name )
    {
        $attributeList = array_merge( array( 'guid' => 'string',
                                             'installation_id' => 'string',
                                             'installation_url' => 'string',
                                             'name' => 'text',
                                             'anon_access' => 'boolean',
                                             'language_code' => 'string',
                                             'main_url_alias' => 'string',
                                             'owner_name' => 'text',
                                             'path' => 'sint' ),
                                      self::metaAttributes(),
                                      self::nodeAttributes() );
        return $attributeList[$name];
    }

    /**
     * Get meta attribute field name
     *
     * @param string Meta attribute field name ( base )
     *
     * @return string Solr doc field name
     */
    protected function getMetaFieldName( $baseName )
    {
        return $this->SolrDocumentFieldName->lookupSchemaName( 'meta_' . $baseName,
                                                               eZSolr::getMetaAttributeType( $baseName ) );
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
        $ini = eZINI::instance();

        // Add all translations to the document list
        $docList = array();

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
            $anonymousUserID = $ini->variable( 'UserSettings', 'AnonymousUserID' );
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

        foreach( $currentVersion->translationList( false, false ) as $languageCode )
        {
            $doc = new eZSolrDoc();
            // Set global unique object ID
            $doc->addField( $this->getMetaFieldName( 'guid' ), $this->guid( $contentObject, $languageCode ) );

            // Set installation identifier
            $doc->addField( $this->getMetaFieldName( 'installation_id' ), $this->installationID() );
            $doc->addField( $this->getMetaFieldName( 'installation_url' ),
                            $this->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $ini->variable( 'SiteSettings', 'SiteURL' ) . '/' );

            // Set Object attributes
            $doc->addField( $this->getMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
            $doc->addField( $this->getMetaFieldName( 'anon_access' ), $anonymousAccess );
            $doc->addField( $this->getMetaFieldName( 'language_code' ), $languageCode );
            $doc->addField( $this->getMetaFieldName( 'owner_name' ),
                            $contentObject->attribute( 'owner' )->name( false, $languageCode ) );

            // Set content object meta attribute values.
            foreach ( $metaAttributeValues as $metaInfo )
            {
                $doc->addField( $this->getMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Set content node meta attribute values.
            foreach ( $nodeAttributeValues as $metaInfo )
            {
                $doc->addField( $this->getMetaFieldName( $metaInfo['name'] ),
                                ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
            }

            // Add main url_alias
            $doc->addField( $this->getMetaFieldName( 'main_url_alias' ), $mainNode->attribute( 'url_alias' ) );

            foreach ( $pathArray as $pathNodeID )
            {
                $doc->addField( $this->getMetaFieldName( 'path' ), $pathNodeID );
            }

            eZContentObject::recursionProtectionStart();

            // Loop through all eZContentObjectAttributes and add them to the Solr document.
            foreach ( $currentVersion->contentObjectAttributes( $languageCode ) as $attribute )
            {
                $metaDataText = '';
                $classAttribute = $attribute->contentClassAttribute();
                if ( $classAttribute->attribute( 'is_searchable' ) == 1 )
                {
                    $documentFieldBase = ezfSolrDocumentFieldBase::getInstance( $attribute );
                    $this->addFieldBaseToDoc( $documentFieldBase, $doc );
                }
            }
            eZContentObject::recursionProtectionEnd();

            $docList[] = $doc;
        }

        $this->Solr->addDocs( $docList, $commit );

        if ( $commit )
        {
            // For every 1000 time, call optimize
            if ( mt_rand( 0, 999 ) == 1 )
            {
                $this->optimize();
            }
        }
    }

    /**
     * Add instance of ezfSolrDocumentFieldBase to Solr document.
     *
     * @param ezfSolrDocumentFieldBase Instance of ezfSolrDocumentFieldBase
     * @param eZSolrDoc Solr document
     */
    function addFieldBaseToDoc( ezfSolrDocumentFieldBase $fieldBase, eZSolrDoc $doc )
    {
        if ( $fieldBase->isCollection() )
        {
            foreach( $fieldBase->getCollectionData() as $collectionBase )
            {
                $this->addFieldBaseToDoc( $collectionBase, $doc );
            }
        }
        else
        {
            foreach( $fieldBase->getData() as $key => $value )
            {
                $doc->addField( $key, $value );
            }
        }
    }

    /*!
     Create policy limitation query.

     \return string Lucene/Solr query string which can be used as filter query for Solr
    */
    function policyLimitationFilterQuery()
    {
        $currentUser = eZUser::currentUser();
        $accessResult = $currentUser->hasAccessTo( 'content', 'read' );

        $filterQuery = false;

        // Add limitations for filter query based on local permissions.
        if ( !in_array( $accessResult['accessWord'], array( 'yes', 'no' ) ) )
        {
            $policies = $accessResult['policies'];

            $limitationHash = array(
                'Class'        => $this->getMetaFieldName( 'contentclass_id' ),
                'Section'      => $this->getMetaFieldName( 'section_id' ),
                'User_Section' => $this->getMetaFieldName( 'section_id' ),
                'Subtree'      => $this->getMetaFieldName( 'path_string' ),
                'User_Subtree' => $this->getMetaFieldName( 'path_string' ),
                'Node'         => $this->getMetaFieldName( 'main_node_id' ),
                'Owner'        => $this->getMetaFieldName( 'owner_id' ) );

            $filterQueryPolicies = array();

            // policies are concatenated with OR
            foreach ( $policies as $limitationList )
            {
                // policy limitations are concatenated with AND
                $filterQueryPolicyLimitations = array();

                foreach ( $limitationList as $limitationType => $limitationValues )
                {
                    // limitation values of one type in a policy are concatenated with OR
                    $filterQueryPolicyLimitationParts = array();

                    switch ( $limitationType )
                    {
                        case 'User_Subtree':
                        case 'Subtree':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $pathString = trim( $limitationValue, '/' );
                                $pathArray = explode( '/', $pathString );
                                // we only take the last node ID in the path identification string
                                $subtreeNodeID = array_shift( $pathArray );
                                $filterQueryPolicyLimitationParts[] = $this->getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;
                            }
                        } break;

                        case 'Node':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $pathString = trim( $limitationValue, '/' );
                                $pathArray = explode( '/', $pathString );
                                // we only take the last node ID in the path identification string
                                $nodeID = array_shift( $pathArray );
                                $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $nodeID;
                            }
                        } break;

                        case 'Group':
                        {
                            // Not supported
                        } break;

                        case 'Owner':
                        {
                            $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $currentUser->attribute ( 'contentobject_id' );
                        } break;

                        case 'Class':
                        case 'Section':
                        case 'User_Section':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $limitationValue;
                            }
                        } break;

                        default :
                        {
                            eZDebug::writeDebug( $limitationType, 'eZSolr::policyLimitationFilterQuery unknown limitation type: ' . $limitationType );
                            continue;
                        }
                    }

                    $filterQueryPolicyLimitations[] = '( ' . implode( ' OR ', $filterQueryPolicyLimitationParts ) . ' )';
                }

                if ( count( $filterQueryPolicyLimitations ) > 0 )
                {
                    $filterQueryPolicies[] = '( ' . implode( ' AND ', $filterQueryPolicyLimitations ) . ')';
                }
            }

            if ( count( $filterQueryPolicies ) > 0 )
            {
                $filterQuery = implode( ' OR ', $filterQueryPolicies );
            }
        }

        // Add limitations for allowing search of other installations.
        $anonymousPart = '';
        if ( $this->FindINI->variable( 'SiteSettings', 'SearchOtherInstallations' ) == 'enabled' )
        {
            $anonymousPart = ' OR ' . $this->getMetaFieldName( 'anon_access' ) . ':true ';
        }

        if ( !empty( $filterQuery ) )
        {
            $filterQuery = '( ( ' . $this->getMetaFieldName( 'installation_id' ) . ':' . $this->installationID() . ' AND ' . $filterQuery . ' ) ' . $anonymousPart . ' )';
        }
        else
        {
            $filterQuery = '( ' . $this->getMetaFieldName( 'installation_id' ) . ':' . $this->installationID() . $anonymousPart . ' )';
        }

        // Add limitations based on allowed languages.
        $ini = eZINI::instance();
        if ( $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) )
        {
            $filterQuery = '( ' . $filterQuery . ' AND ( ' . $this->getMetaFieldName( 'language_code' ) . ':' .
                implode( ' OR ' . $this->getMetaFieldName( 'language_code' ) . ':', $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) ) . ' ) )';
        }

        eZDebug::writeDebug( $filterQuery, 'eZSolr::policyLimitationFilterQuery' );

        return $filterQuery;
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

    /*!
     \brief Removes an object from the Solr search server
    */
    function removeObject( $contentObject )
    {
        $this->Solr->deleteDocs( array(),
                                 $this->getMetaFieldName( 'id' ) . ':' . $contentObject->attribute( 'id' ) . ' AND '.
                                 $this->getMetaFieldName( 'installation_id' ) . ':' . $this->installationID() );
    }

    /**
     * Get an array of class attribute identifiers based on a list of class ids, prepended with attr_
     *
     * @param array $classIDArray ( if set to false, fetch for all existing classes )
     * @param array $classAttributeID ( if set to false, fetch for all )
     * @param array $fieldTypeExcludeList filter list. List of field types to exclude. ( set to empty array by default ).
     *
     * @return array List of field names.
     */
    function getClassAttributes( $classIDArray = false,
                                 $classAttributeIDArray = false,
                                 $fieldTypeExcludeList = null )
    {
        eZDebug::createAccumulator( 'solr_classattribute_list', 'solr' );
        eZDebug::accumulatorStart( 'solr_classattribute_list' );
        $fieldArray = array();

        $classAttributeArray = array();

        if ( is_numeric( $classAttributeIDArray ) and $classAttributeIDArray > 0 )
        {
            $classAttributeArray[] = eZContentClassAttribute::fetch( $classAttributeIDArray );
        }
        else if ( is_array( $classAttributeIDArray ) )
        {
            foreach( $classAttributeIDArray as $classAttributeID )
            {
                $classAttributeArray[] = eZContentClassAttribute::fetch( $classAttributeID );
            }
        }

        if ( !empty( $classAttributeArray ) )
        {
            foreach( $classAttributeArray as $classAttribute )
            {
                if ( empty( $fieldTypeExcludeList ) ||
                     !in_array( ezfSolrDocumentFieldBase::getClassAttributeType( $classAttribute ),
                                $fieldTypeExcludeList ) )
                {
                    $fieldArray[] = ezfSolrDocumentFieldBase::getFieldName( $classAttribute );
                }
            }
        }
        else
        {
            // Fetch class list.
            if ( is_numeric( $classIDArray ) and $classIDArray > 0 )
            {
                $classIDArray = array( $classIDArray );
            }
            else if ( !is_array( $classIDArray ) )
            {
                $classIDArray = false;
            }

            $condArray = array( "is_searchable" => 1,
                                "version" => eZContentClass::VERSION_STATUS_DEFINED );
            if ( is_array( $classIDArray ) )
            {
                $condArray['contentclass_id'] = array( $classIDArray );
            }
            foreach( eZContentClassAttribute::fetchFilteredList( $condArray ) as $classAttribute )
            {
                if ( empty( $fieldTypeExcludeList ) ||
                     !in_array( ezfSolrDocumentFieldBase::getClassAttributeType( $classAttribute ),
                                $fieldTypeExcludeList ) )
                {
                    $fieldArray[] = ezfSolrDocumentFieldBase::getFieldName( $classAttribute );
                }
            }
        }
        //eZDebug::writeDebug( $fieldArray, ' Field array ' );
        eZDebug::accumulatorStop( 'solr_classattribute_list' );
        return $fieldArray;
    }


    /*!
     \brief Search on the Solr search server

     \param string search term
     \param array parameters.
            Example: array( 'SearchOffset' => <offset>,
                            'SearchLimit' => <limit>,
                            'SearchSubTreeArray' => array( <node ID1>[, <node ID2>]... ),
                            'SearchContentClassID' => array( <class ID1>[, <class ID2>]... ),
                            'SearchContentClassAttributeID' => <class attribute ID> )
     \param array search types. Reserved.

     \return array List of eZFindResultNode objects.
    */
    function search( $searchText, $params = array(), $searchTypes = array() )
    {
        eZDebug::writeDebug( $params, 'search params' );
        $searchCount = 0;

        $offset = ( isset( $params['SearchOffset'] ) && $params['SearchOffset'] ) ? $params['SearchOffset'] : 0;
        $limit = ( isset( $params['SearchLimit']  ) && $params['SearchLimit'] ) ? $params['SearchLimit'] : 20;
        $subtrees = isset( $params['SearchSubTreeArray'] ) ? $params['SearchSubTreeArray'] : array();
        $contentClassID = ( isset( $params['SearchContentClassID'] ) && $params['SearchContentClassID'] <> -1 ) ? $params['SearchContentClassID'] : false;
        $contentClassAttributeID = ( isset( $params['SearchContentClassAttributeID'] ) && $params['SearchContentClassAttributeID'] <> -1 ) ? $params['SearchContentClassAttributeID'] : false;
        $sectionID = isset( $params['SearchSectionID'] ) && $params['SearchSectionID'] > 0 ? $params['SearchSectionID'] : false;
        $filterQuery = array();
        //FacetFields and FacetQueries not used yet! Need to add it to the module as well

        if ( count( $subtrees ) > 0 )
        {
            $subtreeQueryParts = array();
            foreach ( $subtrees as $subtreeNodeID )
            {
                $subtreeQueryParts[] = $this->getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;
            }

            $filterQuery[] = implode( ' OR ', $subtreeQueryParts );
        }

        $policyLimitationFilterQuery = $this->policyLimitationFilterQuery();

        if ( $policyLimitationFilterQuery !== false )
        {
            $filterQuery[] = $policyLimitationFilterQuery;
        }

        if ( $contentClassID )
        {
            if ( is_array( $contentClassID ) )
            {
                $classQueryParts = array();
                foreach ( $contentClassID as $classID )
                {
                    if ( is_int( (int)$classID ) and  (int)$classID != 0 )
                    {
                        $classQueryParts[] = $this->getMetaFieldName( 'contentclass_id' ) . ':' . $classID;
                    }
                    elseif ( is_string( $classID ) and  $classID != "" )
                    {
                        $class = eZContentClass::fetchByIdentifier( $classID );
                        if ( $class )
                        {
                            $classID = $class->attribute( 'id' );
                            $classQueryParts[] = $this->getMetaFieldName( 'contentclass_id' ) . ':' . $classID;
                        }
                        else
                        {
                            eZDebug::writeError( 'No valid content class', 'eZSolr::search' );
                        }
                    }
                    else
                    {
                        eZDebug::writeError( 'No valid content class', 'eZSolr::search' );
                    }
                }
                $filterQuery[] = implode( ' OR ', $classQueryParts );
            }
            elseif ( is_int( (int)$contentClassID ) and  (int)$contentClassID != 0 )
            {
                $filterQuery[] = $this->getMetaFieldName( 'contentclass_id' ) . ':' . $contentClassID;
            }
            elseif ( is_string( $contentClassID ) and  $contentClassID != "" )
            {
                $class = eZContentClass::fetchByIdentifier( $contentClassID );
                if ( $class )
                {
                    $contentClassID = $class->attribute( 'id' );
                    $filterQuery[] = $this->getMetaFieldName( 'contentclass_id' ) . ':' . $contentClassID;
                }
                else
                {
                    eZDebug::writeError( 'No valid content class', 'eZSolr::search' );
                }
            }
            else
            {
                eZDebug::writeError( 'No valid content class', 'eZSolr::search' );
            }
        }

        if ( $sectionID )
        {
            $filterQuery[] = $this->getMetaFieldName( 'section_id' ) . ':' . $sectionID;
        }

        // Pre-process search text, and see if some field types must be excluded from the search.
        $fieldTypeExcludeList = $this->fieldTypeExludeList( $searchText );

        //the array_unique below is necessary because attribute identifiers are not unique .. and we get as
        //much highlight snippets as there are duplicate attribute identifiers
        //these are also in the list of query fields (dismax, ezpublish) request handlers
        $queryFields = array_unique( $this->getClassAttributes( $contentClassID, $contentClassAttributeID, $fieldTypeExcludeList ) );

        //$queryFields = array ('df_attr');
        //highlighting only in the attributes, otherwise the object name is repeated in the highlight, which is already
        //partly true as it is mostly composed of one or more attributes.
        //maybe we should add meta data to the index to filter them out.

        $highLightFields = $queryFields;
        $queryFields[] = $this->getMetaFieldName( 'name' ) . '^2.0';
        $queryFields[] = $this->getMetaFieldName( 'owner_name' ) . '^1.5';
        $queryParams = array(
            'start' => $offset,
            'rows' => $limit,
            'indent' => 'on',
            'version' => '2.2',
            'qt' => 'ezpublish',
            'qf' => implode( ' ', $queryFields ),
            'bq' => $this->boostQuery(),
            'fl' =>
            $this->getMetaFieldName( 'guid' ) . ' ' . $this->getMetaFieldName( 'installation_id' ) . ' ' .
            $this->getMetaFieldName( 'main_url_alias' ) . ' ' . $this->getMetaFieldName( 'installation_url' ) . ' ' .
            $this->getMetaFieldName( 'id' ) . ' ' . $this->getMetaFieldName( 'main_node_id' ) . ' ' .
            $this->getMetaFieldName( 'language_code' ) . ' ' . $this->getMetaFieldName( 'name' ) .
            ' score ' . $this->getMetaFieldName( 'published' ),
            'q' => $searchText,
            'fq' => $filterQuery,
            'hl' => 'true',
            'hl.fl' => $highLightFields,
            'hl.snippets' => 2,
            'hl.fragsize' => 100,
            'hl.requireFieldMatch' => 'true',
            'hl.simple.pre' => '<b>',
            'hl.simple.post' => '</b>',
            'wt' => 'php'
            );

        eZDebug::writeDebug( $queryParams );
        $resultArray = $this->Solr->rawSearch( $queryParams );

        if (! $resultArray )
        {
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => array(
                    'DocExtras' => array(),
//                   'FacetArray' => $resultArray['facet_counts'],
                    'ResponseHeader' => $resultArray['responseHeader'],
                    'Error' => ezi18n( 'ezfind', 'Server not running' ),
                    'Engine' => $this->engineText() ) );
        }

        $highLights = array();
        if ( !empty( $resultArray['highlighting'] ) )
        {
            foreach ( $resultArray['highlighting'] as $id => $highlight)
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
                if ( $doc[$this->getMetaFieldName( 'installation_id' )] == $this->installationID() )
                {
                    $localNodeIDList[] = $doc[$this->getMetaFieldName( 'main_node_id' )][0];
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
                if ( $doc[$this->getMetaFieldName( 'installation_id' )] == $this->installationID() )
                {
                    // Search result document is from current installation
                    $resultTree = new eZFindResultNode( $nodeRowList[$doc[$this->getMetaFieldName( 'main_node_id' )][0]] );
                    $resultTree->setContentObject( new eZContentObject( $nodeRowList[$doc[$this->getMetaFieldName( 'main_node_id' )][0]] ) );
                    $resultTree->setAttribute( 'is_local_installation', true );
                    if ( !$resultTree->attribute( 'can_read' ) )
                    {
                        eZDebug::writeNotice( 'Access denied for eZ Find result, node_id: ' . $doc[$this->getMetaFieldName( 'main_node_id' )],
                                              'eZSolr::search()' );
                        continue;
                    }


                    $globalURL = $doc[$this->getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[$this->getMetaFieldName( 'language_code' )];
                    eZURI::transformURI( $globalURL );
                }
                else
                {
                    $resultTree = new eZFindResultNode();
                    $resultTree->setAttribute( 'is_local_installation', false );
                    $globalURL = $doc[$this->getMetaFieldName( 'installation_url' )] .
                        $doc[$this->getMetaFieldName( 'main_url_alias' )] .
                        '/(language)/' . $doc[$this->getMetaFieldName( 'language_code' )];
                }

                $resultTree->setAttribute( 'name', $doc[$this->getMetaFieldName( 'name' )] );
                $resultTree->setAttribute( 'published', $doc[$this->getMetaFieldName( 'published' )] );
                $resultTree->setAttribute( 'global_url_alias', $globalURL );
                $resultTree->setAttribute( 'highlight', isset( $highLights[$doc[$this->getMetaFieldName( 'guid' )]] ) ?
                                           $highLights[$doc[$this->getMetaFieldName( 'guid' )]] : null );
                $resultTree->setAttribute( 'score_percent', (int) ( ( $doc['score'] / $maxScore ) * 100 ) );
                $resultTree->setAttribute( 'language_code', $doc[$this->getMetaFieldName( 'language_code' )] );
                $objectRes[] = $resultTree;
            }
        }

        $stopWordArray = array();
        eZDebug::writeDebug( isset( $resultArray['highlighting'] ) ? $resultArray['highlighting'] : 'No hightlights returned', ' Highlights ' );
//      eZDebug::writeDebug( $resultArray['facet_counts'], ' Facets ' );


        //rewrite highlight array into one string

        eZDebug::writeDebug( $highLights, ' Highlight massage ' );
        return array(
            'SearchResult' => $objectRes,
            'SearchCount' => $searchCount,
            'StopWordArray' => $stopWordArray,
            'SearchExtras' => array(
//                   'FacetArray' => $resultArray['facet_counts'],
                'ResponseHeader' => $resultArray['responseHeader'],
                'Error' => '',
                'Engine' => $this->engineText() )
            );
    }

    /**
     * Check if search string requires certain field types to be excluded from the search
     *
     * @param string Search string
     *
     * @return array List of field types to exclude from the search
     */
    protected function fieldTypeExludeList( $searchText )
    {
        $excludeFieldList = array();
        // Check if search text is a date.
        if ( strtotime( $searchText ) === false )
        {
            $excludeFieldList[] = 'date';
        }
        if  ( strtolower( $searchText ) !== 'true' &&
              strtolower( $searchText ) !== 'false' )
        {
            $excludeFieldList[] = 'boolean';
        }

        return $excludeFieldList;
    }

    /*!
     Generate boost query on search. This boost is configured boost the following criterias:
     - local installation
     - Language priority

     \return boostQuery
    */
    function boostQuery()
    {
        // Local installation boost
        $boostQuery = $this->getMetaFieldName( 'installation_id' ) . ':' . $this->installationID() . '^1.5';
        $ini = eZINI::instance();

        // Language boost. Only boost 3 first languages.
        $languageBoostList = array( '1.2', '1.0', '0.8' );
        foreach ( $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) as $idx => $languageCode )
        {
            if ( empty( $languageBoostList[$idx] ) )
            {
                break;
            }
            $boostQuery .= ' ' . $this->getMetaFieldName( 'language_code' ) . ':' . $languageCode . '^' . $languageBoostList[$idx];
        }

        return $boostQuery;
    }


    /*!
     Experimental
    */
    function supportedSearchTypes()
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


/*
    function initSpellChecker()
    {

	$return = $this->Solr->rawSearch( array( 'q' => 'solr', 'qt' => 'spellchecker', 'wt' => 'php', 'cmd' => 'rebuild') );

    }



    function spellCheck ( $string, $onlyMorePopular = false, $suggestionCount = 1, $accuracy=0.5 )
    {
	$onlyMorePopularString = $onlyMorePopular ? 'true' : 'false';
	return $this->Solr->rawSearch( '/select', array( 'q' => $string, 'qt' => 'spellchecker',
						 'suggestionCount' => $suggestionCount, 'wt' => 'php',
						 'accuracy' => $accuracy, 'onlyMorePopular' => $onlyMorePopularString ) );

    }


    function moreLikeThis ( $objectID )
    {
	return $this->rawSolrRequest ( '/mlt' , array( 'q' => 'm_id:' . $objectID, 'wt' => 'php', 'fl' => 'm_id' ) );
    }
*/

    /*!
     Get eZFind installation ID

     \return installaiton ID.
     */
    function installationID()
    {
        if ( !empty( $this->InstallationID ) )
        {
            return $this->InstallationID;
        }
        $db = eZDB::instance();

        $resultSet = $db->arrayQuery( 'SELECT value FROM ezsite_data WHERE name=\'ezfind_site_id\'' );

        if ( count( $resultSet ) >= 1 )
        {
            $this->InstallationID = $resultSet[0]['value'];
        }
        else
        {
            $this->InstallationID = md5( time() . '-' . mt_rand() );
            $db->query( 'INSERT INTO ezsite_data ( name, value ) values( \'ezfind_site_id\', \'' . $this->InstallationID . '\' )' );
        }

        return $this->InstallationID;
    }

    /*!
     Get GlobalID of contentobject

     \param \a $contentObject
     \param \a $languageCode ( optional )

     \return guid
    */
    function guid( $contentObject, $languageCode = '' )
    {
        return md5( $this->installationID() . '-' . $contentObject->attribute( 'id' ) . '-' . $languageCode );
    }

    /*!
     Clean up search index for current installation.
    */
    function cleanup()
    {
        $this->Solr->deleteDocs( array(), $this->getMetaFieldName( 'installation_id' ) . ':' . $this->installationID(), true );
    }

    /*!
     Get engine text

     \return engine text
    */
    function engineText()
    {
        return ezi18n( 'ezfind', 'eZ Find search plugin &copy; 2007 eZ Systems AS, eZ Labs' );
    }

    /// Object vars
    var $InstallationID;
    var $SolrINI;
    var $SearchSeverURI;
    var $FindINI;
    var $SolrDocumentFieldName;
    var $SolrDocumentFieldBase;
}

?>

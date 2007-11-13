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
            $doc->addField( self::getMetaFieldName( 'guid' ), $this->guid( $contentObject, $languageCode ) );

            // Set installation identifier
            $doc->addField( self::getMetaFieldName( 'installation_id' ), self::installationID() );
            $doc->addField( self::getMetaFieldName( 'installation_url' ),
                            $this->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $ini->variable( 'SiteSettings', 'SiteURL' ) . '/' );

            // Set Object attributes
            $doc->addField( self::getMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
            $doc->addField( self::getMetaFieldName( 'anon_access' ), $anonymousAccess );
            $doc->addField( self::getMetaFieldName( 'language_code' ), $languageCode );
            $doc->addField( self::getMetaFieldName( 'owner_name' ),
                            $contentObject->attribute( 'owner' )->name( false, $languageCode ) );

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
    function removeObject( $contentObject )
    {
        $this->Solr->deleteDocs( array(),
                                 self::getMetaFieldName( 'id' ) . ':' . $contentObject->attribute( 'id' ) . ' AND '.
                                 self::getMetaFieldName( 'installation_id' ) . ':' . self::installationID() );
    }

    /**
     * Search on the Solr search server
     *
     * @param string search term
     * @param array parameters. @see ezfeZPSolrQueryBuilder::buildSearch()
     * @param array search types. Reserved.
     *
     * @return array List of eZFindResultNode objects.
     */
    function search( $searchText, $params = array(), $searchTypes = array() )
    {
        eZDebug::createAccumulator( 'Search', 'eZ Find' );
        eZDebug::accumulatorStart( 'Search' );

        eZDebug::createAccumulator( 'Query build', 'eZ Find' );
        eZDebug::accumulatorStart( 'Query build' );
        $queryBuilder = new ezfeZPSolrQueryBuilder();
        $queryParams = $queryBuilder->buildSearch( $searchText, $params, $searchTypes );
        eZDebug::accumulatorStop( 'Query build' );

        eZDebug::createAccumulator( 'Engine time', 'eZ Find' );
        eZDebug::accumulatorStart( 'Engine time' );
        $resultArray = $this->Solr->rawSearch( $queryParams );
        eZDebug::accumulatorStop( 'Engine time' );

        if (! $resultArray )
        {
            eZDebug::accumulatorStop( 'Search' );
            return array(
                'SearchResult' => false,
                'SearchCount' => 0,
                'StopWordArray' => array(),
                'SearchExtras' => new ezfSearchResultInfo( array( 'error' => ezi18n( 'ezfind', 'Server not running' ) ) ) );
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
        return ezi18n( 'ezfind', 'eZ Find search plugin &copy; 2007 eZ Systems AS' );
    }

    /// Object vars
    var $SolrINI;
    var $SearchSeverURI;
    var $FindINI;
    var $SolrDocumentFieldBase;

    static $InstallationID;
    static $SolrDocumentFieldName;
}

eZSolr::$SolrDocumentFieldName = new ezfSolrDocumentFieldName();

?>

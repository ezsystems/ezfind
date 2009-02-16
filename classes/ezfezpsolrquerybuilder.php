<?php
//
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

/*! \file ezfezpsolrquerybuilder.php
*/

/*!
  \class ezfeZPSolrQueryBuilder ezfezpsolrquerybuilder.php
  \brief The class ezfeZPSolrQueryBuilder does

*/

class ezfeZPSolrQueryBuilder
{
    /**
     * Constructor
     *
     * Sets variables for creating a new instance of ezfeZPSolrQueryBuilder
     */
    function ezfeZPSolrQueryBuilder()
    {
    }


    /**
     * @since eZ Find 2.0
     * build a multi field query, basically doing the same as a Lucene MultiField query
     * not always safe
     * @param string $searchText
     * @param array $solrFields
     * @param string $mode
     *
     */
    public function buildMultiFieldQuery( $searchText, $solrFields = array(), $mode = null )
    {
        // simple implode implying an OR functionality, $mode is ignored
        $multiFieldQuery = '';
        foreach ($solrFields as $field)
        {
            //don't mind the last extra space, it's ignored by Solr
            $multiFieldQuery .= $field . ':(' . $searchText . ') ';
        }
        return $multiFieldQuery;


    }


    /**
     * Search on the Solr search server
     *
     * @param string search term
     * @param array parameters.
     *      Example:
     * <code>
     * array( 'SearchOffset' => <offset>,
     *        'SearchLimit' => <limit>,
     *        'SearchSubTreeArray' => array( <node ID1>[, <node ID2>]... ),
     *        'SearchContentClassID' => array( <class ID1>[, <class ID2>]... ),
     *        'SearchContentClassAttributeID' => <class attribute ID>,
     *        'Facet' => array( array( 'field' => <class identifier>/<attribute identifier>[/<option>], ... ) ) ),
     *        'Filter' => array( <base_name> => <value>, <base_name2> => <value2> )
     *        'SortBy' => array( <field> => <asc|desc> [, <field2> => <asc|desc> [,...]] ) |
                          array( array( <field> => <asc|desc> )[, array( <field2> => <asc|desc> )[,...]] )
     * </code>
     * For full facet description, see facets design document.
     * @param array Search types. Reserved.
     *
     * @return array Solr query results.
     *
     * @todo implement case $asObjects == false
     * @todo add a field list parameter for use if $asObjects == false

     */
    public function buildSearch( $searchText, $params = array(), $searchTypes = array() )
    {
        eZDebug::writeDebug( $params, 'search params' );
        $searchCount = 0;

        $offset = ( isset( $params['SearchOffset'] ) && $params['SearchOffset'] ) ? $params['SearchOffset'] : 0;
        $limit = ( isset( $params['SearchLimit']  ) && $params['SearchLimit'] ) ? $params['SearchLimit'] : 10;
        $subtrees = isset( $params['SearchSubTreeArray'] ) ? $params['SearchSubTreeArray'] : array();
        $contentClassID = ( isset( $params['SearchContentClassID'] ) && $params['SearchContentClassID'] <> -1 ) ? $params['SearchContentClassID'] : false;
        $contentClassAttributeID = ( isset( $params['SearchContentClassAttributeID'] ) && $params['SearchContentClassAttributeID'] <> -1 ) ? $params['SearchContentClassAttributeID'] : false;
        $sectionID = isset( $params['SearchSectionID'] ) && $params['SearchSectionID'] > 0 ? $params['SearchSectionID'] : false;
        $asObjects = isset( $params['AsObjects'] ) && $params['AsObjects'] ? $params['AsObjects'] : true;
        $spellCheck = isset( $params['SpellCheck'] ) && $params['SpellCheck'] > 0 ? $params['SpellCheck'] : array();
        $queryHandler = isset( $params['QueryHandler'] )  ?  $params['QueryHandler'] : self::$FindINI->variable( 'SearchHandler', 'DefaultSearchHandler' );
        $ignoreVisibility = isset( $params['IgnoreVisibility'] )  ?  $params['IgnoreVisibility'] : false;
        $limitation = isset( $params['Limitation'] )  ?  $params['Limitation'] : null;

        // check if filter parameter is indeed an array, and set it otherwise
        if ( isset( $params['Filter']) && ! is_array( $params['Filter'] ) )
        {
            $params['Filter'] = array( $params['Filter'] );
        }



        $filterQuery = array();

        // Add subtree query filter
        if ( count( $subtrees ) > 0 )
        {
            $subtreeQueryParts = array();
            foreach ( $subtrees as $subtreeNodeID )
            {
                $subtreeQueryParts[] = eZSolr::getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;
            }

            $filterQuery[] = implode( ' OR ', $subtreeQueryParts );
        }

        // Add policy limitation query filter
        $policyLimitationFilterQuery = $this->policyLimitationFilterQuery( $limitation, $ignoreVisibility );
        if ( $policyLimitationFilterQuery !== false )
        {
            $filterQuery[] = $policyLimitationFilterQuery;
        }



        if ( (!eZContentObjectTreeNode::showInvisibleNodes() || !$ignoreVisibility ) && (self::$FindINI->variable( 'SearchFilters', 'FilterHiddenFromDB' ) == 'enabled') )
        {
            $db =& eZDB::instance();
            $invisibleNodeIDArray = $db->arrayQuery( 'SELECT node_id FROM ezcontentobject_tree WHERE ezcontentobject_tree.is_invisible = 1', array( 'column' => 0) );
            $hiddenNodesQueryText = 'meta_main_node_id_si:[* TO *] -meta_main_node_id_si:(';
            foreach ( $invisibleNodeIDArray as $element )
            {
                $hiddenNodesQueryText =  $hiddenNodesQueryText . $element['node_id'] . ' ';
            }
            $hiddenNodesQueryText = $hiddenNodesQueryText . ')';
            $filterQuery[] = $hiddenNodesQueryText;

        }

        // Add content class query filter
        $classLimitationFilter = $this->getContentClassFilterQuery( $contentClassID );
        if ( $classLimitationFilter !== null )
        {
            $filterQuery[] = $classLimitationFilter;
        }

        // Add section to query filter.
        if ( $sectionID )
        {
            $filterQuery[] = eZSolr::getMetaFieldName( 'section_id' ) . ':' . $sectionID;
        }

        // Add Filter from function parameters
        // but add the sitelanguage first (only current language is searched)
        // maybe we'll make this configurable later on
        $ini = eZINI::instance();
        // TODO - check ini settings wether or not to search main language only
        $languages = $ini->variable( 'RegionalSettings', 'SiteLanguageList' );
        $mainLanguage = $languages[0];
        $params['Filter']['language_code'] =  $mainLanguage;
        $paramFilterQuery = $this->getParamFilterQuery( $params );
        if ( $paramFilterQuery )
        {
            $filterQuery[] = $paramFilterQuery;
        }

        //add raw filters
        $rawFilters = self::$FindINI->variable( 'SearchFilters', 'RawFilterList' );
        if ( is_array( $rawFilters ) )
        {
            $filterQuery = array_merge( $filterQuery, $rawFilters );
        }

        // Build and get facet query prameters.
        $facetQueryParamList = $this->buildFacetQueryParamList( $params );

        // search only text type declared fields
        $fieldTypeExcludeList = $this->fieldTypeExludeList( NULL );

        // Create sort parameters based on the parameters.
        $sortParameter = $this->buildSortParameter( $params );

        //the array_unique below is necessary because attribute identifiers are not unique .. and we get as
        //much highlight snippets as there are duplicate attribute identifiers
        //these are also in the list of query fields (dismax, ezpublish) request handlers
        $queryFields = array_unique( $this->getClassAttributes( $contentClassID, $contentClassAttributeID, $fieldTypeExcludeList ) );

        //highlighting only in the attributes, otherwise the object name is repeated in the highlight, which is already
        //partly true as it is mostly composed of one or more attributes.
        //maybe we should add meta data to the index to filter them out.

        $highLightFields = $queryFields;
        $queryFields[] = eZSolr::getMetaFieldName( 'name' );
        $queryFields[] = eZSolr::getMetaFieldName( 'owner_name' );

        $spellCheckParamList = array();
        // @param $spellCheck expects array (true|false, dictionary identifier, ...)
        if ( ( isset( $spellCheck[0] ) and $spellCheck[0] ) or self::$FindINI->variable( 'SpellCheck', 'SpellCheck' ) == 'enabled' )
        {
            $dictionary = isset( $spellCheck[1]) ? $spellCheck[1] : self::$FindINI->variable( 'SpellCheck', 'DefaultDictionary' );
            $spellCheckParamList = array(
                'spellcheck' => 'true',
                // q is manipulated in case of standard request handler, so make it explicit by using spellcheck.q
                'spellcheck.q' => $searchText,
                'spellcheck.dictionary' => $dictionary,
                'spellcheck.collate' => 'true',
                'spellcheck.extendedResults' => 'true',
                'spellcheck.onlyMorePopular' => 'true',
                'spellcheck.count' => 1);
        }


        // process query handler: standard, simplestandard, ezpublish, heuristic
        // first determine which implemented handler to use when heuristic is specified
        if ( strtolower( $queryHandler ) === 'heuristic' )
        {
            // @todo: this code will evolve of course
            if ( preg_match('/[\^\*\~]|AND|OR/', $searchText) > 0 )
            {
                $queryHandler = 'simplestandard';
            }
            else
            {
                $queryHandler = 'ezpublish';
            }
        }

        $handlerParameters = array();

        $queryHandler = strtolower( $queryHandler );

        switch ( $queryHandler )
        {
            case 'standard':
                // @todo: this is more complicated
                // build the query against all "text" like fields
                // should take into account all the filter fields and class filters to shorten the query
                // need to build: Solr q
                $handlerParameters = array ( 'q' => $this->buildMultiFieldQuery( $searchText, $queryFields ),
                                             'qt' => 'standard');
                break;

            case 'simplestandard':
                // not to do much, searching is against the default aggregated field
                $handlerParameters = array ( 'q' => $searchText,
                                             'qt' => 'standard');
                break;
            case 'ezpublish':
                // the dismax based handler, just keywordss input, most useful for ordinary queries by users
                // need to build: Solr q, qf, dismax specific parameters

            default:
                // ezpublish of course, this to not break BC and is the most "general"
                // if another value is specified, it is supposed to be a dismax like handler
                // with possible other tuning variables then the stock provided 'ezpublish' in solrconfi.xml
                // remark it should be lowercase in solrconfig.xml!
                $handlerParameters = array ( 'q' => $searchText,
                                             'qf' => implode( ' ', $queryFields ),
                                             'qt' => $queryHandler );

        }

        $queryParams =  array_merge(
            $handlerParameters,
            array(
                'start' => $offset,
                'rows' => $limit,
                'sort' => $sortParameter,
                'indent' => 'on',
                'version' => '2.2',
                'bq' => $this->boostQuery(),
                'fl' =>
                eZSolr::getMetaFieldName( 'guid' ) . ' ' . eZSolr::getMetaFieldName( 'installation_id' ) . ' ' .
                eZSolr::getMetaFieldName( 'main_url_alias' ) . ' ' . eZSolr::getMetaFieldName( 'installation_url' ) . ' ' .
                eZSolr::getMetaFieldName( 'id' ) . ' ' . eZSolr::getMetaFieldName( 'main_node_id' ) . ' ' .
                eZSolr::getMetaFieldName( 'language_code' ) . ' ' . eZSolr::getMetaFieldName( 'name' ) .
                ' score ' . eZSolr::getMetaFieldName( 'published' ),
                'fq' => $filterQuery,
                'hl' => 'true',
                'hl.fl' => $highLightFields,
                'hl.snippets' => 2,
                'hl.fragsize' => 100,
                'hl.requireFieldMatch' => 'false',
                'hl.simple.pre' => '<b>',
                'hl.simple.post' => '</b>',
                'wt' => 'php' ),
            $facetQueryParamList,
            $spellCheckParamList );
        eZDebug::writeDebug( $queryParams, 'Final query parameters sent to Solr backend' );
        return $queryParams;
    }

    /**
     * @since eZ Find 2.0
     *
     * More Like This similarity searches
     * @param query
     *
     * @return
     */
    public function buildMoreLikeThis( $queryType, $query, $params = array() )
    {
        eZDebug::writeDebug( $queryType, 'mlt querytype' );
        eZDebug::writeDebug( $query, 'mlt query' );
        eZDebug::writeDebug( $params, 'mlt params' );
        $searchCount = 0;

        $offset = ( isset( $params['SearchOffset'] ) && $params['SearchOffset'] ) ? $params['SearchOffset'] : 0;
        $limit = ( isset( $params['SearchLimit']  ) && $params['SearchLimit'] ) ? $params['SearchLimit'] : 10;
        $subtrees = isset( $params['SearchSubTreeArray'] ) ? $params['SearchSubTreeArray'] : array();
        $contentClassID = ( isset( $params['SearchContentClassID'] ) && $params['SearchContentClassID'] <> -1 ) ? $params['SearchContentClassID'] : false;
        $sectionID = isset( $params['SearchSectionID'] ) && $params['SearchSectionID'] > 0 ? $params['SearchSectionID'] : false;
        $filterQuery = array();


        // Add subtree query filter
        if ( count( $subtrees ) > 0 )
        {
            $subtreeQueryParts = array();
            foreach ( $subtrees as $subtreeNodeID )
            {
                $subtreeQueryParts[] = eZSolr::getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;
            }

            $filterQuery[] = implode( ' OR ', $subtreeQueryParts );
        }

        // Add policy limitation query filter
        $policyLimitationFilterQuery = $this->policyLimitationFilterQuery();
        if ( $policyLimitationFilterQuery !== false )
        {
            $filterQuery[] = $policyLimitationFilterQuery;
        }

        // Add content class query filter
        $classLimitationFilter = $this->getContentClassFilterQuery( $contentClassID );
        if ( $classLimitationFilter !== null )
        {
            $filterQuery[] = $classLimitationFilter;
        }

        // Add section to query filter.
        if ( $sectionID )
        {
            $filterQuery[] = eZSolr::getMetaFieldName( 'section_id' ) . ':' . $sectionID;
        }

        // Add Filter from function parameters
        // but add the sitelanguage first (only current language is searched)
        // maybe we'll make this configurable later on
        $ini = eZINI::instance();
        // @todo 2.0 - check ini settings wether or not to search main language only
        $languages = $ini->variable( 'RegionalSettings', 'SiteLanguageList' );
        $mainLanguage = $languages[0];
        $params['Filter']['language_code'] =  $mainLanguage;
        $paramFilterQuery = $this->getParamFilterQuery( $params );
        if ( $paramFilterQuery )
        {
            $filterQuery[] = $paramFilterQuery;
        }

        //add raw filters
        $rawFilters = self::$FindINI->variable( 'SearchFilters', 'RawFilterList' );
        if ( is_array( $rawFilters ) )
        {
            $filterQuery = array_merge( $filterQuery, $rawFilters );
        }

        // Build and get facet query prameters.
        $facetQueryParamList = $this->buildFacetQueryParamList( $params );

        // return only text searcheable fields by passing NULL
        $fieldTypeExcludeList = $this->fieldTypeExludeList( NULL );

        // Create sort parameters based on the parameters.
        $sortParameter = $this->buildSortParameter( $params );

        //the array_unique below is necessary because attribute identifiers are not unique .. and we get as
        //much highlight snippets as there are duplicate attribute identifiers
        //these are also in the list of query fields (dismax, ezpublish) request handlers
        $queryFields = array_unique( $this->getClassAttributes( $contentClassID, false, $fieldTypeExcludeList ) );

        //query type can vary for MLT q, or stream
        //if no valid match for the mlt query variant is obtained, it is treated as text
        $mltVariant = 'q';
        switch ( strtolower ($queryType) )
        {
            case 'nid':
                $mltQuery = eZSolr::getMetaFieldName( 'node_id') . ':' . $query;
                break;
            case 'oid':
                $mltQuery = eZSolr::getMetaFieldName( 'object_id') . ':' . $query;
                break;
            case 'url':
                $mltVariant = 'stream.url';
                $mltQuery = $query;
                break;
            case 'text':
            default:
                $mltVariant = 'stream.body';
                $mltQuery = $query;
                break;
        }

        // @todo decide which of the hard-coded mlt parameters should become input parameters or ini settings
        return array_merge(
            array(
                $mltVariant => $mltQuery,
                'start' => $offset,
                'rows' => $limit,
                'sort' => $sortParameter,
                'indent' => 'on',
                'version' => '2.2',
                'mlt.match.include' => 'false', // exclude the doc itself
                'mlt.minwl' => 3, //minimum wordlength
                'mlt.mindf' => 2,
                'mlt.mintf'=> 2,
                'mlt.interestingTerms' => 'list', // useful for debug output
                'mlt.boost' => 'true', // boost the highest ranking terms
                //'mlt.qf' => implode( ' ', $queryFields ),
                'mlt.fl' => implode( ' ', $queryFields ),
                'fl' =>
                eZSolr::getMetaFieldName( 'guid' ) . ' ' . eZSolr::getMetaFieldName( 'installation_id' ) . ' ' .
                eZSolr::getMetaFieldName( 'main_url_alias' ) . ' ' . eZSolr::getMetaFieldName( 'installation_url' ) . ' ' .
                eZSolr::getMetaFieldName( 'id' ) . ' ' . eZSolr::getMetaFieldName( 'main_node_id' ) . ' ' .
                eZSolr::getMetaFieldName( 'language_code' ) . ' ' . eZSolr::getMetaFieldName( 'name' ) .
                ' score ' . eZSolr::getMetaFieldName( 'published' ),
                'fq' => $filterQuery,
                'wt' => 'php' ),
            $facetQueryParamList );

        return $queryParams;
    }
    /**
     * Build sort parameter based on params provided.
     * @todo specify dedicated sorting fields
     * @param array Parameter list array. SortBy element contains sort
     * definition.
     *
     * @return string Sort description. Default sort string is 'score desc'.
     */
    protected function buildSortParameter( $parameterList )
    {
        $sortString = 'score desc';

        if ( !empty( $parameterList['SortBy'] ) )
        {
            $sortString = '';
            foreach( $parameterList['SortBy'] as $field => $order )
            {
                // If array, set key and order from array values
                if ( is_array( $order ) )
                {
                    $field = $order[0];
                    $order = $order[1];
                }

                // Fixup field name
                switch( $field )
                {
                    case 'score':
                    case 'relevance':
                    {
                        $field = 'score';
                    } break;

                    case 'published':
                    case 'modified':
                    case 'class_name':
                    case 'class_identifier':
                    case 'name':
                    case 'path':
                    case 'section_id':
                    {
                        $field = eZSolr::getMetaFieldName( $field );
                    } break;

                    case 'author':
                    {
                        $field = eZSolr::getMetaFieldName( 'owner_name' );
                    } break;

                    default:
                    {
                        $field = eZSolr::getFieldName( $field );
                        if ( !$field )
                        {
                            eZDebug::writeNotice( 'Sort field does not exist in local installation, but may still be valid: ' .
                                                  $facetDefinition['field'],
                                                  'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                            continue;
                        }
                    } break;
                }

                // Fixup order name.
                switch( strtolower( $order ) )
                {
                    case 'desc':
                    case 'asc':
                    {
                        $order = strtolower( $order );
                    } break;

                    default:
                    {
                        eZDebug::writeDebug( 'Unrecognized sort order. Settign for order for default: "desc"',
                                             'ezfeZPSolrQueryBuilder::buildSortParameter()' );
                        $order = $order;
                    } break;
                }

                if ( $sortString !== '' )
                {
                    $sortString .= ',';
                }

                $sortString .= $field . ' ' . $order;
            }
        }

        return $sortString;
    }

    /**
     * Build filter query from search filter parameter.
     * @deprecated api is way too limited now
     * @todo for eZ Find 2.0: rework this for recursive boolean combinations and a few more filter types, the possible combinations are almost infinite for pure Solr syntax
     * @param array Parameter list array.
     *              The normal simple use is an array of type: array( '<field name>', <value> ).
     *              The value may also be an array containing values.
     *
     *              The value may be the <basename>:<value>, example: array( 'Filter' => array( 'car/make:audi' ) )
     *
     *              The value may also be a string, or range, example: [10 to *].
     *
     *
     * @return string Filter Query. Null if no filter parameters are in
     * the $parameterList
     */
    protected function getParamFilterQuery( $parameterList )
    {
        if ( empty( $parameterList['Filter'] ) )
        {
            return null;
        }

        $filterQueryList = array();
        foreach( $parameterList['Filter'] as $baseName => $value )
        {
            if ( strpos( $value, ':' ) !== false && is_numeric( $baseName ) )
            {
                // split at the first colon, leave the rest intact
                list( $baseName, $value ) = explode( ':', $value, 2 );
            }

            // Get internal field name.
            $baseName = eZSolr::getFieldName( $baseName );
            if ( is_array( $value ) )
            {
                foreach( $value as $subValue )
                {
                    //$filterQueryList[] = $baseName . ':' . self::quoteIfNeeded( $subValue );
                    $filterQueryList[] = $baseName . ':' . $subValue;
                }
            }
            else
            {
                //$filterQueryList[] = $baseName . ':' . self::quoteIfNeeded( $value );
                $filterQueryList[] = $baseName . ':' . $value;
            }
        }

        return implode( ' AND ', $filterQueryList );
    }

    /**
     * Analyze the string, and decide if quotes should be added or not.
     *
     * @param string String
     *
     * @return string String with quotes added if needed.
     */
    static function quoteIfNeeded( $value )
    {
        $quote = '';
        if ( strpos( $value, ' ' ) !== false )
        {
            $quote = '"';
            if ( strpos( trim( $value ), '(' ) === 0 )
            {
                $quote = '';
            }
        }
        return $quote . $value . $quote;
    }

    /**
     * Build facet parameter list. This function extracts the facet parameter from
     * the ezfeZPSolrQueryBuilder::search( ...,$params parameter.
     *
     * @todo specify dedicated facet fields (may be mapped to sort fields)
     *
     * @param array Parameter list array
     *
     * @return array List of Facet query parameter. The facet parameter corrosponds to
     * the parameters defined here : http://wiki.apache.org/solr/SimpleFacetParameters
     */
    protected function buildFacetQueryParamList( $parameterList )
    {
        $parameterList = array_change_key_case( $parameterList, CASE_LOWER );
        $queryParamList = array();

        if ( empty( $parameterList['facet'] ) )
        {
            return $queryParamList;
        }

        // Loop through facet definitions, and build facet query.
        foreach( $parameterList['facet'] as $facetDefinition )
        {
            if ( empty( $facetDefinition['field'] ) &&
                 empty( $facetDefinition['query'] ) )
            {
                eZDebug::writeDebug( 'No facet field or query provided.',
                                     'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                continue;
            }

            $queryPart = array();
            if ( !empty( $facetDefinition['field'] ) )
            {
                switch( $facetDefinition['field'] )
                {
                    case 'author':
                    {
                        $queryPart['field'] = eZSolr::getMetaFieldName( 'owner_id' );
                    } break;

                    case 'class':
                    {
                        $queryPart['field'] = eZSolr::getMetaFieldName( 'contentclass_id' );
                    } break;

                    case 'installation':
                    {
                        $queryPart['field'] = eZSolr::getMetaFieldName( 'installation_id' );
                    } break;

                    case 'translation':
                    {
                        $queryPart['field'] = eZSolr::getMetaFieldName( 'language_code' );
                    } break;

                    default:
                    {
                        $fieldName = eZSolr::getFieldName( $facetDefinition['field'] );
                        if ( !$fieldName )
                        {
                            eZDebug::writeNotice( 'Facet field does not exist in local installation, but may still be valid: ' .
                                                  $facetDefinition['field'],
                                                  'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                            continue;
                        }
                        $queryPart['field'] = $fieldName;
                    } break;
                }
            }

            // Get query part.
            if ( !empty( $facetDefinition['query'] ) )
            {
                list( $field, $query ) = explode( ':', $facetDefinition['query'] );

                $field = eZSolr::getFieldName( $field );
                if ( !$field )
                {
                    eZDebug::writeNotice( 'Invalid query field provided: ' . $facetDefinition['query'],
                                          'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                    continue;
                }

                $queryPart['query'] = $field . ':' . $query;
            }

            // Get prefix.
            if ( !empty( $facetDefinition['prefix'] ) )
            {
                $queryPart['prefix'] = $facetDefinition['prefix'];
            }

            // Get sort option.
            if ( !empty( $facetDefinition['sort'] ) )
            {
                switch( strtolower( $facetDefinition['sort'] ) )
                {
                    case 'count':
                    {
                        $queryPart['sort'] = 'true';
                    } break;

                    case 'alpha':
                    {
                        $queryPart['sort'] = 'false';
                    } break;

                    default:
                    {
                        eZDebug::writeWarning( 'Invalid sort option provided: ' . $facetDefinition['sort'],
                                               'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                    } break;
                }
            }

            // Get limit option
            if ( !empty( $facetDefinition['limit'] ) )
            {
                $queryPart['limit'] = $facetDefinition['limit'];
            }
            else
            {
                $queryPart['limit'] = ezfeZPSolrQueryBuilder::FACET_LIMIT;
            }

            // Get offset
            if ( !empty( $facetDefinition['offset'] ) )
            {
                $queryPart['offset'] = $facetDefinition['offset'];
            }
            else
            {
                $queryPart['offset'] = ezfeZPSolrQueryBuilder::FACET_OFFSET;
            }

            // Get mincount
            if ( !empty( $facetDefinition['mincount'] ) )
            {
                $queryPart['mincount'] = $facetDefinition['mincount'];
            }
            else
            {
                $queryPart['mincount'] = ezfeZPSolrQueryBuilder::FACET_MINCOUNT;
            }

            // Get missing option.
            if ( !empty( $facetDefinition['missing'] ) )
            {
                $queryPart['missing'] = 'true';
            }

            // Get date start option - may add validation later.
            if ( !empty( $facetDefinition['date.start'] ) )
            {
                $queryPart['date.start'] = $facetDefinition['date.start'];
            }

            // Get date end option - may add validation later.
            if ( !empty( $facetDefinition['date.end'] ) )
            {
                $queryPart['date.end'] = $facetDefinition['date.end'];
            }

            // Get date gap option - may add validation later.
            if ( !empty( $facetDefinition['date.gap'] ) )
            {
                $queryPart['date.gap'] = $facetDefinition['date.gap'];
            }

            // Get date hardend option - may add validation later.
            if ( !empty( $facetDefinition['date.hardend'] ) )
            {
                $queryPart['date.hardend'] = $facetDefinition['date.hardend'];
            }

            // Get date hardend option - may add validation later.
            if ( !empty( $facetDefinition['date.other'] ) )
            {
                switch( strtolower( $facetDefinition['date.other'] ) )
                {
                    case 'before':
                    case 'after':
                    case 'between':
                    case 'none':
                    case 'all':
                    {
                        $queryPart['date.other'] = strtolower( $facetDefinition['date.other'] );
                    }

                    default:
                    {
                        eZDebug::writeWarning( 'Invalid option gived for date.other: ' . $facetDefinition['date.other'],
                                               'ezfeZPSolrQueryBuilder::buildFacetQueryParamList()' );
                    } break;
                }
            }

            if ( count( $queryPart ) )
            {
                foreach( $queryPart as $key => $value )
                {
                    $queryParamList['facet.' . $key][] = $value;
                }
            }
        }

        if ( count( $queryParamList ) )
        {
            $queryParamList['facet'] = 'true';
        }

        return $queryParamList;
    }

    /**
     * Check if search string requires certain field types to be excluded from the search
     *
     * @param string Search string
     * if $searchText is null, exclude all non text fields
     * @todo make sure this function is in sync with schema.xml
     * @todo decide wether or not to drop this, pure numeric and date values are most likely to go into filters, not the main query
     *
     * @return array List of field types to exclude from the search
     */
    protected function fieldTypeExludeList( $searchText )
    {
        if ( is_null($searchText) )
        {
            return array('date', 'boolean', 'int', 'long', 'float', 'double', 'sint', 'slong', 'sfloat', 'sdouble');
        }



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
        if ( !is_numeric( $searchText ) )
        {
            $excludeFieldList[] = 'int';
            $excludeFieldList[] = 'long';
            $excludeFieldList[] = 'float';
            $excludeFieldList[] = 'double';
            $excludeFieldList[] = 'sint';
            $excludeFieldList[] = 'slong';
            $excludeFieldList[] = 'sfloat';
            $excludeFieldList[] = 'sdouble';
        }

        return $excludeFieldList;
    }

    /**
     * Generate boost query on search. This boost is configured boost the following criterias:
     * - local installation
     * - Language priority
     *
     * @return boostQuery
     */
    protected function boostQuery()
    {
        // Local installation boost
        $boostQuery = eZSolr::getMetaFieldName( 'installation_id' ) . ':' . eZSolr::installationID() . '^1.5';
        $ini = eZINI::instance();

        // Language boost. Only boost 3 first languages.
        $languageBoostList = array( '1.2', '1.0', '0.8' );
        foreach ( $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) as $idx => $languageCode )
        {
            if ( empty( $languageBoostList[$idx] ) )
            {
                break;
            }
            $boostQuery .= ' ' . eZSolr::getMetaFieldName( 'language_code' ) . ':' . $languageCode . '^' . $languageBoostList[$idx];
        }

        // User defined boosts through ini settings


        return $boostQuery;
    }

    /**
     * Generate class query filter.
     *
     * @param mixed eZContentClass id, identifier or list of ids.
     *
     * @return string Content class query filter. Returns null if invalid
     * $contentClassIdent is provided.
     */
    protected function getContentClassFilterQuery( $contentClassIdent )
    {
        if ( empty( $contentClassIdent ) )
        {
            return null;
        }

        if ( is_array( $contentClassIdent ) )
        {
            $classQueryParts = array();
            foreach ( $contentClassIdent as $classID )
            {
                $classID = trim( $classID );
                if ( empty( $classID ) )
                {
                    continue;
                }
                if ( (int)$classID . '' == $classID . '' )
                {
                    $classQueryParts[] = eZSolr::getMetaFieldName( 'contentclass_id' ) . ':' . $classID;
                }
                elseif ( is_string( $classID ) )
                {
                    if ( $class = eZContentClass::fetchByIdentifier( $classID ) )
                    {
                        $classQueryParts[] = eZSolr::getMetaFieldName( 'contentclass_id' ) . ':' . $class->attribute( 'id' );
                    }
                }
            }

            return implode( ' OR ', $classQueryParts );
        }
        elseif ( (int)$contentClassIdent . '' == $contentClassIdent . '' )
        {
            return eZSolr::getMetaFieldName( 'contentclass_id' ) . ':' . $contentClassIdent;
        }
        elseif ( is_string( $contentClassIdent ) )
        {
            if ( $class = eZContentClass::fetchByIdentifier( $contentClassIdent ) )
            {
                return eZSolr::getMetaFieldName( 'contentclass_id' ) . ':' . $class->attribute( 'id' );
            }
        }

        eZDebug::writeError( 'No valid content class',
                             'ezfeZPSolrQueryBuilder::getContentClassFilterQuery()' );

        return null;
    }

    /**
     * Create policy limitation query.
     *
     * @param $limitation array in the same format as $accessResult['policies']
     * @param $ignoreVisibility boolean
     * @return string Lucene/Solr query string which can be used as filter query for Solr
     */
    protected function policyLimitationFilterQuery( $limitation = null, $ignoreVisibility = false )
    {
        $filterQuery = false;
        $policies = array();
        if ( is_array( $limitation ) && ( count( $limitation ) == 0 ) )
        {
            return $filterQuery;
        }
        elseif ( is_array( $limitation ) && ( count( $limitation ) > 0 ) )
        {
            $policies = $limitation;
        }
        else
        {
            $currentUser = eZUser::currentUser();
            $accessResult = $currentUser->hasAccessTo( 'content', 'read' );
            if ( !in_array( $accessResult['accessWord'], array( 'yes', 'no' ) ) )
            {
                $policies = $accessResult['policies'];
            }
        }


        // Add limitations for filter query based on local permissions.


        $limitationHash = array(
            'Class'        => eZSolr::getMetaFieldName( 'contentclass_id' ),
            'Section'      => eZSolr::getMetaFieldName( 'section_id' ),
            'User_Section' => eZSolr::getMetaFieldName( 'section_id' ),
            'Subtree'      => eZSolr::getMetaFieldName( 'path_string' ),
            'User_Subtree' => eZSolr::getMetaFieldName( 'path_string' ),
            'Node'         => eZSolr::getMetaFieldName( 'main_node_id' ),
            'Owner'        => eZSolr::getMetaFieldName( 'owner_id' ),
            'Group'        => eZSolr::getMetaFieldName( 'owner_group_id' ) );

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
                            $subtreeNodeID = array_pop( $pathArray );
                            $filterQueryPolicyLimitationParts[] = eZSolr::getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;
                        }
                    } break;

                    case 'Node':
                    {
                        foreach ( $limitationValues as $limitationValue )
                        {
                            $pathString = trim( $limitationValue, '/' );
                            $pathArray = explode( '/', $pathString );
                            // we only take the last node ID in the path identification string
                            $nodeID = array_pop( $pathArray );
                            $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $nodeID;
                        }
                    } break;

                    case 'Group':
                    {
                        foreach( eZUser::currentUser()->attribute( 'contentobject' )->attribute( 'parent_nodes' ) as $groupID )
                        {
                            $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $groupID;
                        }
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
                        eZDebug::writeDebug( $limitationType,
                                             'ezfeZPSolrQueryBuilder::policyLimitationFilterQuery unknown limitation type: ' . $limitationType );
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


        // Add limitations for allowing search of other installations.
        $anonymousPart = '';
        if ( self::$FindINI->variable( 'SiteSettings', 'SearchOtherInstallations' ) == 'enabled' )
        {
            $anonymousPart = ' OR ' . eZSolr::getMetaFieldName( 'anon_access' ) . ':true ';
        }

        if ( !empty( $filterQuery ) )
        {
            $filterQuery = '((' . eZSolr::getMetaFieldName( 'installation_id' ) . ':' . eZSolr::installationID() . ' AND (' . $filterQuery . ')) ' . $anonymousPart . ' )';
        }
        else
        {
            $filterQuery = '(' . eZSolr::getMetaFieldName( 'installation_id' ) . ':' . eZSolr::installationID() . $anonymousPart . ')';
        }

        // Add limitations based on allowed languages.
        $ini = eZINI::instance();
        if ( $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) )
        {
            $filterQuery = '( ' . $filterQuery . ' AND ( ' . eZSolr::getMetaFieldName( 'language_code' ) . ':' .
                implode( ' OR ' . eZSolr::getMetaFieldName( 'language_code' ) . ':', $ini->variable( 'RegionalSettings', 'SiteLanguageList' ) ) . ' ) )';
        }

        // Add visibility condition
        if ( !eZContentObjectTreeNode::showInvisibleNodes() || !$ignoreVisibility )
        {
            $filterQuery .= ' AND ' . eZSolr::getMetaFieldName( 'is_invisible' ) . ':false';
        }

        eZDebug::writeDebug( $filterQuery,
                             'ezfeZPSolrQueryBuilder::policyLimitationFilterQuery' );

        return $filterQuery;
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
    protected function getClassAttributes( $classIDArray = false,
                                           $classAttributeIDArray = false,
                                           $fieldTypeExcludeList = null )
    {
        eZDebug::createAccumulator( 'Class attribute list', 'eZ Find' );
        eZDebug::accumulatorStart( 'Class attribute list' );
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

        eZDebug::accumulatorStop( 'Class attribute list' );
        return $fieldArray;
    }

    /// Vars
    static $FindINI;

    const FACET_LIMIT = 20;
    const FACET_OFFSET = 0;
    const FACET_MINCOUNT = 1;
}

ezfeZPSolrQueryBuilder::$FindINI = eZINI::instance( 'ezfind.ini' );

?>

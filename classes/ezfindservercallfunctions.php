<?php

/**
 * Implements methods called remotely by sending XHR calls
 *
 */
class eZFindServerCallFunctions
{
    /**
     * Returns search results based on given params
     *
     * @param mixed $args
     * @return array
     * @deprecated Use ezjsc::search instead (in ezjscore)
     */
    public static function search( $args )
    {
        $http = eZHTTPTool::instance();

        if ( $http->hasPostVariable( 'SearchStr' ) )
            $searchStr = trim( $http->postVariable( 'SearchStr' ) );

        $searchOffset = 0;
        if ( $http->hasPostVariable( 'SearchOffset' ))
            $searchOffset = (int) $http->postVariable( 'SearchOffset' );

        $searchLimit = 10;
        if ( $http->hasPostVariable( 'SearchLimit' ))
            $searchLimit = (int) $http->postVariable( 'SearchLimit' );

        if ( $searchLimit > 30 ) $searchLimit = 30;

        if ( $http->hasPostVariable( 'SearchSubTreeArray' ) && $http->postVariable( 'SearchSubTreeArray' ) )
        {
            $search_sub_tree_array = explode( ',', $http->postVariable( 'SearchSubTreeArray' ) );
        }

        //Prepare the search params
        $param = array( 'SearchOffset' => $searchOffset,
                        'SearchLimit' => $searchLimit+1,
                        'SortArray' => array( 'score', 0 ),
                        'SearchSubTreeArray' => $search_sub_tree_array
                      );

        if ( $http->hasPostVariable( 'enable-spellcheck' ) and $http->postVariable( 'enable-spellcheck' ) == 1 )
        {
            $param['SpellCheck'] = array( true );
        }

        if ( $http->hasPostVariable( 'show-facets' ) and $http->postVariable( 'show-facets' ) == 1 )
        {
            $defaultFacetFields = eZFunctionHandler::execute( 'ezfind', 'getDefaultSearchFacets', array() );
            $param['facet'] = $defaultFacetFields;
        }

        $solr= new eZSolr();
        $searchList = $solr->search( $searchStr, $param );

        $result = array();
        $result['SearchResult'] = eZFlowAjaxContent::nodeEncode( $searchList['SearchResult'], array(), false );
        $result['SearchCount'] = $searchList['SearchCount'];
        $result['SearchOffset'] = $searchOffset;
        $result['SearchLimit'] = $searchLimit;
        $result['SearchExtras'] = array();

        if ( isset( $param['SpellCheck'] ) )
            $result['SearchExtras']['spellcheck'] = $searchList['SearchExtras']->attribute( 'spellcheck' );

        if ( isset( $param['facet'] ) )
        {
            $facetInfo = array();
            $retrievedFacets = $searchList['SearchExtras']->attribute( 'facet_fields' );
            $baseSearchUrl = "/content/search/";
            eZURI::transformURI( $baseSearchUrl, false, 'full' );

            foreach ( $defaultFacetFields as $key => $defaultFacet )
            {
                $facetData=$retrievedFacets[$key];
                $facetInfo[$key] = array();
                $facetInfo[$key][] = $defaultFacet['name'];

                if ( $facetData != null )
                {
                    foreach ( $facetData['nameList'] as $key2 => $facetName )
                    {
                        $tmp = array();
                        if ( $key2 != '' )
                        {
                            $tmp[] = $baseSearchUrl . '?SearchText=' . $searchStr . '&filter[]=' . $facetData['queryLimit'][$key2] . '&activeFacets[' . $defaultFacet['field'] . ':' . $defaultFacet['name'] . ']=' . $facetName;
                            $tmp[] = $facetName;
                            $tmp[] = "(" . $facetData['countList'][$key2] . ")";
                            $facetInfo[$key][] = $tmp;
                        }
                    }
                }
            }
            $result['SearchExtras']['facets'] = $facetInfo;
        }

        return $result;
    }

    /**
     * Returns autocomplete suggestions for given params
     *
     * @param mixed $args
     * @return array
     */
    public static function autocomplete( $args )
    {
        $result = array();
        $findINI = eZINI::instance( 'ezfind.ini' );
        $fullSolrURI = false;
        if ( $findINI->variable( 'LanguageSearch', 'MultiCore' ) == 'enabled' )
        {
           $solrINI = eZINI::instance( 'solr.ini' );
           $siteINI = eZINI::instance( 'site.ini' );
           $currentLanguage = $siteINI->variable( 'RegionalSettings', 'Locale');
           $languageMapping = $findINI->variable( 'LanguageSearch','LanguagesCoresMap');
           $shardMapping = $solrINI->variable ('SolrBase', 'Shards');
           $fullSolrURI=$shardMapping[$languageMapping[$currentLanguage]];
        }

        $input = isset( $args[0] ) ? mb_strtolower( $args[0], 'UTF-8' ) : null;
        $limit = isset( $args[1] ) ? (int)$args[1] : (int)$findINI->variable( 'AutoCompleteSettings', 'Limit' );

        $facetField = $findINI->variable( 'AutoCompleteSettings', 'FacetField' );

        $params = array( 'q' => '*:*',
                         'json.nl' => 'arrarr',
                         'facet' => 'true',
                         'facet.field' => $facetField,
                         'facet.prefix' => $input,
                         'facet.limit' => $limit );

        $solrBase = new eZSolrBase( $fullSolrURI );
        $result = $solrBase->rawSolrRequest( '/select', $params, 'json' );

        return $result['facet_counts']['facet_fields'][$facetField];
    }
}

?>

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

        //Prepare the search params
        $param = array( 'SearchOffset' => $searchOffset,
                        'SearchLimit' => $searchLimit+1,
                        'SortArray' => array( 'score', 0 )
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
        $ini = eZINI::instance( 'ezfind.ini' );

        $input = isset( $args[0] ) ? $args[0] : null;
        $limit = isset( $args[1] ) ? (int)$args[1] : (int)$ini->variable( 'AutoCompleteSettings', 'Limit' );

        $params = $ini->variable( 'AutoCompleteSettings', 'SolrParams' );

        // Replace <input> and <limit> tags with proper values
        $params[array_search( '<input>', $params)] = $input;
        $params[array_search( '<limit>', $params)] = $limit;

        $sorlBase = new eZSolrBase();
        $result = $sorlBase->rawSolrRequest( '/select', $params, 'json' );

        return $result['facet_counts']['facet_fields']['ezf_sp_words'];
    }
}

?>
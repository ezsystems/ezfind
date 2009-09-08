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

        $enableSpellcheck = false;
        if ( $http->hasPostVariable( 'enable-spellcheck' ) and $http->postVariable( 'enable-spellcheck' ) )
        {
            $param['SpellCheck'] = array( true );
        }

        // @FIXME : replace by ezfind.
        $solr= new eZSolr();
        $searchList = $solr->search( $searchStr, $param );

        $result = array();
        $result['SearchResult'] = eZFlowAjaxContent::nodeEncode( $searchList['SearchResult'], array(), false );
        $result['SearchCount'] = $searchList['SearchCount'];
        $result['SearchOffset'] = $searchOffset;
        $result['SearchLimit'] = $searchLimit;
        $result['SearchExtras'] = array();
        if ( $param['SpellCheck'][0] )
            $result['SearchExtras']['spellcheck'] = $searchList['SearchExtras']->attribute( 'spellcheck' );

        // @ TODO : add optional facets and spellcheck here.

        return $result;
    }


}

?>
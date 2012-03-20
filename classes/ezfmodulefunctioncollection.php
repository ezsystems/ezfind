<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
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

/*! \file ezfmodulefunctioncollection.php
*/


/**
 * The ezfModuleFunctionCollection contains methods for functions defined
 * in the module ezfind.
 */
class ezfModuleFunctionCollection
{
    /**
     * Constructor
     */
    function ezfModuleFunctionCollection()
    {
    }

    /**
     * Get HTTP get facet parameters
     *
     * @return array HTTP GET facet parameters, as described in the facets
     * design document
     */
    public function getFacetParameters()
    {
        $facetArray = array();
        foreach ( $_GET as $name => $value )
        {
            if ( strpos( $name, 'facet_' ) === 0 )
            {
                $facetArray[substr( $name, 6 )] = $value;
            }
        }

        return array( 'result' => array( $facetArray ) );
    }

    /**
     * Get HTTP get filter parameters.
     * The filter parameters are specified by "filter[]=<base_name>:<value>".
     * Example:
     * <code>
     * http://my_url/some/path?filter[]=owner_id:12&filter[]=language_code:eng-GB
     *
     * @return array HTTP GET filter parameters
     */
    public function getFilterParameters()
    {
        $http = eZHTTPTool::instance();
        $filterList = array();
        if ( $http->hasGetVariable( 'filter' ) )
        {
            foreach ( $http->getVariable( 'filter' ) as $filterCond )
            {
                list( $name, $value ) = explode( ':', $filterCond, 2 );
                $filterList[$name] = $value;
            }
        }

        return array( 'result' => $filterList );
    }

    /**
     * Search function
     *
     * @param string Query string
     * @param int Offset
     * @param int Limit
     * @param array Facet definition
     * @param array Filter parameters
     * @param array Sort by parameters
     * @param mixed Content class ID or list of content class IDs
     * @param array list of subtree limitation node IDs
     * @param boolean $enableElevation Controls whether elevation should be enabled or not
     * @param boolean $forceElevation Controls whether elevation is forced. Applies when the srt criteria is NOT the default one ( 'score desc' ).
     *
     * @return array Search result
     */
    public function search( $query, $offset = 0, $limit = 10, $facets = null,
                            $filters = null, $sortBy = null, $classID = null, $sectionID = null,
                            $subtreeArray = null, $ignoreVisibility = false, $limitation = null, $asObjects = true, $spellCheck = null, $boostFunctions = null, $queryHandler = 'ezpublish',
                            $enableElevation = true, $forceElevation = false, $publishDate = null, $distributedSearch = null, $fieldsToReturn = null )
    {
        $solrSearch = new eZSolr();
        $params = array( 'SearchOffset' => $offset,
                         'SearchLimit' => $limit,
                         'Facet' => $facets,
                         'SortBy' => $sortBy,
                         'Filter' => $filters,
                         'SearchContentClassID' => $classID,
                         'SearchSectionID' => $sectionID,
                         'SearchSubTreeArray' => $subtreeArray,
                         'AsObjects' => $asObjects,
                         'SpellCheck' => $spellCheck,
                         'IgnoreVisibility' => $ignoreVisibility,
                         'Limitation' => $limitation,
                         'BoostFunctions' => $boostFunctions,
                         'QueryHandler' => $queryHandler,
                         'EnableElevation' => $enableElevation,
                         'ForceElevation' => $forceElevation,
                         'SearchDate' => $publishDate,
                         'DistributedSearch' => $distributedSearch,
                         'FieldsToReturn' => $fieldsToReturn );
        return array( 'result' => $solrSearch->search( $query, $params ) );
    }

    /**
     * rawSolrRequest function
     *
     * @param base specifies the Solr server/shard to use
     * @param request the base request
     * @param parameters all parameters for the request
     *
     * @return array result as a PHP array
     */
    public function rawSolrRequest( $baseURI, $request, $parameters = array() )
    {

        $solr = new eZSolrBase( $baseURI );
        return array( 'result' => $solr->rawSolrRequest( $request, $parameters ) );
    }

    /**
     * moreLikeThis function
     * @todo document the solrconfig.xml required setting for remote streaming to be true
     *       if $queryType 'url' is to be used
     * @todo consider adding limitation and visibility parameters
     *
     * @param string $queryType string ( 'nid' | 'oid' | 'text' | 'url' )
     * @param string $query value for QueryType
     * @param int Offset
     * @param int Limit
     * @param array Facet definition
     * @param array Filter parameters
     * @param array Sort by parameters
     * @param mixed Content class ID or list of content class IDs
     * @param array list of subtree limitation node IDs
     * @param boolean asObjects return regular eZPublish objects if true, stored Solr content if false
     * @param string|null $queryInstallationID the eZ Find installation id to
     *        use when looking for the reference document in Solr
     *
     * @return array result as a PHP array
     */
    public function moreLikeThis( $queryType, $query, $offset = 0, $limit = 10, $facets = null,
                                  $filters = null, $sortBy = null, $classID = null, $sectionID = null,
                                  $subtreeArray = null, $asObjects = true, $queryInstallationID = null )

    {
        $solrSearch = new eZSolr();
        $params = array( 'SearchOffset' => $offset,
                         'SearchLimit' => $limit,
                         'Facet' => $facets,
                         'SortBy' => $sortBy,
                         'Filter' => $filters,
                         'SearchContentClassID' => $classID,
                         'SearchSectionID' => $sectionID,
                         'SearchSubTreeArray' => $subtreeArray,
                         'QueryInstallationID' => $queryInstallationID,
                         'AsObjects' => $asObjects);
        return array( 'result' => $solrSearch->moreLikeThis( $queryType, $query, $params ) );


    }

    /*
     * Retrieves the Elevate configuration, optionnally filtered.
     * @todo Add the sort_by, languageCode, searchQuery parameters
     *
     * @param boolean $countOnly If only the count of configuration elements shall be fetched, optionnally filtered.
     * @param integer $offset Used to frame the fetch.
     * @param integer $limit Used to frame the fetch.
     * @param string $searchQuery Find elevate configurations for a given search query, with or without fuzzy search.
     * @param string $languageCode Find elevate configurations for a given language.
     *
     * @see eZFindElevateConfiguration::fetchObjectsForQueryString
     */
    public function fetchElevateConfiguration( $countOnly = false, $offset = 0, $limit = 10, $searchQuery = null, $languageCode = null )
    {
        $conds = null;
        $limit = array( 'offset' => $offset,
                        'limit' => $limit );
        $fieldFilters = null;
        $custom = null;

        // START polymorphic part
        if ( $searchQuery !== null )
        {
            $results = eZFindElevateConfiguration::fetchObjectsForQueryString( $searchQuery, false, $languageCode, $limit, $countOnly );
        }
        else
        {
            if ( $languageCode )
                $conds = array( 'language_code' => $languageCode );

            if ( $countOnly )
            {
                $results = eZPersistentObject::count( eZFindElevateConfiguration::definition(),
                                                                $conds );
            }
            else
            {
                $sorts = array( 'search_query' => 'asc' );
                $results = eZPersistentObject::fetchObjectList( eZFindElevateConfiguration::definition(),
                                                                $fieldFilters,
                                                                $conds,
                                                                $sorts,
                                                                $limit,
                                                                false,
                                                                false,
                                                                $custom );
            }
        }
        // END polymorphic part

        if ( $results === null )
        {
            // @TODO : return a more explicit error code and info.
            return array( 'error' => array( 'error_type' => 'extension/ezfind/elevate',
                                            'error_code' => eZError::KERNEL_NOT_FOUND ) );
        }
        else
        {
            return array( 'result' => $results );
        }
    }

    /**
     * spellCheck function, see also the search integrated spell check
     *
     * @param string contains the string/word
     * @param parameters all parameters for the request
     * @param realm the ini configured parameters grouped into a realm
     *
     * @return array result as a PHP array
     */
    public function spellCheck( $string, $parameters = array(), $realm = null )
    {
        //@todo: configure a spellCheck request handler and implement a raw Solr request to it
        return false;
    }


    public function getDefaultSearchFacets()
    {
        $limit = 5;
        $facets = array();
        $facets[] = array( 'field' => 'class',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Content type' ),
                           'limit' => $limit );
        $facets[] = array( 'field' => 'author',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Author' ),
                           'limit' => $limit );

        /*$facets[] = array( 'field' => 'modified',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Last modified' ),
                           'limit' => $limit );*/
        $facets[] = array( 'field' => 'article/tags',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Keywords' ),
                           'limit' => $limit );

        $facets[] = array( 'range' => array( 'field' => 'published',
                                             'start' => 'NOW/YEAR-3YEARS',
                                             'end'   => 'NOW/YEAR+1YEAR',
                                             'gap'   => '+1YEAR',
                                             'other' => 'all'));


        // Date facets
        /*$facets[] = array( 'field' => 'published',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Creation time' ),
                           'limit' => $limit );
        */
        /*$facets[] = array( 'date' => 'modified',
                           'date.start' => 'NOW-1MONTH',
                           'date.end' => 'NOW',
                           'date.gap' => '%2B1DAY',
                           'name'  => ezpI18n::tr( 'extension/ezfind/facets', 'Last modified' ),
                           'limit' => $limit );*/

        // @TODO : location ( in the content tree )
        //$facets[] = array( 'field' => '' );

        return array( 'result' => $facets );
    }
}
?>

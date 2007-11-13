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
        foreach( $_GET as $name => $value )
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
            foreach( $http->getVariable( 'filter' ) as $filterCond )
            {
                list( $name, $value ) = explode( ':', $filterCond );
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
     *
     * @return array Search result
     */
    public function search( $query, $offset = 0, $limit = 10, $facet = null, $filter = null )
    {
        $solrSearch = new eZSolr();
        $params = array( 'SearchOffset' => $offset,
                         'SearchLimit' => $limit,
                         'Facet' => $facet,
                         'Filter' => $filter );
        return array( 'result' => $solrSearch->search( $query, $params ) );
    }
}

?>

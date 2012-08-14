<?php
/**
 * SolR Geodist extended attribute filter
 *
 * Returns nearest documents from a given reference geopoint
 *
 * Usage :
 * fetch( 'ezfind', 'search', hash(
 * 	   'extended_attribute_filter', array(
 * 		   hash(
 *             'id', 'geodist',
 *             'params', hash(
 *                 'field', 'article/location',
 *                 'latitude', '46.75984',
 *                 'longitude', '1.738281'
 *             )
 * 	       )
 * 	   )
 * ))
 *
 * Filter parameters :
 * - field    : solr geopoint field holding document location
 * - latitude : reference geopoint latitude
 * - longitude: reference geopoint longitude
 * @author bchoquet
 *
 */
class eZFindGeoDistExtendedAttributeFilter implements eZFindExtendedAttributeFilterInterface
{

    /**
     * Modifies SolR query params according to filter parameters
     * @param array $queryParams
     * @param array $filterParams
     * @return array $queryParams
     */
    public function filterQueryParams( array $queryParams, array $filterParams )
    {
        try
        {
            if( !isset( $filterParams['field'] ) )
            {
                throw new Exception( 'Missing filter parameter "field"' );
            }

            if( !isset( $filterParams['latitude'] ) )
            {
                throw new Exception( 'Missing filter parameter "latitude"' );
            }

            if( !isset( $filterParams['longitude'] ) )
            {
                throw new Exception( 'Missing filter parameter "longitude"' );
            }

            $fieldName = eZSolr::getFieldName( $filterParams['field'] );

            //geodist custom parameters
            $queryParams['sfield'] = $fieldName;
            $queryParams['pt'] = $filterParams['latitude'] . ',' . $filterParams['longitude'];

            //sort by geodist
            $queryParams['sort']  = 'geodist() asc,' . $queryParams['sort'];

            //exclude unlocated documents
            $queryParams['fq'][] = $fieldName.':[-90,-90 TO 90,90]';
        }
        catch( Exception $e )
        {
            eZDebug::writeWarning( $e->getMessage(), __CLASS__ );
        }

        return $queryParams;
    }
}
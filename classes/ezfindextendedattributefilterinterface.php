<?php
/**
 * Extended attribute filter interface
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author bchoquet
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */
interface eZFindExtendedAttributeFilterInterface
{
    /**
     * Modifies SolR query params according to filter parameters
     * The returned array is merged with global SolR query
     * @param array $queryParams
     * @param array $filterParams
     * @return array $queryParams
     */
    public function filterQueryParams( array $queryParams, array $filterParams );

}
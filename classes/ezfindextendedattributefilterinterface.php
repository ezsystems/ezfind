<?php
/**
 * Extended attribute filter interface
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author bchoquet
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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
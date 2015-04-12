<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

/**
 * Description of ezftestqueryplugin
 *
 * This test plugin simply looks up if the string 'article' is present in the query
 * provided by the user and if so, adds a class filter to type article
 *
 * @author paul
 *
 */
class ezfTestQueryPlugin implements ezfQuerySearchPlugin
{

    /**
     *
     * @param mixed $queryParams
     */
    public function modify( &$queryParams, $pluginParams = array() )
    {
        // To test plugin parameters in legacy templates, add to the search hash array:
        // 'plugin_parameters', hash( 'TestPlugin', hash( 'ClassIdentifier', 'folder' ) )
        // and add 'folder' as one of the search keywords or any other class identifier that may be fit

        $classIdentifier = isset($pluginParams['TestPlugin']['ClassIdentifier']) ? $pluginParams['TestPlugin']['ClassIdentifier'] : 'article';
        if ( strpos( $queryParams['q'], $classIdentifier ) !== FALSE )
        {
            $queryParams['fq'][]='meta_class_identifier_ms:' . $classIdentifier;
            //remove the filter value from the query string
            $queryParams['q'] = str_ireplace( $classIdentifier, '', $queryParams['q']) ;
        }
    }
}

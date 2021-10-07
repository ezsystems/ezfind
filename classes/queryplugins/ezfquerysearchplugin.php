<?php

/**
 * File containing Search Query Plugin Interface
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

/**
 * Description of ezfQuerySearchPlugin.
 * Interface that Query PLugins should implement.
 * The plugin code checks for the correct implementation.
 *
 */
interface ezfQuerySearchPlugin
{
    /**
     * @var array $queryParams
     */
    public function modify( &$queryParams, $pluginParams = array() );
}

?>

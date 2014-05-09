<?php

/**
 * File containing Index Plugin Interface
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

/**
 * Description of ezfIndexPlugin.
 * Interface that Index PLugins should implement.
 * The plugin code checks for the correct implementation.
 *
 */
interface ezfIndexPlugin
{
    /**
     * @var eZContentObject $contentObject
     * @var array $docList
     */
    public function modify( eZContentObject $contentObject, &$docList );
}

?>

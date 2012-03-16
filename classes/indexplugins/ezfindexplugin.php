<?php

/**
 * File containing Index Plugin Interface
 *
 * @copyright Copyright (C) 2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
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

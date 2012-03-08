<?php

/**
 * File containing Index Plugin Interface
 */

/**
 * Description of ezfIndexPlugin.
 * Interface that Index PLugins should implement.
 * The plugin code checks for the correct implementation.
 *
 * @author paul
 */
interface ezfIndexPlugin
{
    /**
     * @var eZContentObject $contentObject
     * @var array $docList
     */
    public function modify(eZContentObject $contentObject, &$docList);
}

?>

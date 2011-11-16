<?php

/**
 * File containing Index PLugin Interface
 */

/**
 * Description of azfIndexPlugin.
 * Interface that Index PLugins should implement.
 * The plugin code checks for the correct implementation.
 *
 * @author paul
 */
interface azfIndexPlugin
{
    /**
     * @var eZContentObject $contentObject
     * @var array $docList
     */
    public function modify(eZContentObject $contentObject, &$docList);
}

?>

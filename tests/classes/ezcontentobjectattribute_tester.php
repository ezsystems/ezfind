<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

class eZContentObjectAttributeTester extends eZContentObjectAttribute
{
    /**
     * @var eZContentClassAttribute
     */
    public $ContentClassAttribute;

    /**
     * Makes the 'contentclass_attribute' attribute a 'field' instead
     * of a function attribute, for testing purposes.
     *
     * @return the overridden definition array
     */
    public static function definition()
    {
        $definitionOverride = array( 'fields' => array( 'contentclass_attribute' =>
                                                            array( 'name' =>     "ContentClassAttribute",
                                                                   'datatype' => 'mixed' ) ) );
        $definition = array_merge_recursive( parent::definition(), $definitionOverride );
        $definition['class_name'] = __CLASS__;
        unset( $definition['function_attributes']['contentclass_attribute'] );
        return $definition;
    }
}
?>

<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

class eZContentObjectTester extends eZContentObject
{
    public $ClassName;
    public $ClassIdentifier;

    /**
     * Makes the following function attributes field attribtues, for testing purposes :
     *  - class_name
     *  - class_identifier
     *  - main_node_id
     *  - main_parent_node_id
     *
     * @return the overridden definition array
     */
    public static function definition()
    {
        $definitionOverride = array( 'fields' => array( 'class_name' =>
                                                            array( 'name' =>     "LocalClassName",
                                                                   'datatype' => 'mixed' ),
                                                        'class_identifier' =>
                                                            array( 'name' =>     "LocalClassIdentifier",
                                                                   'datatype' => 'mixed' ),
                                                        'main_node_id' =>
                                                            array( 'name' =>     "MainNodeId",
                                                                   'datatype' => 'mixed' ),
                                                        'main_parent_node_id' =>
                                                            array( 'name' =>     "MainParentNodeId",
                                                                   'datatype' => 'mixed' )
                                                      )
                                   );

        $definition = array_merge_recursive( parent::definition(), $definitionOverride );
        $definition['class_name'] = __CLASS__;

        unset( $definition['function_attributes']['class_name'] );
        unset( $definition['function_attributes']['class_identifier'] );
        unset( $definition['function_attributes']['main_node_id'] );
        unset( $definition['function_attributes']['main_parent_node_id'] );

        return $definition;
    }
}
?>

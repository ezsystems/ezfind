<?php

/**
 * File containing the ezrangeoptionSolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

class ezrangeoptionSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $option = $contentObjectAttribute->attribute( 'content' );

        return array(
            'content' => array(
                'name' => $option->attribute( 'name' ),
                'start_value' => $option->attribute( 'start_value' ),
                'stop_value' => $option->attribute( 'stop_value' ),
                'step_value' => $option->attribute( 'step_value' ),
            ),
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

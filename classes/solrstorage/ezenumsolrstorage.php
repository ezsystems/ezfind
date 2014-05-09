<?php

/**
 * File containing the ezenumSolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

class ezenumSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $availableEnumerations = array();
        foreach ( $contentObjectAttribute->content()->ObjectEnumerations  as $enumeration )
        {
            $availableEnumerations[] = array(
                'id' => $enumeration->EnumID,
                'element' => $enumeration->EnumElement,
                'value' => $enumeration->EnumValue
            );
        }

        return array(
            'content' => $availableEnumerations,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

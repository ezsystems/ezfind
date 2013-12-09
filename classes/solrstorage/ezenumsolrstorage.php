<?php

/**
 * File containing the ezenumSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
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

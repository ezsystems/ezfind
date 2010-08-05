<?php

/**
 * File containing the ezoptionSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezoptionSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $content = $contentObjectAttribute->attribute( 'content' );
        $optionArray = array(
            'name' => $content->attribute( 'name' ),
        );

        foreach ( $content->attribute( 'option_list' ) as $value )
        {
            $optionArray['option_list'][] = array(
                'value' => $value['value'],
                'additional_price' => $value['additional_price']
            );
        }

        return array(
            'content' => $optionArray,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

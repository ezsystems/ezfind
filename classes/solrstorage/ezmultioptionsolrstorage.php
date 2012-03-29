<?php

/**
 * File containing the ezmultioptionSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezmultioptionSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $content = $contentObjectAttribute->attribute( 'content' );
        $multioptionArray = array(
            'name' => $content->attribute( 'name' ),
        );

        foreach ( $content->attribute( 'multioption_list' ) as $option )
        {
            $optionArray = array(
                'name' => $option['name'],
                'default_option_id' => $option['default_option_id']
            );
            foreach ( $option['optionlist'] as $value )
            {
                $optionArray['optionlist'][] = array(
                    'value' => $value['value'],
                    'additional_price' => $value['additional_price']
                );
            }
            $multioptionArray['multioption_list'][] = $optionArray;
        }

        return array(
            'content' => $multioptionArray,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

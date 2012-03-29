<?php

/**
 * File containing the ezselectionSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezselectionSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $selectedOptionsList   = array_fill_keys( $contentObjectAttribute->content(), true );
        $availableOptionsArray = $contentObjectAttribute->attribute( 'class_content' );
        $finalAvailableOptions = array();

        foreach ( $availableOptionsArray['options'] as $availableOption )
        {
            if ( isset( $selectedOptionsList[$availableOption['id']] ) )
            {
                $finalAvailableOptions[] = array( 'name' => $availableOption['name'], 'id' => $availableOption['id'] );
            }
        }
        return array(
            'content' => $finalAvailableOptions,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

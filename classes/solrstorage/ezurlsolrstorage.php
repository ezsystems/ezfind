<?php

/**
 * File containing the ezurlSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezurlSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $url = eZURL::fetch( $contentObjectAttribute->attribute( 'data_int' ) );
        return array(
            'content' => array(
                'url' => ( $url instanceof eZURL ) ? $url->attribute( 'url' ) : null,
                'text' => $contentObjectAttribute->attribute( 'data_text' ),
            ),
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

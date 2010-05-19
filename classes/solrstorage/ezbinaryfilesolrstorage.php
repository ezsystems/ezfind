<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezbinaryfileSolrStorage
{

    /**
     *
     */


    function  __construct( )
    {

    }

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @return json encoded string for further processing
     * required first level elements 'method', 'version_format', 'data_type_identifier', 'content'
     * optional first level element is 'rendered' which should store (template) rendered xhtml snippets
     */
    public static function getAttributeContent ( eZContentObjectAttribute $contentObjectAttribute, $contentClassAttribute )
    {

        $dataTypeIdentifier = $contentObjectAttribute->attribute( 'data_type_string' );
        if( !$contentObjectAttribute->hasContent() )
        {
            $content = null;
        }
        else
        {
            $content = $contentObjectAttribute->content()->filePath();
        }

        $target = array(
                
                'content' => $content,
                'has_rendered_content' => false,
                'rendered' => null
                );

        return $target;
    }

}


?>

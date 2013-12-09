<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array with keys 'content', 'has_rendered_content', 'rendered'
     * required first level elements 'method', 'version_format', 'data_type_identifier', 'content'
     * optional first level element is 'rendered' which should store (template) rendered xhtml snippets
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {

        $target = array(
                'content' => $contentObjectAttribute->content(),
                'has_rendered_content' =>false,
                'rendered' => null
                );

        return  $target ;
    }
}

?>

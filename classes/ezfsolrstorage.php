<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 * @todo: see if we need to make this an abstract class to accomodate CouchDB, MongoDB, so API is not frozen
 *        also, perhaps better use dependency injection instead for class attribute specific
 *        handlers to facilitate custom overrides
 *
 * ezfSolrStorage is a helper class to store serialized versions of attribute content
 * meta-data from eZ Publish objects is inserted in its stored form already
 */

class ezfSolrStorage
{

    /**
     *
     */
    const STORAGE_ATTR_FIELD_PREFIX = 'as_';
    const STORAGE_ATTR_FIELD_SUFFIX = '_bst';
    const CONTENT_METHOD_TOSTRING = 'to_string';
    const CONTENT_METHOD_CUSTOM_HANDLER = 'custom_handler';
    const STORAGE_VERSION_FORMAT = '1';

    /* var $handler; */

    function  __construct( )
    {

    }

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @return array for further processing
     */

    public static function getAttributeData ( eZContentObjectAttribute $contentObjectAttribute )
    {
        $dataTypeIdentifier = $contentObjectAttribute->attribute( 'data_type_string' );
        $contentClassAttribute = eZContentClassAttribute::fetch( $contentObjectAttribute->attribute( 'contentclassattribute_id' ) );
        $attributeHandler =  $dataTypeIdentifier . 'SolrStorage';
        // prefill the array with generic metadata first
        $target = array (
            'data_type_identifier' => $dataTypeIdentifier,
            'version_format' => self::STORAGE_VERSION_FORMAT,
            'attribute_identifier' => $contentClassAttribute->attribute( 'identifier' ),
            'has_content' => $contentObjectAttribute->hasContent(),

            );
        if ( class_exists( $attributeHandler ) )
        {
            $attributeContent = call_user_func( array( $attributeHandler, 'getAttributeContent' ),
                     $contentObjectAttribute, $contentClassAttribute );
            return array_merge( $target, $attributeContent, array( 'content_method' => self::CONTENT_METHOD_CUSTOM_HANDLER ) );

        }
        else
        {
            $target = array_merge( $target, array(
                'content_method' => self::CONTENT_METHOD_TOSTRING,
                'content' => $contentObjectAttribute->toString(),
                'has_rendered_content' => false,
                'rendered' => null
                ));
            return $target;
        }
    }

    public static function serializeData ( $attributeData )
    {
            return base64_encode( json_encode( $attributeData ) );
    }

    /**
     *
     * @param string $jsonString
     * @return mixed
     */
    public static function unserializeData ( $storageString )
    {
        // primitive for now, it does not return the content in a general usable form yet
        // could insert code to use fromString methods returning an array for the content part
        return json_decode( base64_decode( $storageString ) , true );

    }

    /**
     *
     * @param string $fieldNameBase
     * @return string Solr field name
     */
    public static function getSolrStorageFieldName( $fieldNameBase )
    {
        return  self::STORAGE_ATTR_FIELD_PREFIX . $fieldNameBase . self::STORAGE_ATTR_FIELD_SUFFIX;
    }
}

?>

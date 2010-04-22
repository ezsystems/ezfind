<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 * @todo: finalize API
 *
 * ezfSolrStorage stores serialized versions of attribute content
 * meta-data from eZ Publish objects is inserted in its stored form already
 */

class ezfSolrStorage
{
    function  __construct()
    {

    }

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @return json encoded string for further processing
     */
    public static function serializeAttribute ( eZContentObjectAttribute $contentObjectAttribute )
    {
        $target = array(
            'method' => self::SERIALIZE_METHOD_TOSTRING,
            'attributedatatype' => $contentObjectAttribute->attribute( 'class_identifier' ),
            'content' => $contentObjectAttribute->toString()
                );
        return json_encode( $target );
    }

    /**
     *
     * @param string $jsonString
     * @return mixed
     */
    public static function unserializeAttribute ( $jsonString )
    {
        // primitive for now, it does not return the content in a general usable form yet
        $attributeContentArray = json_decode( $jsonString, true );
        return $attributeContentArray['content'];
    }

    /**
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return <type> 
     */
    public static function getSolrStorageField( eZContentObjectAttribute $contentObjectAttribute )
    {
        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        return array (
            self::STORAGE_ATTR_FIELD_PREFIX . $classAttribute->attribute('identifier') . self::STORAGE_ATTR_FIELD_SUFFIX,
            base64_encode( self::serializeAttribute( $contentObjectAttribute ) )

        );
    }

    /**
     *
     * @param <type> $fieldValue
     * @return <type> 
     */
    public static function decodeSolrStorageFieldValue ( $fieldValue )
    {
        return ( self::unserializeAttribute( base64_decode( $fieldValue ) ) );
    }

    /**
     *
     * @param string $fieldName
     * @param string $fieldValue the encoded field value
     * @return array with fieldName and the result of deserialize 
     */
    public static function decodeSolrStorageField ( $fieldName, $fieldValue )
    {
        return ( array( $fieldName, self::decodeSolrStorageFieldValue( $fieldValue ) )  );
    }

    const STORAGE_ATTR_FIELD_PREFIX = 'as_';
    const STORAGE_ATTR_FIELD_SUFFIX = '_bst';
    const SERIALIZE_METHOD_TOSTRING = 'tostring';
}

?>

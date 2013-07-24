<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezfSolrDocumentFieldGmapLocation extends ezfSolrDocumentFieldBase
{
    public static $subattributesDefinition = array( self::DEFAULT_SUBATTRIBUTE => 'text',
                                                    'coordinates' => 'geopoint',
                                                    'geohash' => 'geohash',
                                                    'latitude' => 'float',
                                                    'longitude' => 'float' );


    const DEFAULT_SUBATTRIBUTE = 'address';

    function __construct( eZContentObjectAttribute $attribute )
    {
        parent::__construct( $attribute );
    }


    public function getData()
    {
        $data = array();
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $data[self::getFieldName( $contentClassAttribute, self::DEFAULT_SUBATTRIBUTE )] = $this->ContentObjectAttribute->attribute( 'content' )->attribute( 'address' );
        $longitude = $this->ContentObjectAttribute->attribute( 'content' )->attribute( 'longitude' );
        if ( !empty( $longitude) )
        {
            $data[self::getFieldName( $contentClassAttribute, 'longitude' )] = $longitude;
        }
        $latitude = $this->ContentObjectAttribute->attribute( 'content' )->attribute( 'latitude' );
        if ( !empty( $latitude) )
        {
            $data[self::getFieldName( $contentClassAttribute, 'latitude' )] = $latitude;
        }
        if ( !empty( $longitude ) && !empty( $latitude ) )
        {
            $data[self::getFieldName( $contentClassAttribute, 'coordinates' )] = $longitude . ',' . $latitude;
            //almost the same input format, Solr will take care of the conversion to a geohash string
            //disabled for now, need to update Solr.war first
            //$data[self::getFieldName( $contentClassAttribute, 'geohash' )] = $longitude . ' ' . $latitude;
        }
        return $data;

    }

    public static function getFieldName( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = null )
    {
        if ( $subAttribute and
             $subAttribute !== '' and
             array_key_exists( $subAttribute, self::$subattributesDefinition ) and
             $subAttribute != self::DEFAULT_SUBATTRIBUTE )
        {
            return parent::generateSubattributeFieldName( $classAttribute,
                                                          $subAttribute,
                                                          self::$subattributesDefinition[$subAttribute] );
        }
        else
        {
            return parent::generateAttributeFieldName( $classAttribute,
                                                       self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
        }
    }

    public static function getFieldNameList( eZContentClassAttribute $classAttribute, $exclusiveTypeFilter = array() )
    {
        // Generate the list of subfield names.
        $subfields = array();

        //   Handle first the default subattribute
        $subattributesDefinition = self::$subattributesDefinition;
        if ( !in_array( $subattributesDefinition[self::DEFAULT_SUBATTRIBUTE], $exclusiveTypeFilter ) )
        {
            $subfields[] = parent::generateAttributeFieldName( $classAttribute, $subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
        }
        unset( $subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );

        //   Then hanlde all other subattributes
        foreach ( $subattributesDefinition as $name => $type )
        {
            if ( empty( $exclusiveTypeFilter ) or !in_array( $type, $exclusiveTypeFilter ) )
            {
                $subfields[] = parent::generateSubattributeFieldName( $classAttribute, $name, $type );
            }
        }
        return $subfields;
    }
    static function getClassAttributeType( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = 'search' )
    {
        if ( $subAttribute and
             $subAttribute !== '' and
             array_key_exists( $subAttribute, self::$subattributesDefinition ) )
        {
            return self::$subattributesDefinition[$subAttribute];
        }
        else
        {
            return self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE];
        }
    }
}
?>

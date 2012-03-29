<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.1.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2012 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/**
 * File containing the ezfSolrDocumentFieldDummyExample class.
 * This class is provided as an example on how to write a
 * datatype-specific indexing and querying handler. Below
 * are detailed potential usages.
 *
 * Subattributes
 * =============
 * Typically, when a datatype does not contain a simple, scalar value
 * but differentiated data bits. So are the 'ezobjectrelation' and
 * 'ezobjectrelationlist' datatypes.
 * The 'ezimage' datatype is also in this case : it contains a binary file,
 * an alternative text for the image, and metadata for the image ( EXIF, IPTC, XMP, etc.),
 * all of which can be considered as "subattributes".
 * The default search-related handling of an ezimage attribute would be
 * to rely on the metaData() method of the datatype, returning the alternative
 * text entered, if any. An ezimage-specific handler, crafted after the example below
 * would allow using subattributes both when the attribute is being indexed,
 * and when it is being queried. This allows for instance to filter results on an
 * Image's EXIF metadata value.
 *
 *
 * Metadata preprocessing
 * ======================
 * An attribute's metadata may need to be processed prior to being sent
 * to the search engine. The 'ezxmltext' is an example : the formatted text
 * is stored as XML, of which textual data must be extracted. The
 * 'ezfSolrDocumentFieldXML' handler is responsible of this.
 *
 *
 * @package eZFind
 * @see ezfSolrDocumentFieldBase
 */

class ezfSolrDocumentFieldDummyExample extends ezfSolrDocumentFieldBase
{
    /**
     * Contains the definition of subattributes for this given datatype.
     * This associative array takes as key the name of the field, and as value
     * the type. The type must be picked amongst the value present as keys in the
     * following array :
     * ezfSolrDocumentFieldName::$FieldTypeMap
     *
     * WARNING : this definition *must* contain the default attribute's one as well.
     *
     * @see ezfSolrDocumentFieldName::$FieldTypeMap
     * @var array
     */
    public static $subattributesDefinition = array( 'subattribute1' => 'int',
                                                    self::DEFAULT_SUBATTRIBUTE => 'text' );

    /**
     * The name of the default subattribute. It will be used when
     * this field is requested with no subfield refinement.
     *
     * @see ezfSolrDocumentFieldDummyExample::$subattributesDefinition
     * @var string
     */
    const DEFAULT_SUBATTRIBUTE = 'subattribute2';

    /**
     * @see ezfSolrDocumentFieldBase::__construct()
     */
    function __construct( eZContentObjectAttribute $attribute )
    {
        parent::__construct( $attribute );
    }

    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData()
    {
        // @TODO : Extract data from the attribute, and format it as described in the doc link above.
        //         Dummy content here, for testing purposes.
        $data = array();
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        foreach ( self::getFieldNameList( $contentClassAttribute ) as $fieldName )
        {
            $data[$fieldName] = 'DATA_FOR_' . $fieldName;
        }
        return $data;
    }

    /**
     * @see ezfSolrDocumentFieldBase::getFieldName()
     */
    public static function getFieldName( eZContentClassAttribute $classAttribute, $subAttribute = null )
    {
        if ( $subAttribute and
             $subAttribute !== '' and
             array_key_exists( $subAttribute, self::$subattributesDefinition ) and
             $subAttribute != self::DEFAULT_SUBATTRIBUTE )
        {
            // A subattribute was passed
            return parent::generateSubattributeFieldName( $classAttribute,
                                                          $subAttribute,
                                                          self::$subattributesDefinition[$subAttribute] );
        }
        else
        {
            // return the default field name here.
            return parent::generateAttributeFieldName( $classAttribute,
                                                       self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
        }
    }

    /**
     * @see ezfSolrDocumentFieldBase::getFieldNameList()
     */
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

    /**
     * @see ezfSolrDocumentFieldBase::getClassAttributeType()
     */
    static function getClassAttributeType( eZContentClassAttribute $classAttribute, $subAttribute = null )
    {
        if ( $subAttribute and
             $subAttribute !== '' and
             array_key_exists( $subAttribute, self::$subattributesDefinition ) )
        {
            // If a subattribute's type is being explicitly requested :
            return self::$subattributesDefinition[$subAttribute];
        }
        else
        {
            // If no subattribute is passed, return the default subattribute's type :
            return self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE];
        }
    }
}
?>

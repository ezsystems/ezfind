<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
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

/*! \file ezfsolrdocumentfieldbase.php
*/

/**
 * Class ezfSolrDocumentFieldBase. This class handles indexing of data from eZ Publish
 * to native Solr format.
 *
 * Usage:
 * <code>
 * $documentBase = ezfSolrDocumentFieldBase::instance( <contentObjectAttribute> );
 * $documentBase->getData();
 * </code>
 *
 * @property eZContentObjectAttribute $ContentObjectAttribute Instance of eZContentObjectAttribute
 * object.
 */
class ezfSolrDocumentFieldBase
{
    /**
     * Constructor. Use ezfSolrDocumentFieldBase::instance() to create new
     * object of ezfSolrDocumentFieldBase class.
     *
     * @param eZContentObjectAttribute Instance of eZContentObjectAttribute
     */
    function ezfSolrDocumentFieldBase( eZContentObjectAttribute $attribute )
    {
        $this->ContentObjectAttribute = $attribute;
    }

    /**
     * Get data to index, and field name to use. Returns an associative array
     * with field name and field value.
     * Example:
     * <code>
     * array( 'field_name_i' => 123 );
     * </code>
     *
     * @return array Associative array with fieldname and value.
     */
    public function getData()
    {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldName = self::getFieldName( $contentClassAttribute );

        $metaData = $this->ContentObjectAttribute->metaData();
        if ( is_array( $metaData ) )
        {
            $metaData = $this->implode( $metaData );
        }
        $metaData = $this->preProcessValue( $metaData,
                                            self::getClassAttributeType( $contentClassAttribute ) );
        return array( $fieldName => $metaData );
    }

    /**
     * Join array to string ( recursive )
     * Used to convert metadata array to string.
     *
     * @param array Array data
     *
     * @return string String representation of array. Return empty string '' if
     *         the array is empty.
     */
    protected function implode( $array )
    {
        $retString = '';
        if ( empty( $array ) )
        {
            return '';
        }
        foreach( $array as $key => $value )
        {
            if ( is_array( $value ) )
            {
                $value = $this->implode( $value );
            }
            $retString .= $key . ' ' . $value . ' ';
        }

        return $retString;
    }

    /**
     * Check if eZContentObjectAttribute is a collection.
     *
     * @return boolean True if the eZContentObjectAttribute provided in the constructor
     * must be threated like a collection.
     */
    public function isCollection()
    {
        return false;
    }

    /**
     * Get collection data. Returns list of ezfSolrDocumentFieldBase documents.
     *
     * @return array List of ezfSolrDocumentFieldBase objects.
     */
    public function getCollectionData()
    {
        return null;
    }

    /**
     * Get Solr schema field type from eZContentClassAttribute. Available field types are:
     * - string - Unprosessed text
     * - boolean - Boolean
     * - int - Integer, not sortable
     * - long - Long, not sortable
     * - float - Float, not sortable
     * - double - Double, not sortable
     * - sint - Integer, sortable
     * - slong - Long, sortable
     * - sfloat - Float, sortable
     * - sdouble - Double, sortable
     * - date - Date, see also: http://www.w3.org/TR/xmlschema-2/#dateTime
     * - text - Text, processed and allows fuzzy matches.
     * - textTight - Text, less filters are applied than for the text datatype.
     *
     * @param eZContentClassAttribute Instance of eZContentClassAttribute.
     *
     * @return string Field type. Null if no field type is defined.
     */
    static function getClassAttributeType( eZContentClassAttribute $classAttribute )
    {
        $datatypeMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'DatatypeMap' );
        // Check Datatype field map.
        if ( !empty( $datatypeMapList[$classAttribute->attribute( 'data_type_string' )] ) )
        {
            return $datatypeMapList[$classAttribute->attribute( 'data_type_string' )];
        }

        // Return default field.
        return self::$FindINI->variable( 'SolrFieldMapSettings', 'Default' );
    }

    /**
     * Get Field name. Classes extending ezfSolrDocumentFieldBase should extend this functions if
     * they provide custom field names.
     *
     * @param eZContentClassAttribute Instance of eZContentClassAttribute.
     * @param mixed Additional conditions for creating the field name. What
     *              this value may be depends on the Datatype used for the
     *              eZContentClassAttribute. Default value: null.
     *
     * @return string Field name.
     */
    static function getCustomFieldName( eZContentClassAttribute $classAttribute, $options = null )
    {
        return null;
    }

    /**
     * Get Field name
     *
     * @param eZContentClassAttribute Instance of eZContentClassAttribute.
     * @param mixed Additional conditions for creating the field name. What
     *              this value may be depends on the Datatype used for the
     *              eZContentClassAttribute. Default value: null.
     *
     * @return string Field name.
     */
    static function getFieldName( eZContentClassAttribute $classAttribute, $options = null )
    {
        $datatypeString = $classAttribute->attribute( 'data_type_string' );
        $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );
        if ( array_key_exists( $datatypeString, $customMapList ) )
        {
            if ( $returnValue = call_user_func_array( array( $customMapList[$datatypeString], 'getCustomFieldName' ),
                                                      array( $classAttribute, $options ) ) )
            {
                return $returnValue;
            }
        }

        return self::$DocumentFieldName->lookupSchemaName( 'attr_' . $classAttribute->attribute( 'identifier' ),
                                                           self::getClassAttributeType( $classAttribute ) );
    }

    /**
     * Returns instance of ezfSolrDocumentFieldBase based on the eZContentObjectAttribute
     * provided.
     *
     * To override the standard class ezfSolrDocumentFieldBase, specify in the configuration
     * files which sub-class which should be used.
     *
     * @param eZContentObjectAttribute Instance of eZContentObjectAttribute.
     *
     * @return ezfSolrDocumentFieldBase Instance of ezfSolrDocumentFieldBase.
     */
    static function getInstance( eZContentObjectAttribute $objectAttribute )
    {
        $datatypeString = $objectAttribute->attribute( 'data_type_string' );

        // Check if using custom handler.
        $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );
        if ( array_key_exists( $datatypeString, $customMapList ) )
        {
            return new $customMapList[$datatypeString]( $objectAttribute );
        }

        // Return standard handler.
        return new ezfSolrDocumentFieldBase( $objectAttribute );
    }

    /**
     * Preprocess value to make sure it complies to the
     * requirements Solr has to the different field types.
     *
     * @param mixed Value
     * @param string Fielt type
     *
     * @return moxed Processed value
     */
    static function preProcessValue( $value, $fieldType )
    {
        switch( $fieldType )
        {
            case 'date':
            {
                if ( is_numeric( $value ) )
                {
                    $value = self::convertTimestampToDate( $value );
                }
            } break;

            case 'boolean':
            {
                if ( is_numeric( $value ) )
                {
                    $value = $value ? 'true' : 'false';
                }
            } break;

            default:
            {
                // Do nothing yet.
            } break;
        }

        return $value;
    }

    /**
     * Convert timestamp to Solr date
     * See also: http://www.w3.org/TR/xmlschema-2/#dateTime
     *
     * @param int Timestamp
     *
     * @return string Solr datetime
     */
    static function convertTimestampToDate( $timestamp )
    {
        return strftime( '%Y-%m-%dT%TZ', (int)$timestamp );
    }


    /// Vars
    protected $ContentObjectAttribute;
    static $FindINI;
    static $DocumentFieldName;
}

ezfSolrDocumentFieldBase::$FindINI = eZINI::instance( 'ezfind.ini' );
ezfSolrDocumentFieldBase::$DocumentFieldName = new ezfSolrDocumentFieldName();

?>

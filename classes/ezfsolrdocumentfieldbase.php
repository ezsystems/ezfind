<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.2.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2013 eZ Systems AS
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
    function __construct( eZContentObjectAttribute $attribute )
    {
        $this->ContentObjectAttribute = $attribute;
    }

    /**
     * Get data to index, and field name to use.
     *
     * @since ez find 2.2: indexed values to be context aware
     *      different fields may be used for searching, sorting and faceting
     *
     * @return array Associative array with field name and field value.
     *               Field value can be an array.
     *
     * Example 1:
     * <code>
     *   array( 'field_name_i' => 123 );
     * </code>
     *
     * Example 2:
     * <code>
     *   array( 'field_name_i' => array( "1", 2, '3' ) );
     * </code>
     *
     */
    public function getData()
    {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldNameArray = array();
        foreach ( array_keys( eZSolr::$fieldTypeContexts ) as $context )
        {
            $fieldNameArray[] = self::getFieldName( $contentClassAttribute, null, $context );
        }
        $fieldNameArray = array_unique( $fieldNameArray );



        $metaData = $this->ContentObjectAttribute->metaData();
        $processedMetaDataArray = array();
        if ( is_array( $metaData ) )
        {
            $processedMetaDataArray = array();
            foreach ( $metaData as $value )
            {
                $processedMetaDataArray[] = $this->preProcessValue( $value,
                                            self::getClassAttributeType( $contentClassAttribute ) );
            }
        }
        else
        {

            $processedMetaDataArray[] = $this->preProcessValue( $metaData,
                                            self::getClassAttributeType( $contentClassAttribute ) );

        }
        $fields = array();
        foreach ( $fieldNameArray as $fieldName )
        {
            $fields[$fieldName] = $processedMetaDataArray ;
        }
        return $fields;
    }


    /**
     * @deprecated
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
        foreach ( $array as $key => $value )
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
     * @depracated since 2.1
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
     * @deprecated since 2.1
     * Get collection data. Returns list of ezfSolrDocumentFieldBase documents.
     *
     * @return array List of ezfSolrDocumentFieldBase objects.
     */
    public function getCollectionData()
    {
        return null;
    }


    /**
     * Get Solr schema field type from eZContentClassAttribute.
     *
     * Available field types are:
     * - string - Unprocessed text
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
     * @see ezfSolrDocumentFieldName::$FieldTypeMap
     * @param eZContentClassAttribute Instance of eZContentClassAttribute.
     * @param $subAttribute string In case the type of a datatype's sub-attribute is requested,
     *                             the subattribute's name is passed here.
     *
     * @return string Field type. Null if no field type is defined.
     */
    static function getClassAttributeType( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = 'search' )
    {
        // Subattribute-related behaviour here.
        $datatypeString = $classAttribute->attribute( 'data_type_string' );
        $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );

        if ( array_key_exists( $datatypeString, $customMapList ) )
        {
            if ( self::isStaticDelegationAllowed( $customMapList[$datatypeString], 'getClassAttributeType' ) and
                 ( $returnValue = call_user_func_array( array( $customMapList[$datatypeString], 'getClassAttributeType' ),
                                                        array( $classAttribute, $subAttribute, $context ) ) )
               )
            {
                return $returnValue;
            }
        }

        // Fallback #1: single-fielded datatype behaviour here.
        $datatypeMapList = self::$FindINI->variable( 'SolrFieldMapSettings', eZSolr::$fieldTypeContexts[$context] );
        if ( !empty( $datatypeMapList[$classAttribute->attribute( 'data_type_string' )] ) )
        {
            return $datatypeMapList[$classAttribute->attribute( 'data_type_string' )];
        }
        // Fallback #2: search field datatypemap (pre ezfind 2.2 behaviour)
        $datatypeMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'DatatypeMap' );
        if ( !empty( $datatypeMapList[$classAttribute->attribute( 'data_type_string' )] ) )
        {
            return $datatypeMapList[$classAttribute->attribute( 'data_type_string' )];
        }
        // Fallback #3: return default field.
        return self::$FindINI->variable( 'SolrFieldMapSettings', 'Default' );
    }

    /**
     * Gets the list of solr fields for the given content class attribute. Delegates
     * the action to the datatype-specific handler, if any. If none, the datatype has one
     * field only, hence the delegation to the local getFieldName.
     *
     * @param eZContentClassAttribute $classAttribute
     * @param array $exclusiveTypeFilter Array of types ( strings ) which should be excluded
     *                                  from the result.
     *
     * @return array Array of applicable solr field names
     * @see ezfSolrDocumentFieldBase::getFieldName()
     */
    public static function getFieldNameList( eZContentClassAttribute $classAttribute, $exclusiveTypeFilter = array() )
    {
        $datatypeString = $classAttribute->attribute( 'data_type_string' );
        $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );

        if ( array_key_exists( $datatypeString, $customMapList ) )
        {
            if ( self::isStaticDelegationAllowed( $customMapList[$datatypeString], 'getFieldNameList' ) and
                 ( $returnValue = call_user_func_array( array( $customMapList[$datatypeString], 'getFieldNameList' ),
                                                        array( $classAttribute, $exclusiveTypeFilter ) ) )
               )
            {
                return $returnValue;
            }
        }

        // fallback behaviour :
        if ( empty( $exclusiveTypeFilter ) or !in_array( self::getClassAttributeType( $classAttribute ), $exclusiveTypeFilter ) )
            return array( self::getFieldName( $classAttribute ) );
        else
            return array();
    }

    /**
     * @deprecated since 2.1
     *
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
    static function getCustomFieldName( eZContentClassAttribute $classAttribute, $subattribute = null )
    {
        return null;
    }

    /**
     *
     * Get Field name. Classes extending ezfSolrDocumentFieldBase ( per-datatype handlers )
     * should extend this functions if they provide custom field names.
     *
     * @param eZContentClassAttribute $classAttribute Instance of eZContentClassAttribute.
     * @param mixed $subAttribute Typically the 'subattribute' name
     *
     * @return string Fully qualified Solr field name.
     */
    public static function getFieldName( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = 'search' )
    {
        $datatypeString = $classAttribute->attribute( 'data_type_string' );
        $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );

        if ( array_key_exists( $datatypeString, $customMapList ) )
        {
            if ( self::isStaticDelegationAllowed( $customMapList[$datatypeString], 'getFieldName' ) and
                 ( $returnValue = call_user_func_array( array( $customMapList[$datatypeString], 'getFieldName' ),
                                                        array( $classAttribute, $subAttribute, $context ) ) )
               )
            {
                return $returnValue;
            }
        }

        return self::generateAttributeFieldName( $classAttribute, self::getClassAttributeType( $classAttribute, null, $context ) );
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
        if ( isset( self::$singletons[$objectAttribute->attribute( 'id' )] ) )
        {
            return self::$singletons[$objectAttribute->attribute( 'id' )];
        }
        else
        {
            $datatypeString = $objectAttribute->attribute( 'data_type_string' );

            // Check if using custom handler.
            $customMapList = self::$FindINI->variable( 'SolrFieldMapSettings', 'CustomMap' );
            if ( isset( $customMapList[$datatypeString] ) )
            {
                $fieldBaseClass = $customMapList[$datatypeString];
                if ( class_exists( $fieldBaseClass ) )
                {
                    self::$singletons[$objectAttribute->attribute( 'id' )] = new $customMapList[$datatypeString]( $objectAttribute );
                    return self::$singletons[$objectAttribute->attribute( 'id' )];
                }
                else
                {
                    eZDebug::writeError( "Unknown document field base class '$fieldBaseClass' for datatype '$datatypeString', check your ezfind.ini configuration", __METHOD__ );
                }
            }

            // Return standard handler.
            self::$singletons[$objectAttribute->attribute( 'id' )] = new ezfSolrDocumentFieldBase( $objectAttribute );
            return self::$singletons[$objectAttribute->attribute( 'id' )];
        }
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
                // Flag this as not to index
                else
                {
                    $value = null;
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
        return strftime( '%Y-%m-%dT%H:%M:%SZ', (int)$timestamp );
    }


    /**
     * Generates the full Solr field name for a datatype's subattribute.
     * Helper method to be used, if needed, by datatype-specific handlers.
     *
     * @see ezfSolrDocumentFieldDummyExample
     *
     * @param eZContentClassAttribute $classAttribute
     * @param string $subfieldName
     * @param string $type The fully qualified type. It must be picked amongst
     *                     the keys of the ezfSolrDocumentFieldName::$FieldTypeMap array.
     *
     * @return string
     */
    public static function generateSubattributeFieldName( eZContentClassAttribute $classAttribute, $subfieldName, $type )
    {
        // base name of subfields ends with self::SUBATTR_FIELD_PREFIX so
        // that it's possible to differentiate those fields in schema.xml
        return self::$DocumentFieldName->lookupSchemaName( self::SUBATTR_FIELD_PREFIX . $classAttribute->attribute( 'identifier' )
                                                            . self::SUBATTR_FIELD_SEPARATOR . $subfieldName . self::SUBATTR_FIELD_SEPARATOR,
                                                           $type );
    }

    /**
     * Generates the full Solr field name for an attribute.
     * Helper method to be used, if needed, by datatype-specific handlers.
     *
     * @see ezfSolrDocumentFieldDummyExample
     *
     * @param eZContentClassAttribute $classAttribute
     * @param string $type The fully qualified type. It must be picked amongst
     *                     the keys of the ezfSolrDocumentFieldName::$FieldTypeMap array.
     *
     * @return string
     */
    public static function generateAttributeFieldName( eZContentClassAttribute $classAttribute, $type )
    {
        return self::$DocumentFieldName->lookupSchemaName( self::ATTR_FIELD_PREFIX . $classAttribute->attribute( 'identifier' ),
                                                           $type );
    }

    /**
     * Generates the full Solr field name for a metadata attribute.
     * Helper method to be used, if needed, by datatype-specific handlers.
     * It is used in the search plugin eZSolr.
     *
     * @param string $baseName
     * @return string
     *
     * @example
     *      If $baseName equals 'main_url_alias',
     *      the return value will be : 'meta_main_url_alias_s'
     */
    public static function generateMetaFieldName( $baseName, $context = 'search' )
    {
        return self::$DocumentFieldName->lookupSchemaName( self::META_FIELD_PREFIX . $baseName,
                                                           eZSolr::getMetaAttributeType( $baseName, $context ) );
    }

    /**
     * Generates the full Solr field name for a metadata subattribute.
     * Helper method to be used, if needed, by datatype-specific handlers.
     * Used particularly when indexing metadata of a related object.
     *
     * @param string $baseName
     * @param eZContentClassAttribute $classAttribute
     * @return string
     *
     * @example
     *      If $baseName equals 'main_url_alias', and $classAttribute
     *      has as identifier 'dummy', the return value will be :
     *      'submeta_dummy-main_url_alias_s'
     *
     * @see ezfSolrDocumentFieldObjectRelation
     */
    public static function generateSubmetaFieldName( $baseName, eZContentClassAttribute $classAttribute )
    {
        return self::$DocumentFieldName->lookupSchemaName( self::SUBMETA_FIELD_PREFIX . $classAttribute->attribute( 'identifier' ) . self::SUBATTR_FIELD_SEPARATOR . $baseName,
                                                           eZSolr::getMetaAttributeType( $baseName ) );
    }

    /**
     * Checks whether a given method is actually declared in a given class.
     * This is primarily made to avoid infinite recursion. This walks around
     * a suboptimal object design, mixing static and dynamic methods in the same
     * class. @FIXME.
     *
     * @see ezfSolrDocumentFieldBase::getFieldNameList()
     *
     * @param $delegationClass string the class static delegation should be offered to
     * @param $method string the method static delegation applies to
     *
     * @return boolean true if delegation is allowed, false otherwise.
     */
    public static function isStaticDelegationAllowed( $delegationClass, $method )
    {
        $m = new ReflectionMethod( $delegationClass, $method );
        if ( $m->getDeclaringClass()->name === $delegationClass )
            return true;
        else
            return false;
    }

    /// Vars
    public $ContentObjectAttribute;
    static $FindINI;
    static $DocumentFieldName;

    /**
     * Registry storing singletons for the getInstance method.
     *
     * @var array
     * @see ezfSolrDocumentFieldBase::getInstance()
     */
    public static $singletons = array();

    /**
     * Prefix for metadata field names in Solr.
     */
    const META_FIELD_PREFIX = 'meta_';

    /**
     * Prefix for metadata subfield names in Solr.
     *
     * @example
     *      Metadata attributes of a related object will be indexed
     *      as submetas in a Solr document, their names starting
     *      with 'submeta_' instead of '_meta'
     */
    const SUBMETA_FIELD_PREFIX = 'submeta_';

    /**
     * Prefix for attribute field names in Solr.
     */
    const ATTR_FIELD_PREFIX = 'attr_';

    /**
     * Prefix for subattribute field names in Solr.
     *
     * @example
     *      Content attributes of a related object will be indexed
     *      as subattributes in a Solr document, their names starting
     *      with 'subattr_' instead of '_attr'
     */
    const SUBATTR_FIELD_PREFIX = 'subattr_';

    /**
     * Separator for sub attributes
     * Since eZ Find 2.3, this is changed to a lucene query syntax friendly string
     * which is important for function queries and some internal Solr calls
     * as well
     */
    const SUBATTR_FIELD_SEPARATOR = '___';
}

ezfSolrDocumentFieldBase::$FindINI = eZINI::instance( 'ezfind.ini' );
ezfSolrDocumentFieldBase::$DocumentFieldName = new ezfSolrDocumentFieldName();

?>

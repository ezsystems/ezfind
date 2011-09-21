<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
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
 * File containing the ezfSolrDocumentFieldObjectRelation class.
 *
 * @package eZFind
 */
/**
 * The ezfSolrDocumentFieldObjectRelation class handles indexing and
 * querying for the ezobjectrelation and ezobjectrelationlist in eZFind.
 *
 * @package eZFind
 */
class ezfSolrDocumentFieldObjectRelation extends ezfSolrDocumentFieldBase
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
    public static $subattributesDefinition = array( self::DEFAULT_SUBATTRIBUTE => 'text' );

    /**
     * The name of the default subattribute.
     * Will contain the textual representation of all of the related object(s)
     * fields.
     *
     * @var string
     */
    const DEFAULT_SUBATTRIBUTE = 'full_text_field';


    /**
     * @deprecated since eZ Find 2.1
     * Get collection data. Returns list of ezfSolrDocumentFieldBase documents.
     *
     * @return array List of ezfSolrDocumentFieldBase objects.
     */
    public function getCollectionData()
    {
        $returnList = array();
        switch( $this->ContentObjectAttribute->attribute( 'data_type_string' ) )
        {
            case 'ezobjectrelation':
            {
                $returnList = $this->getBaseList( $this->ContentObjectAttribute->attribute( 'object_version' ) );
            } break;

            case 'ezobjectrelationlist':
            {
                $content = $this->ContentObjectAttribute->content();
                foreach ( $content['relation_list'] as $relationItem )
                {
                    $subObjectID = $relationItem['contentobject_id'];
                    if ( !$subObjectID )
                        continue;
                    $subObject = eZContentObjectVersion::fetchVersion( $relationItem['contentobject_version'], $subObjectID );
                    if ( !$subObject )
                        continue;

                    $returnList = array_merge( $this->getBaseList( $subObject ),
                                               $returnList );
                }
            } break;
        }

        return $returnList;
    }


    /**
     * @see ezfSolrDocumentFieldBase::getFieldName()
     *
     * @todo Implement this
     */
    public static function getFieldName( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = 'search' )
    {
        switch ( $classAttribute->attribute( 'data_type_string' ) )
        {
        	case 'ezobjectrelation' :
        	{
        	    // Optimistic name generation here : assume the $subAttribute value actually matches
                // one of the related object's attributes name. Hence the commented out line in the first "if" below.
                if ( $subAttribute and
                     $subAttribute !== '' and
                     //array_key_exists( $subAttribute, self::$subattributesDefinition ) and
                     $subAttribute != self::DEFAULT_SUBATTRIBUTE and
                     ( $type = self::getTypeForSubattribute( $classAttribute, $subAttribute, $context ) ) )
                {
                    // A subattribute was passed
                    return parent::generateSubattributeFieldName( $classAttribute,
                                                                  $subAttribute,
                                                                  $type );
                }
                else
                {
                    // return the default field name here.
                    return parent::generateAttributeFieldName( $classAttribute,
                                                               self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                }
        	} break;

        	case 'ezobjectrelationlist' :
    	    {

    	    } break;

        	default:
        	{} break;
        }
    }

    /**
     * Identifies, based on the existing object relations, the type of the subattribute.
     *
     * @param eZContentClassAttribute $classAttribute The ezobjectrelation/ezobjectrelationlist attribute
     * @param $subAttribute The subattribute's name
     * @return string The type of the subattribute, false otherwise.
     */
    protected static function getTypeForSubattribute( eZContentClassAttribute $classAttribute, $subAttribute, $context = 'search'  )
    {
        $q = "SELECT DISTINCT( ezcoa.data_type_string )
                FROM   ezcontentobject_link AS ezcol,
                       ezcontentobject_attribute AS ezcoa,
                       ezcontentclass_attribute AS ezcca,
                       ezcontentclass_attribute AS ezcca_target
                WHERE  ezcol.contentclassattribute_id={$classAttribute->attribute( 'id' )}
                  AND  ezcca_target.identifier='{$subAttribute}'
                  AND  ezcca.data_type_string='{$classAttribute->attribute( 'data_type_string' )}'
                  AND  ezcca.id=ezcol.contentclassattribute_id
                  AND  ezcol.to_contentobject_id = ezcoa.contentobject_id
                  AND  ezcoa.contentclassattribute_id = ezcca_target.id;
        ";
        $rows = eZDB::instance()->arrayQuery( $q );
        if ( $rows and count( $rows ) > 0 )
        {
            if ( count( $rows ) > 1 )
            {
                $msg = "Multiple types were found for subattribute '{$subAttribute}' of
                class attribute #{$classAttribute->attribute( 'id' )} [{$classAttribute->attribute( 'data_type_string' )}].
                This means that objects of different content classes were related through class attribute #{$classAttribute->attribute( 'id' )}
                and had attributes named '{$subAttribute}' of different datatypes : \n"
                . print_r( $rows , true ) .
                " Picking the first one here : {$rows[0]['data_type_string']}";
                eZDebug::writeWarning( $msg,  __METHOD__ );
            }
            return ezfSolrDocumentFieldBase::getClassAttributeType( new eZContentClassAttribute( $rows[0] ), null, $context );
        }
        return false;
    }

    /**
     * @see ezfSolrDocumentFieldBase::getFieldNameList()
     *
     * @todo Implement this
     */
    public static function getFieldNameList( eZContentClassAttribute $classAttribute, $exclusiveTypeFilter = array() )
    {
        return false;
    }

    /**
     * Extracts textual representation of a related content object. Used to populate a
     * default, full-text search field for an ezobjectrelation/ezobjectrelationlist
     * content object attribute.
     *
     * @return string The string representation of the related eZContentObject(s),
     *                then indexed in Solr.
     * @param eZContentObjectAttribute $contentObjectAttribute The ezobjectrelation/ezobjectrelationlist
     *                                                         textual representation shall be extracted from.
     */
    protected function getPlainTextRepresentation( eZContentObjectAttribute $contentObjectAttribute = null )
    {
        if ( $contentObjectAttribute === null )
        {
            $contentObjectAttribute = $this->ContentObjectAttribute;
        }

        $metaData = '';

        if ( $contentObjectAttribute )
        {
            $metaDataArray = $contentObjectAttribute->metaData();

            if ( !is_array( $metaDataArray ) )
                $metaDataArray = array( $metaDataArray );

            foreach ( $metaDataArray as $item )
            {
                $metaData .= $item['text'] . ' ';
            }
        }
        return trim( $metaData, "\t\r\n " );
    }

    /**
     * @see ezfSolrDocumentFieldBase::getClassAttributeType
     *
     * @todo Implement specific behaviour and context aware typing
     */
    static function getClassAttributeType( eZContentClassAttribute $classAttribute, $subAttribute = null, $context = 'search' )
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

    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData()
    {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );

        switch ( $contentClassAttribute->attribute( 'data_type_string' ) )
        {
            case 'ezobjectrelation' :
                $returnArray = array();

                $defaultFieldName = parent::generateAttributeFieldName( $contentClassAttribute,
                                                                        self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                $returnArray[$defaultFieldName] = $this->getPlainTextRepresentation();
                $relatedObject = $this->ContentObjectAttribute->content();

                if ( $relatedObject && $relatedObject->attribute( 'status' ) == eZContentObject::STATUS_PUBLISHED )
                {
                    // 1st, add content fields of the related object.
                    $baseList = $this->getBaseList( $relatedObject->attribute( 'current' ) );

                    foreach ( $baseList as $field )
                    {
                        $tmpClassAttribute = $field->ContentObjectAttribute->attribute( 'contentclass_attribute' );
                        $fieldName = $field->ContentObjectAttribute->attribute( 'contentclass_attribute_identifier' );
                        $fieldName = parent::generateSubattributeFieldName( $contentClassAttribute,
                                                                            $fieldName,
                                                                            self::getClassAttributeType( $tmpClassAttribute ) );

                        $finalValue = '';
                        if ( $tmpClassAttribute->attribute( 'data_type_string' ) == 'ezobjectrelation' or
                             $tmpClassAttribute->attribute( 'data_type_string' ) == 'ezobjectrelationlist' )
                        {
                            // The subattribute is in turn an object relation. Stop recursion and get full text representation.
                            $finalValue = $field->getPlainTextRepresentation();
                        }
                        else
                        {
                            $values = array_values( $field->getData() );
                            foreach ( $values as $value )
                            {
                                if ( is_array( $value ) )
                                {
                                    $finalValue .= implode( ' ', $value );
                                }
                                else
                                {
                                    $finalValue .= ' ' . $value;
                                }
                            }
                        }

                        $returnArray[$fieldName] = trim( $finalValue, "\t\r\n " );
                    }

                    // 2ndly, add meta fields of the related object.
                    $metaAttributeValues = eZSolr::getMetaAttributesForObject( $relatedObject );
                    foreach ( $metaAttributeValues as $metaInfo )
                    {
                        $returnArray[ezfSolrDocumentFieldBase::generateSubmetaFieldName( $metaInfo['name'], $contentClassAttribute )] = ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] );
                    }

                    return $returnArray;
                }
                break;
            case 'ezobjectrelationlist' :
            {
                $returnArray = array();
                $content = $this->ContentObjectAttribute->content();

                foreach ( $content['relation_list'] as $relationItem )
                {
                    $subObjectID = $relationItem['contentobject_id'];
                    if ( !$subObjectID )
                        continue;
                    $subObject = eZContentObjectVersion::fetchVersion( $relationItem['contentobject_version'], $subObjectID );
                    if ( !$subObject || $relationItem['in_trash'] )
                        continue;

                    // 1st create aggregated metadata fields
                    $metaAttributeValues = eZSolr::getMetaAttributesForObject( $subObject->attribute( 'contentobject' ) );
                    foreach ( $metaAttributeValues as $metaInfo )
                    {
                        $submetaFieldName = ezfSolrDocumentFieldBase::generateSubmetaFieldName( $metaInfo['name'], $contentClassAttribute );
                        if ( isset( $returnArray[$submetaFieldName] ) )
                        {
                            $returnArray[$submetaFieldName] = array_merge( $returnArray[$submetaFieldName], array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) ) );
                        }
                        else
                        {
                            $returnArray[$submetaFieldName] = array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
                        }
                    }
                }

                $defaultFieldName = parent::generateAttributeFieldName( $contentClassAttribute,
                                                                        self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                $returnArray[$defaultFieldName] = $this->getPlainTextRepresentation();
                return $returnArray;
            }
                break;
            default:
            {
            } break;
        }
    }

    /**
     * Get ezfSolrDocumentFieldBase instances for all attributes of specified eZContentObjectVersion
     *
     * @param eZContentObjectVersion Instance of eZContentObjectVersion to fetch attributes from.
     *
     * @return array List of ezfSolrDocumentFieldBase instances.
     */
    function getBaseList( eZContentObjectVersion $objectVersion )
    {
        $returnList = array();
        // Get ezfSolrDocumentFieldBase instance for all attributes in related object
        if ( eZContentObject::recursionProtect( $this->ContentObjectAttribute->attribute( 'contentobject_id' ) ) )
        {
            foreach ( $objectVersion->contentObjectAttributes( $this->ContentObjectAttribute->attribute( 'language_code' ) ) as $attribute )
            {
                if ( $attribute->attribute( 'contentclass_attribute' )->attribute( 'is_searchable' ) )
                {
                    $returnList[] = ezfSolrDocumentFieldBase::getInstance( $attribute );
                }
            }
        }
        return $returnList;
    }
}

?>
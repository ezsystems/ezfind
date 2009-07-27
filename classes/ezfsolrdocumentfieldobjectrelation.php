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

/*! \file ezfsolrdocumentfieldobjectrelation.php
*/

/*!
  \class ezfSolrDocumentFieldObjectRelation ezfsolrdocumentfieldobjectrelation.php
  \brief The class ezfSolrDocumentFieldObjectRelation does

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
                foreach( $content['relation_list'] as $relationItem )
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
        $metaDataArray = $contentObjectAttribute->metaData();

        if( !is_array( $metaDataArray ) )
            $metaDataArray = array( $metaDataArray );

        foreach( $metaDataArray as $item )
        {
            $metaData .= $item['text'] . ' ';
        }

        return trim( $metaData, "\t\r\n " );
    }

    /**
     * @see ezfSolrDocumentFieldBase::getClassAttributeType
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

                if ( $relatedObject )
                {
                    $baseList = $this->getBaseList( $relatedObject->attribute( 'current' ) );

                    // Add content fields of the related object.
                    // @TODO : handle meta fields. Requires a refactoring of the eZSolr::addObject method.
                    foreach( $baseList as $field )
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
                    return $returnArray;
                }

            case 'ezobjectrelationlist' :
            {
                /*
                $content = $this->ContentObjectAttribute->content();
                foreach( $content['relation_list'] as $relationItem )
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
                */
                $defaultFieldName = parent::generateAttributeFieldName( $contentClassAttribute,
                                                                        self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                $returnArray[$defaultFieldName] = $this->getPlainTextRepresentation();
                return $returnArray;
            };
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
            foreach( $objectVersion->contentObjectAttributes( $this->ContentObjectAttribute->attribute( 'language_code' ) ) as $attribute )
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

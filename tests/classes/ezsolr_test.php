<?php
/**
 * Test suite for eZSolr
 **/
class eZSolrTest extends ezpDatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

   /**
    * Data provider for testGetFieldName
    **/
    public static function providerTestGetFieldName()
    {
        $providerArray = array();

        $providerArray[] = array(
            ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t',
            'article/title'
        );

        if ( $contentClass = eZContentClass::fetchByIdentifier( 'article' ) )
        {
            $contentClassId = $contentClass->attribute( 'id' );
            $providerArray[] = array(
                array( 'fieldName' => ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t',
                       'contentClassId' => $contentClassId ),
                'article/title',
                true
            );
        }

        /*
        if ( $contentClass = eZContentClass::fetchByIdentifier( 'article' ) )
        {
            $providerArray[] = array(
                ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . 'image-alternative_text_t',
                'article/image/alternative_text'
            );
        }
        */
        return $providerArray;
    }

    /**
     * @dataProvider providerTestGetFieldName
     **/
    public function testGetFieldName( $expected, $baseName, $includingClassID = false )
    {
        self::assertEquals(
            $expected,
            eZSolr::getFieldName( $baseName, $includingClassID )
        );
    }


   /**
    * Data provider for testGetMetaAttributesForObject
    **/
    public static function providerTestGetMetaAttributesForObject()
    {
        $providerArray = array();

        # 1:start
        $object1 = new eZContentObjectTester( array(   'id'                  => 'id-value',
                                                       'class_name'          => 'class_name-value',
                                                       'section_id'          => 'section_id-value',
                                                       'owner_id'            => 'owner_id-value',
                                                       'contentclass_id'     => 'contentclass_id-value',
                                                       'current_version'     => 'current_version-value',
                                                       'remote_id'           => 'remote_id-value',
                                                       'class_identifier'    => 'class_identifier-value',
                                                       'main_node_id'        => 'main_node_id-value',
                                                       'modified'            => 'modified-value',
                                                       'published'           => 'published-value',
                                                       'main_parent_node_id' => 'main_parent_node_id-value'
                                                    ) );
        $expected1 = array(
                        array( 'name'      => 'id',
                               'value'     => 'id-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'class_name',
                               'value'     => 'class_name-value',
                               'fieldType' => 'text'
                             ),
                        array( 'name'      => 'section_id',
                               'value'     => 'section_id-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'owner_id',
                               'value'     => 'owner_id-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'contentclass_id',
                               'value'     => 'contentclass_id-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'current_version',
                               'value'     => 'current_version-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'remote_id',
                               'value'     => 'remote_id-value',
                               'fieldType' => 'string'
                             ),
                        array( 'name'      => 'class_identifier',
                               'value'     => 'class_identifier-value',
                               'fieldType' => 'string'
                             ),
                        array( 'name'      => 'main_node_id',
                               'value'     => 'main_node_id-value',
                               'fieldType' => 'sint'
                             ),
                        array( 'name'      => 'modified',
                               'value'     => 'modified-value',
                               'fieldType' => 'date'
                             ),
                        array( 'name'      => 'published',
                               'value'     => 'published-value',
                               'fieldType' => 'date'
                             ),
                        array( 'name'      => 'main_parent_node_id',
                               'value'     => 'main_parent_node_id-value',
                               'fieldType' => 'sint'
                             )
                          );

        $providerArray[] = array( $expected1, $object1 );
        # 1:end

        return $providerArray;
    }

    /**
     * @dataProvider providerTestGetMetaAttributesForObject
     **/
    public function testGetMetaAttributesForObject( $expected, $contentObject  )
    {
        self::assertEquals(
            $expected,
            eZSolr::getMetaAttributesForObject( $contentObject )
        );
    }
}
?>

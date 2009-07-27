<?php
/**
 * Test suite for ezfSolrDocumentFieldBase
 **/
class ezfSolrDocumentFieldBaseTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    public function setUp()
    {
        // Enabled delayed indexing in order not to index support objects
        // ( the ones used for testing ezfSolrDocumentFieldObjectRelation::getData() for instance )
        $siteINI = eZINI::instance( 'site.ini' );
        $siteINI->setVariable( 'SearchSettings', 'DelayedIndexing', 'enabled' );
        parent::setUp();
    }

    /**
     * Testing the 'singleton' aspect of the getInstance() method
     **/
    public function testGetInstanceSingleton()
    {
        $ezcoa1 = new eZContentObjectAttribute( array( "data_type_string" => 'ezstring',
                                                       "id" => 100 ) );
        $field1 = ezfSolrDocumentFieldBase::getInstance( $ezcoa1 );
        $field2 = ezfSolrDocumentFieldBase::getInstance( $ezcoa1 );

        self::assertSame(
            $field1,
            $field2
        );
    }

    /**
    * Data provider for testGetInstance
    **/
    public static function providerTestGetInstance()
    {
        $ezcoa1 = new eZContentObjectAttribute( array( "data_type_string" => 'ezstring',
                                                       "id" => 100 ) );
        $field1 = new ezfSolrDocumentFieldBase( $ezcoa1 );

        $ezcoa2 = new eZContentObjectAttribute( array( "data_type_string" => 'ezobjectrelation',
                                                       "id" => 101 ) );
        $ezcoa3 = new eZContentObjectAttribute( array( "data_type_string" => 'ezobjectrelationlist',
                                                       "id" => 102 ) );
        $field2 = new ezfSolrDocumentFieldObjectRelation( $ezcoa2 );
        $field3 = new ezfSolrDocumentFieldObjectRelation( $ezcoa3 );

        $ezcoa4 = new eZContentObjectAttribute( array( "data_type_string" => 'ezxmltext',
                                                       "id" => 103 ) );
        $ezcoa5 = new eZContentObjectAttribute( array( "data_type_string" => 'ezmatrix',
                                                       "id" => 104 ) );
        $field4 = new ezfSolrDocumentFieldXML( $ezcoa4 );
        $field5 = new ezfSolrDocumentFieldXML( $ezcoa5 );


        return array(
            array( $ezcoa1, $field1 ),
            array( $ezcoa2, $field2 ),
            array( $ezcoa3, $field3 ),
            array( $ezcoa4, $field4 ),
            array( $ezcoa5, $field5 ),
            // testing if the singleton system is working : replaying an already played param set :
            array( $ezcoa1, $field1 )
        );
    }

    /**
     * @dataProvider providerTestGetInstance
     **/
    public function testGetInstance( $eZContentObjectAttribute, $expected )
    {
        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::getInstance( $eZContentObjectAttribute )
        );
    }


    /**
     * test for getFieldName()
     */
    public function testGetFieldName()
    {
        $providerArray = array();

        $ezcca1 = new eZContentClassAttribute( array( 'identifier'        => 'title' ,
                                                      'data_type_string'  => 'ezstring' ) );
        $expected1 = ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t';
        $providerArray[] = array( $expected1, $ezcca1, null );


        // Testing the default subattribute
        $ezcca2 = new eZContentClassAttribute( array( 'identifier'        => 'dummy' ,
                                                      'data_type_string'  => 'dummy_example' ) );
        $expected2 = ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'dummy_t';
        $providerArray[] = array( $expected2, $ezcca2, null );


        //Testing the class/attribute/subattribute syntax
        $ezcca3 = $ezcca2;
        $expected3 = ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . 'dummy-subattribute2_t';
        $options3 = 'subattribute2';
        $providerArray[] = array( $expected3, $ezcca3, $options3 );


        //Testing the class/attribute/subattribute syntax for ezobjectrelation attributes
        $time4 = time();
        $image4 = new ezpObject( "image", 2 );
        $image4->name = __METHOD__ . $time4;
        $image4->caption = __METHOD__ . $time4;
        $imageId4 = $image4->publish();
        $ezcca4 = new eZContentClassAttribute( array( 'identifier'        => 'image' ,
                                                      'data_type_string'  => 'ezobjectrelation',
                                                      'data_int'          => $imageId4 ) );
        $expected4 = ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . 'image-name_t';
        $options4 = 'name';
        $providerArray[] = array( $expected4, $ezcca4, $options4 );


        // perform actual testing
        foreach ( $providerArray as $input )
        {
            $expected = $input[0];
            $contentClassAttribute = $input[1];
            $options = $input[2];

            self::assertEquals(
                $expected,
                ezfSolrDocumentFieldBase::getFieldName( $contentClassAttribute, $options )
            );
        }
    }

    /**
     * Data provider testGetFieldNameList()
     */
    public function providerTestGetFieldNameList()
    {
        $ezcca1 = new eZContentClassAttribute( array( 'identifier'        => 'title' ,
                                                      'data_type_string'  => 'ezstring' ) );
        $expected1 = array( ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t' );

        $ezcca2 = new eZContentClassAttribute( array( 'identifier'        => 'dummy' ,
                                                      'data_type_string'  => 'dummy_example' ) );
        $expected2 = array( ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'dummy_t',
                            ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . 'dummy-subattribute1_i' );

        return array(
            array( $expected1, $ezcca1 ),
            array( $expected2, $ezcca2 )
        );
    }

    /**
     * @dataProvider providerTestGetFieldNameList()
     */
    public function testGetFieldNameList( $expected, $contentClassAttribute )
    {
        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::getFieldNameList( $contentClassAttribute )
        );
    }

    /**
     * Data provider testGetFieldNameList()
     */
    public function providerTestGetClassAttributeType()
    {
        $provider = array();

        $ezcca1 = new eZContentClassAttribute( array( 'data_type_string'  => 'ezstring' ) );
        $expected1 = 'text';
        $provider[] = array( $expected1, $ezcca1 );



        // Testing the 'type' of the default subattribute for a datatype containing subattributes
        $ezcca2 = new eZContentClassAttribute( array( 'data_type_string'  => 'dummy_example' ) );
        $expected2 = 'text';
        $provider[] = array( $expected2, $ezcca2 );

        // Testing the 'type' of an explicitly specified subattribute
        $ezcca3 = $ezcca2;
        $expected3 = 'int';
        $options3 = 'subattribute1';
        $provider[] = array( $expected3, $ezcca3, $options3 );

        return $provider;
    }

    /**
     * @dataProvider providerTestGetClassAttributeType()
     */
    public function testGetClassAttributeType( $expected, $contentClassAttribute, $options = null )
    {
        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::getClassAttributeType( $contentClassAttribute, $options )
        );
    }

    /**
     * test for generateAttributeFieldName()
     */
    public function testGenerateAttributeFieldName()
    {
        $identifier = "dummy";
        $expected = ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . $identifier . '_t';
        $classAttribute = new eZContentClassAttribute( array( 'identifier' => $identifier ) );
        $type = 'text';

        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::generateAttributeFieldName( $classAttribute, $type )
        );
    }

    /**
     * test for generateSubattributeFieldName()
     */
    public function testGenerateSubattributeFieldName()
    {
        $identifier = "dummy";
        $subattributeName = 'subattribute1';
        $expected = ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . $identifier . '-' . $subattributeName .  '_t';
        $classAttribute = new eZContentClassAttribute( array( 'identifier' => $identifier ) );
        $type = 'text';

        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::generateSubattributeFieldName( $classAttribute, $subattributeName, $type )
        );
    }

    /**
     * test for getData()
     */
    public function testGetData()
    {
        $providerArray = array();

        #start 1 : the simplest attribute
        $content1 = "Hello world";
        $ezcca1 = new eZContentClassAttribute( array( 'identifier'        => 'title' ,
                                                      'data_type_string'  => 'ezstring' ) );
        $ezcoa1 = new eZContentObjectAttributeTester( array( "data_type_string" => 'ezstring',
                                                             "id" => 100123,
                                                             "data_text" => $content1,
                                                             "contentclass_attribute" => $ezcca1 ) );

        $fieldName1 = ezfSolrDocumentFieldBase::getFieldName( $ezcca1 );
        $expectedData1 = array( $fieldName1 => $content1 );
        $providerArray[] = array( $expectedData1, $ezcoa1 );
        #end 1


        #start 2 : attribute with subattributes
        $ezcca2 = new eZContentClassAttribute( array( 'identifier'        => 'dummy' ,
                                                      'data_type_string'  => 'dummy_example' ) );
        $ezcoa2 = new eZContentObjectAttributeTester( array( "data_type_string" => 'dummy_example',
                                                             "id" => 100124,
                                                             "contentclass_attribute" => $ezcca2 ) );
        $fieldList2 = ezfSolrDocumentFieldBase::getFieldNameList( $ezcca2 );
        $expectedData2 = array();
        foreach( $fieldList2 as $fieldName )
        {
            $expectedData2[$fieldName] = 'DATA_FOR_' . $fieldName;
        }

        $fieldName2 = ezfSolrDocumentFieldBase::getFieldName( $ezcca2 );
        $providerArray[] = array( $expectedData2, $ezcoa2 );
        #end 2

        #start 3 : object relations
        $expectedData3 = array();
        $tester3 = new ezfSolrDocumentFieldObjectRelationTester( new eZContentObjectAttribute( array() ) );
        $time3 = time();
        $image3 = new ezpObject( "image", 2 );
        $image3->name = __METHOD__ . $time3;
        $image3->caption = __METHOD__ . $time3;
        $imageId3 = $image3->publish();
        // $image3->object->clearCache();
        $dataMapImage3 = $image3->dataMap;

        // eZContentObjectAttribute objects, attributes of the related Image
        $imageName3 = $dataMapImage3['name'];
        $imageCaption3 = $dataMapImage3['caption'];

        $article3 = new ezpObject( "article", 2 );
        $articleId3 = $article3->publish();

        $article3->object->addContentObjectRelation( $imageId3, $article3->current_version, 154, eZContentObject::RELATION_ATTRIBUTE );

        $dataMapArticle3 = $article3->attribute( 'data_map' );
        $ezcoa3 = $dataMapArticle3['image'];
        $ezcoa3->setAttribute( 'data_int', $imageId3 );
        $ezcoa3->store();

        $ezcca3 = $ezcoa3->attribute( 'contentclass_attribute' );
        $defaultFieldName3 = ezfSolrDocumentFieldBase::generateAttributeFieldName( $ezcca3,
                                                                ezfSolrDocumentFieldObjectRelation::$subattributesDefinition[ezfSolrDocumentFieldObjectRelation::DEFAULT_SUBATTRIBUTE] );

        $expectedData3[$defaultFieldName3] = $tester3->getPlainTextRepresentation( $ezcoa3 );
        // required to allow another call to metaData()
        // on $ezcoa3 in getPlainTextRepresentation, called from the getData() method :
        eZContentObject::recursionProtectionEnd();

        $fieldNameImageName3 = ezfSolrDocumentFieldBase::generateSubattributeFieldName( $ezcca3,
                                                            $imageName3->attribute( 'contentclass_attribute_identifier' ),
                                                            ezfSolrDocumentFieldObjectRelation::getClassAttributeType( $imageName3->attribute( 'contentclass_attribute' ) ) );
        $expectedData3[$fieldNameImageName3] = trim( implode( ' ', array_values( ezfSolrDocumentFieldBase::getInstance( $imageName3 )->getData() ) ), "\t\r\n " );

        $fieldNameImageCaption3 = ezfSolrDocumentFieldBase::generateSubattributeFieldName( $ezcca3,
                                                            $imageCaption3->attribute( 'contentclass_attribute_identifier' ),
                                                            ezfSolrDocumentFieldObjectRelation::getClassAttributeType( $imageCaption3->attribute( 'contentclass_attribute' ) ) );
        $expectedData3[$fieldNameImageCaption3] = trim( implode( ' ', array_values( ezfSolrDocumentFieldBase::getInstance( $imageCaption3 )->getData() ) ), "\t\r\n " );

        $providerArray[] = array( $expectedData3, $ezcoa3 );
        #end 3


        // Let's perform the actual testing :
        foreach( $providerArray as $input )
        {
            $expected = $input[0];
            $contentObjectAttribute = $input[1];
            $instance = ezfSolrDocumentFieldBase::getInstance( $contentObjectAttribute );

            self::assertEquals( $expected, $instance->getData() );
        }
    }

    /*protected function tearDown()
    {
        $object = eZContentObject::fetch( $GLOBALS['articleId'] );
        var_dump( $GLOBALS['articleId'], $object );
    }*/
}
?>
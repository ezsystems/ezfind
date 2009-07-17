<?php
/**
 * Test suite for ezfSolrDocumentFieldBase
 **/
class ezfSolrDocumentFieldBaseTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    public function setUp()
    {
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
     * Data provider testGetFieldName()
     */
    public function providerTestGetFieldName()
    {
        $ezcca1 = new eZContentClassAttribute( array( 'identifier'        => 'title' ,
                                                      'data_type_string'  => 'ezstring' ) );
        $expected1 = ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t';

        // Testing the default subattribute
        $ezcca2 = new eZContentClassAttribute( array( 'identifier'        => 'dummy' ,
                                                      'data_type_string'  => 'dummy_example' ) );
        $expected2 = ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'dummy_t';

        //Testing the class/attribute/subattribute syntax
        $ezcca3 = $ezcca2;
        $expected3 = ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . 'dummy-subattribute2_t';
        $options3 = 'subattribute2';

        return array(
            array( $expected1, $ezcca1 ),
            array( $expected2, $ezcca2 ),
            array( $expected3, $ezcca3, $options3 )
        );
    }

    /**
     * @dataProvider providerTestGetFieldName()
     */
    public function testGetFieldName( $expected, $contentClassAttribute, $options = null )
    {
        self::assertEquals(
            $expected,
            ezfSolrDocumentFieldBase::getFieldName( $contentClassAttribute, $options )
        );
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
     * provider for testGetData()
     */
    public function providerTestGetData()
    {
        $providerArray = array();

        #start 1
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


        #start 2
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

        return $providerArray;
    }

    /**
     * test for getData()
     * @dataProvider providerTestGetData()
     */
    public function testGetData( $expected, $contentObjectAttribute )
    {
        $instance = ezfSolrDocumentFieldBase::getInstance( $contentObjectAttribute );

        self::assertEquals(
            $expected,
            $instance->getData()
        );
    }

}
?>
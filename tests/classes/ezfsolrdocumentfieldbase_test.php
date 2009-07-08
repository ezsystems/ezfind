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
    * Data provider for testGetInstance
    **/
    public static function providerTestGetInstance()
    {
        $ezcoa1 = new eZContentObjectAttribute( array( "data_type_string" => 'ezstring' ) );
        $field1 = new ezfSolrDocumentFieldBase( $ezcoa1 );

        $ezcoa2 = new eZContentObjectAttribute( array( "data_type_string" => 'ezobjectrelation' ) );
        $ezcoa3 = new eZContentObjectAttribute( array( "data_type_string" => 'ezobjectrelationlist' ) );
        $field2 = new ezfSolrDocumentFieldObjectRelation( $ezcoa2 );
        $field3 = new ezfSolrDocumentFieldObjectRelation( $ezcoa3 );

        $ezcoa4 = new eZContentObjectAttribute( array( "data_type_string" => 'ezxmltext' ) );
        $ezcoa5 = new eZContentObjectAttribute( array( "data_type_string" => 'ezmatrix' ) );
        $field4 = new ezfSolrDocumentFieldXML( $ezcoa4 );
        $field5 = new ezfSolrDocumentFieldXML( $ezcoa5 );


        return array(
            array( $ezcoa1, $field1 ),
            array( $ezcoa2, $field2 ),
            array( $ezcoa3, $field3 ),
            array( $ezcoa4, $field4 ),
            array( $ezcoa5, $field5 ),
        );
    }

    /**
     * @dataProvider providerTestGetInstance
     **/
    public function testGetInstance( $eZContentObjectAttribute, $expected )
    {
        self::assertEquals(
            ezfSolrDocumentFieldBase::getInstance( $eZContentObjectAttribute ),
            $expected
        );
    }
}
?>
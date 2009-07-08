<?php
/**
 * Test suite for ezfSolrDocumentFieldName
 **/
class ezfSolrDocumentFieldNameTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    public function setUp()
    {
        parent::setUp();
    }

   /**
    * Data provider for testLookupSchemaName
    **/
    public static function providerTestLookupSchemaName()
    {
        $fieldMap = self::$sdfn->getFieldTypeMap();

        $providerData = array();
        foreach ( $fieldMap as $fullyQualifiedTypeName => $shortName )
        {
            $providerData[] = array( 'attr_' . 'title', $fullyQualifiedTypeName, 'attr_title_' . $shortName );
        }
        return $providerData;
    }

    /**
     * @dataProvider providerTestLookupSchemaName
     **/
    public function testLookupSchemaName( $baseName, $fieldType, $expected )
    {
        self::assertEquals(
            self::$sdfn->lookupSchemaName( $baseName, $fieldType ), $expected
        );
    }

    /**
     * ezfSolrDocumentFieldNameTester tester instance
     * @var ezfSolrDocumentFieldNameTester
     * @see ezfSolrDocumentFieldNameTester
     **/
    public static $sdfn;
}

ezfSolrDocumentFieldNameTest::$sdfn = new ezfSolrDocumentFieldNameTester();
?>
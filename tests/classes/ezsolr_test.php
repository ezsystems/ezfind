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
}
?>
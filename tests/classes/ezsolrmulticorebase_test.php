<?php
/**
 * Test suite for eZSolr
 **/
class eZSolrMultiCoreBaseTest extends ezpDatabaseTestCase
{   
    public function setUp()
    {
        parent::setUp();
    }

    
    /**
     * Test for eZSolrBase::solrURL()
     * @dataProvider providerForTestSolrURL
     */
    public function testSolrURL( $request, $languages, $expected, $iniOverrides = array() )
    {
        ezpINIHelper::setINISettings( $iniOverrides );
        
        $solrBase = new eZSolrMultiCoreBase();
        $this->assertEquals( $expected, $solrBase->solrURL( $request, $languages ) );
        
        ezpINIHelper::restoreINISettings();
    }
    
    /**
     * Data provider for testSolrURI()
     * We consider in all these tests that the search URI is set to
     * http & localhost:8983/solr
     */
    public static function providerForTestSolrURL()
    {
        $iniSettings = array(
            self::$INIOverride['MultiCoreEnabled'],
            self::$INIOverride['DefaultMapping'],
            self::$INIOverride['DefaultCore']
        );
        
        return array(
            array( '/select', 'eng-GB', 'http://localhost:8983/solr/eng-GB/select', $iniSettings),
            array( '/update', 'eng-GB', 'http://localhost:8983/solr/eng-GB/update', $iniSettings ),
            array( '/select', array( 'eng-GB', 'fre-FR' ), 'http://localhost:8983/solr/eng-GB/select?shards=localhost:8983/solr/eng-GB,localhost:8983/solr/fre-FR', $iniSettings ),
        );
    }
    
    /**
     * Test for eZSolrBase::getLanguageCore()
     * @dataProvider providerForTestGetLanguageCore
     */
    public function testGetLanguageCore( $expected, $languageCode, $iniOverrides )
    {
        ezpINIHelper::setINISettings( $iniOverrides );
                
        $solrBase = new eZSolrMultiCoreBase();
        $this->assertEquals( $expected, $solrBase->getLanguageCore( $languageCode ) );
        
        ezpINIHelper::restoreINISettings();
    }
    
    public static function providerForTestGetLanguageCore()
    {
        return array(
            // no multicore
            array(
                false,
                'eng-GB',
                array( self::$INIOverride['MultiCoreDisabled'] ),
            ),
            // multi-core with a mapped language
            array(
                'eng-GB', 'eng-GB',
                array( self::$INIOverride['MultiCoreEnabled'], self::$INIOverride['DefaultMapping'], self::$INIOverride['DefaultCore'] ),
            ),
            // multicore with an unmapped language
            array(
                'eng-GB', 'ger-DE', 
                array( self::$INIOverride['MultiCoreEnabled'], self::$INIOverride['DefaultMapping'], self::$INIOverride['DefaultCore'] ),
            )
        );
    }
    
    // predefined INI settings for tests
    protected static $INIOverride = array(
        'MultiCoreEnabled' => array( 'ezfind.ini', 'LanguageSearch', 'MultiCore', 'enabled' ),
        'MultiCoreDisabled' => array( 'ezfind.ini', 'LanguageSearch', 'MultiCore', 'disabled' ),
        'DefaultMapping' => array( 'ezfind.ini', 'LanguageSearch', 'LanguagesCoresMap', array( 'eng-GB' => 'eng-GB', 'fre-FR' => 'fre-FR' ) ),
        'DefaultCore' => array( 'ezfind.ini', 'LanguageSearch', 'DefaultCore', 'eng-GB' )
    );
}
?>
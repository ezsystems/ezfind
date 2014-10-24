<?php
/**
 * Test suite for eZFindElevateConfiguration
 **/
class eZFindElevateConfigurationTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    /**
     * Data provider for testGetRuntimeQueryParameters
     */
    public static function providerTestGetRuntimeQueryParameters()
    {
        $providerArray = array();

        # start 1
        $expected1 = array( 'forceElevation'  => 'false',
                            'enableElevation' => 'true' );
        $providerArray[] = array( $expected1, false, true, 'non empty' );
        # end 1

        # start 2
        $expected2 = array( 'forceElevation'  => 'false',
                            'enableElevation' => 'false' );
        $providerArray[] = array( $expected2 );
        # end 2

        # start 3
        $expected3 = array( 'forceElevation'  => 'false',
                            'enableElevation' => 'false' );
        $providerArray[] = array( $expected3, false, false, 'non empty' );
        # end 3

        # start 4
        $expected4 = array( 'forceElevation'  => 'true',
                            'enableElevation' => 'true' );
        $providerArray[] = array( $expected4, true, true, 'non empty' );
        # end 4

        return $providerArray;
    }

    /**
     * @dataProvider providerTestGetRuntimeQueryParameters
     */
    public function testGetRuntimeQueryParameters( $expected, $forceElevation = false, $enableElevation = true, $searchText = '' )
    {
        self::assertEquals(
            $expected,
            eZFindElevateConfiguration::getRuntimeQueryParameters( $forceElevation, $enableElevation, $searchText )
        );
    }

    /**
     * Testing add()
     */
    public function testAdd()
    {
        # start 1 : simple insert, for all languages
        $queryString = "test 1";
        $objectID = 25;
        $db = eZDB::instance();

        eZFindElevateConfiguration::add( $queryString, $objectID );

        $expected1 = array( 'contentobject_id' => $objectID,
                            'search_query'     => $queryString,
                            'language_code'    => eZFindElevateConfiguration::WILDCARD );
        $rows = $db->arrayQuery( 'SELECT * FROM ezfind_elevate_configuration WHERE contentobject_id=' . $objectID . ';' );

        self::assertEquals(
            $expected1,
            $rows[0]
        );
        # end 1

        # start 2 : trying to insert an elevate configuration row for a specific language, while one already exists for all languages.
        $language2 = 'eng-GB';
        $returnValue = eZFindElevateConfiguration::add( $queryString, $objectID, $language2 );

        $expected2 = null;

        self::assertEquals(
            $expected2,
            $returnValue
        );
        # end 2


        # start 3 : inserting an entry for "all languages" while one already exists for one specific language.
        #           Only the more general entry should exist eventually.
        $rows = $db->query( 'TRUNCATE TABLE ezfind_elevate_configuration;' );

        $language3 = 'eng-GB';
        // Insert for eng-GB :
        eZFindElevateConfiguration::add( $queryString, $objectID, $language3 );
        // General override :
        eZFindElevateConfiguration::add( $queryString, $objectID );

        $expected3 = array( 'contentobject_id' => $objectID,
                            'search_query'     => $queryString,
                            'language_code'    => eZFindElevateConfiguration::WILDCARD );
        $expectedCount3 = 1;
        $rows = $db->arrayQuery( 'SELECT * FROM ezfind_elevate_configuration WHERE contentobject_id=' . $objectID . ';' );

        self::assertEquals(
            $expectedCount3,
            count( $rows )
        );

        self::assertEquals(
            $expected3,
            $rows[0]
        );
        # end 3
    }

    /**
     * test for generateConfiguration()
     */
    public function testGenerateConfiguration()
    {
        // clean up the table beforehand
        $db = eZDB::instance();
        $rows = $db->query( 'TRUNCATE TABLE ezfind_elevate_configuration;' );
        $solr = new eZSolr();

        # start 1
        $queryString = "test 1";
        $objectID = 1;
        $object = eZContentObject::fetch( $objectID );
        $language = "eng-GB";
        $docId = $solr->guid( $object, $language );

        eZFindElevateConfiguration::add( $queryString, $objectID, $language );
        eZFindElevateConfigurationTester::generateConfiguration();
        $configuration1 = eZFindElevateConfigurationTester::getConfiguration();

        $expected1 = <<<ENDT
<?xml version="1.0" encoding="UTF-8"?>
<elevate><query text="test 1"><doc id="
ENDT;
        $expected1 .= $docId;
        $expected1 .= <<<ENDT
"/></query></elevate>

ENDT;

        self::assertEquals(
            $expected1,
            $configuration1
        );
        # end 1
    }

    /**
     * test for fetchConfigurationForObject()
     */
    public function testFetchConfigurationForObject()
    {
        // clean up the table beforehand
        $db = eZDB::instance();
        $rows = $db->query( 'TRUNCATE TABLE ezfind_elevate_configuration;' );

        # start 1 : invalid object ID
        $expected1 = null;
        $configuration1 = eZFindElevateConfiguration::fetchConfigurationForObject( 'non numeric' );

        self::assertEquals(
            $expected1,
            $configuration1
        );
        # end 1


        # start 2 : simple fetch with default parameters.
        $queryString = "test 1";
        $objectID = 1;
        $language = "eng-GB";
        eZFindElevateConfiguration::add( $queryString, $objectID, $language );

        $expected2 = array( $language => array(
                                new eZFindElevateConfiguration( array( 'search_query'     => $queryString,
                                                                       'contentobject_id' => $objectID,
                                                                       'language_code'    => $language ) )
                                              )
                          );
        $configuration2 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID );

        self::assertEquals(
            $expected2,
            $configuration2
        );
        # end 2


        # start 3 : group by language disabled.
        $expected3 = array( new eZFindElevateConfiguration( array( 'search_query'     => $queryString,
                                                                   'contentobject_id' => $objectID,
                                                                   'language_code'    => $language ) )
                          );
        $configuration3 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID, false );

        self::assertEquals(
            $expected3,
            $configuration3
        );
        # end 3


        # start 4 : filtering by language code
        $additionalLanguage = "esl-ES";
        eZFindElevateConfiguration::add( $queryString, $objectID, $additionalLanguage );

        $expected4 = array( $additionalLanguage => array(
                                new eZFindElevateConfiguration( array( 'search_query'     => $queryString,
                                                                       'contentobject_id' => $objectID,
                                                                       'language_code'    => $additionalLanguage ) )
                                              )
                          );
        $configuration4 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID, true, $additionalLanguage );

        self::assertEquals(
            $expected4,
            $configuration4
        );
        # end 4


        # start 5 : testing the countOnly parameter
        $expected5 = 1;
        $configuration5 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID, true, $additionalLanguage, null, true );

        self::assertEquals(
            $expected5,
            $configuration5
        );
        # end 5


        # start 6 : testing the limit parameter
        $expected6 = 2;
        $configuration6 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID, true, null, 1, true );

        self::assertEquals(
            $expected6,
            $configuration6
        );
        # end 6


        # start 7 : filtering by search query
        $additionalQueryString = "test 2";
        eZFindElevateConfiguration::add( $additionalQueryString, $objectID, $language );

        $expected7 = 2;
        $configuration7 = eZFindElevateConfiguration::fetchConfigurationForObject( $objectID, true, null, null, true, $queryString );

        self::assertEquals(
            $expected7,
            $configuration7
        );
        # end 7
    }
}
?>

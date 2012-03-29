<?php
/**
 * File containing the eZFindTestSuite class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class eZFindTestSuite extends ezpDatabaseTestSuite
{
    public function __construct()
    {
        parent::__construct();
        ini_set( 'xdebug.show_exception_trace', 'Off' );
        $this->setName( "eZ Find Test Suite" );

        $this->addTestSuite( 'ezfeZPSolrQueryBuilderTest' );
        $this->addTestSuite( 'ezfSolrDocumentFieldBaseTest' );
        $this->addTestSuite( 'ezfSolrDocumentFieldNameTest' );
        $this->addTestSuite( 'eZSolrTest' );
        $this->addTestSuite( 'eZFindElevateConfigurationTest' );
        $this->addTestSuite( 'eZSolrMultiCoreBaseTest' );
        $this->addTestSuite( 'eZSolrBaseRegression' );
        $this->addTestSuite( 'eZFindFetchRegression' );
        $this->addTestSuite( 'eZSolrRegression' );
    }

    public static function suite()
    {
        return new self();
    }

    public function setUp()
    {
        parent::setUp();

        // make sure extension is enabled and settings are read
        ezpExtensionHelper::load( 'ezfind' );

        $sqlFiles = array( 'extension/ezfind/sql/mysql/mysql.sql' );
        ezpTestDatabaseHelper::insertSqlData( $this->sharedFixture, $sqlFiles );
    }

    public function tearDown()
    {
        ezpExtensionHelper::unload( 'ezfind' );
        parent::tearDown();
    }
}

?>

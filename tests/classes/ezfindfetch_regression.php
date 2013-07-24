<?php
/**
 * File containing eZFindFetchRegression class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package ezfind
 */
class eZFindFetchRegression extends ezpDatabaseTestCase
{
    protected $fetchParams;

    /**
     * @var eZContentObject
     */
    protected $testObj;

    /**
     * @var eZSolr
     */
    protected $solrSearch;

    public function setUp()
    {
        parent::setUp();

        eZINI::instance( 'ezfind.ini' )->loadCache( true );
        eZINI::instance( 'solr.ini' )->loadCache( true );
        ezpINIHelper::setINISetting( 'site.ini', 'SearchSettings', 'AllowEmptySearch', 'enabled' );
        ezpINIHelper::setINISetting( 'site.ini', 'RegionalSettings', 'SiteLanguageList', array( 'eng-GB' ) );
        $this->solrSearch = new eZSolr();
        $this->testObj = eZContentObject::fetchByNodeID( 2 );
        $this->solrSearch->addObject( $this->testObj );

        $this->fetchParams = array(
            'SearchOffset' => 0,
            'SearchLimit' => 10,
            'Facet' => null,
            'SortBy' => null,
            'Filter' => null,
            'SearchContentClassID' => null,
            'SearchSectionID' => null,
            'SearchSubTreeArray' => null,
            'AsObjects' => null,
            'SpellCheck' => null,
            'IgnoreVisibility' => null,
            'Limitation' => null,
            'BoostFunctions' => null,
            'QueryHandler' => 'ezpublish',
            'EnableElevation' => null,
            'ForceElevation' => null,
            'SearchDate' => null,
            'DistributedSearch' => null,
            'FieldsToReturn' => null
        );
    }

    public function tearDown()
    {
        $this->solrSearch->removeObject( $this->testObj );
        ezpINIHelper::restoreINISettings();
        parent::tearDown();
    }

    /**
     * Test for search fetch, results sorted by name
     * @link http://issues.ez.no/15423
     * @group issue15423
     */
    public function testSearchSortByName()
    {
        $res = $this->solrSearch->search(
            '',
            array( 'SortBy' => array( 'name', 'asc' ) ) + $this->fetchParams );

        self::assertType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
    }

    /**
     * Test for search fetch, results sorted by class_id
     * @link http://issues.ez.no/15423
     * @group issue15423
     */
    public function testSearchSortByClassId()
    {
        $res = $this->solrSearch->search(
            '',
            array( 'SortBy' => array( 'class_id', 'asc' ) ) + $this->fetchParams );

        self::assertType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
    }

    /**
     * Test for search fetch, results sorted by name
     * @link http://issues.ez.no/15423
     * @group issue15423
     */
    public function testSearchSortByPath()
    {
        $res = $this->solrSearch->search(
            '',
            array( 'SortBy' => array( 'path', 'asc' ) ) + $this->fetchParams );

        self::assertType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
    }
}
?>

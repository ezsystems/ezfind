<?php
/**
 * File containing eZFindFetchRegression class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package ezfind
 */
class eZFindFetch extends ezpDatabaseTestCase
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
	 */
	public function testSearch()
	{
		$res = $this->solrSearch->search( '', array( 'Filter' => 'meta_node_id_si:2' ) );

		self::assertInternalType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
		self::assertCount( 1, $res[ 'SearchResult' ] );
		
		$rootNode = $res['SearchResult'][0];
		
		self::assertTrue( $rootNode instanceof eZFindResultNode );
	}
	
	public function testNonObjectSearch()
	{
		$res = $this->solrSearch->search( '', array( 'Filter' => 'meta_node_id_si:2',
				                                     'AsObjects' => false ) );
	
		self::assertInternalType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
		self::assertCount( 1, $res[ 'SearchResult' ] );
		self::assertTrue( $res['SearchResult'][0][ 'main_node_id' ] == 2 );
	}

	public function testMoreLikeThis()
	{
		$res = $this->solrSearch->moreLikeThis( 'text', 'ez publish' );
	
		self::assertInternalType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
		self::assertCount( 1, $res[ 'SearchResult' ] );

		$rootNode = $res['SearchResult'][0];
		
		self::assertTrue( $rootNode instanceof eZFindResultNode );
	}

	public function testNonObjectMoreLikeThis()
	{
		$res = $this->solrSearch->moreLikeThis( 'text', 'ez publish', array( 'AsObjects' => false ) );
	
		self::assertInternalType( PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res['SearchResult'] );
		self::assertCount( 1, $res[ 'SearchResult' ] );
		self::assertTrue( $res['SearchResult'][0][ 'main_node_id' ] == 2 );
	}
	
}

?>
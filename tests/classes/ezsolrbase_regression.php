<?php
/**
 * File containing eZSolrBaseRegression class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package ezfind
 */
class eZSolrBaseRegression extends ezpDatabaseTestCase
{
    protected $testURI;

    protected $postParams;

    protected $nonReachableSolr;

    protected $solrINI;

    public function setUp()
    {
        parent::setUp();

        $this->testURI = '/admin/ping';
        $this->postParams = array( 'foo' => 'bar' );
        $this->nonReachableSolr = 'http://10.255.255.1/solr';
        ezpINIHelper::setINISetting( 'solr.ini', 'SolrBase', 'ConnectionTimeout', 2 );
        ezpINIHelper::setINISetting( 'solr.ini', 'SolrBase', 'ProcessMaxRetries', 2 );
        $this->solrINI = eZINI::instance( 'solr.ini' );
    }

    public function tearDown()
    {
        $this->testURI = null;
        $this->postParams = array();
        ezpINIHelper::restoreINISettings();

        parent::tearDown();
    }

    /**
     * Test for {@link eZSolrBase::sendHTTPRequestRetry()} with a valid Solr server
     * @link http://issues.ez.no/17862
     * @group issue17862
     */
    public function testSendHTTPRequestRetry()
    {
        $solrBase = new eZSolrBase();
        $refObj = new ReflectionObject( $solrBase );
        $refMethod = $refObj->getMethod( 'sendHTTPRequestRetry' );
        $refMethod->setAccessible( true );

        $postString = $solrBase->buildPostString( $this->postParams );
        $res = $refMethod->invoke( $solrBase, $solrBase->SearchServerURI.$this->testURI, $postString );
        self::assertType( PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $res );
        self::assertTrue( strpos( $res, '<?xml' ) !== false );

        // Now test with postQuery(), that calls sendHTTPRequestRetry() and check result is the same
        $refMethod2 = $refObj->getMethod( 'postQuery' );
        $refMethod2->setAccessible( true );
        $res2 = $refMethod2->invoke( $solrBase, $this->testURI, $postString );
        self::assertType( PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $res2 );
        self::assertTrue( strpos( $res2, '<?xml' ) !== false );
    }

    /**
     * Test for {@link eZSolrBase::sendHTTPRequestRetry()} with a timedout Solr server
     * @link http://issues.ez.no/17862
     * @group issue17862
     */
    public function testSendHTTPRequestRetryTimeout()
    {
        ezpINIHelper::setINISetting( 'solr.ini', 'SolrBase', 'SearchServerURI', $this->nonReachableSolr );
        $connectionTimeout = $this->solrINI->variable( 'SolrBase', 'ConnectionTimeout' );
        $maxRetries = $this->solrINI->variable( 'SolrBase', 'ProcessMaxRetries' );
        $solrBase = new eZSolrBase();

        $refObj = new ReflectionObject( $solrBase );
        $refMethod = $refObj->getMethod( 'sendHTTPRequestRetry' );
        $refMethod->setAccessible( true );

        $startTime = time();
        $postString = $solrBase->buildPostString( $this->postParams );
        $res = $refMethod->invoke( $solrBase, $solrBase->SearchServerURI.$this->testURI, $postString );
        $stopTime = time();
        $diffTime = $stopTime - $startTime;
        self::assertFalse( $res, 'Failed HTTP request to Solr must return false' );
        self::assertEquals( $diffTime, $maxRetries * $connectionTimeout, "Sending HTTP Request to Solr must be retried a setted number of times (in solr.ini) if server has timed out" );
    }

    /**
     * Test for {@link eZSolrBase::sendHTTPRequest()} with a request that will time out
     * An exception must be thrown in that case
     * @link http://issues.ez.no/17862
     * @group issue17862
     * @expectedException ezfSolrException
     */
    public function testSendHTTPRequestException()
    {
        ezpINIHelper::setINISetting( 'solr.ini', 'SolrBase', 'SearchServerURI', $this->nonReachableSolr );
        $solrBase = new eZSolrBase();
        $postString = $solrBase->buildPostString( $this->postParams );
        $solrBase->sendHTTPRequest( $solrBase->SearchServerURI.$this->testURI, $postString );
    }
}
?>

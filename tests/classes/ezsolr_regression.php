<?php
/**
 * File containing eZSolrRegression class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package ezfind
 */
class eZSolrRegression extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    /**
     * @var ezpObject
     */
    protected $object;

    /**
     * @var eZSolr
     */
    protected $solrSearch;

    /**
     * @var eZINI
     */
    protected $findINI;

    public function setUp()
    {
        parent::setUp();

        ezpINIHelper::setINISetting( 'site.ini', 'SearchSettings', 'AllowEmptySearch', 'enabled' );
        ezpINIHelper::setINISetting( 'site.ini', 'RegionalSettings', 'SiteLanguageList', array( 'eng-GB' ) );
        $this->findINI = eZINI::instance( 'ezfind.ini' );
        $this->findINI->loadCache( true );

        $this->solrSearch = new eZSolr();
        $this->object = new ezpObject( 'folder', 2 );
        $this->object->name = 'foo';
        $this->object->publish();
        $this->object->addNode( 43 ); // Add a location under Media node
        $this->solrSearch->addObject( $this->object->object );
    }

    public function tearDown()
    {
        $this->solrSearch->removeObject( $this->object->object );
        $this->object->remove();
        $this->object = null;
        $this->solrSearch = null;

        ezpINIHelper::restoreINISettings();

        parent::tearDown();
    }

    /**
     * Regression test for issue #17576
     * Expected : Result from Solr should holds local Node when searching with subtree_array
     * @link http://issues.ez.no/17576
     * @group issue17576
     */
    public function testNodeIDWithSubtreeArray()
    {
        $expectedNodeID = 0;
        foreach ( $this->object->nodes as $node )
        {
            if ( strpos( $node->attribute( 'path_string' ), '/1/43/' ) === 0 )
            {
                $expectedNodeID = $node->attribute( 'node_id' );
            }
        }

        $res = $this->solrSearch->search( 'foo', array(
                                                 'SearchOffset' => 0,
                                                 'SearchLimit' => 1,
                                                 'SearchContentClassID' => 'folder',
                                                 'SearchSubTreeArray' => array( 43 ),
                                                 'AsObjects' => true,
                                                 'IgnoreVisibility' => false,
                                                 'QueryHandler' => 'ezpublish',
                                                 'EnableElevation' => true,
                                                 'ForceElevation' => false,
                                             ) );

        self::assertEquals( $expectedNodeID, $res['SearchResult'][0]->attribute( 'node_id' ), 'Result from Solr should holds local Node when searching with subtree_array' );
    }
}

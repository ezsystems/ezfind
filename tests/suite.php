<?php
/**
 * File containing the eZFindTestSuite class
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class eZFindTestSuite extends ezpDatabaseTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "eZ Find Test Suite" );

        $this->addTestSuite( 'ezfeZPSolrQueryBuilderTest' );
    }

    public static function suite()
    {
        return new self();
    }
}

?>
#!/usr/bin/env php
<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.0beta1
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//
//

require 'autoload.php';

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
set_time_limit( 0 );

$cli = eZCLI::instance();
$endl = $cli->endlineString();

$script = eZScript::instance( array( 'description' => ( "eZ publish search index updater.\n\n" .
							 "Goes trough all objects and reindexes the meta data to the search engine" .
							 "\n" .
							 "updatesearchindex.php"),
				      'use-session' => true,
				      'use-modules' => true,
				      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[db-host:][db-user:][db-password:][db-database:][db-type:|db-driver:][sql][clean]",
				"",
				array( 'db-host' => "Database host",
				       'db-user' => "Database user",
				       'db-password' => "Database password",
				       'db-database' => "Database name",
				       'db-driver' => "Database driver",
				       'db-type' => "Database driver, alias for --db-driver",
				       'sql' => "Display sql queries",
				       'clean' =>  "Remove all search data before beginning indexing"
				       ) );
$script->initialize();

$dbUser = $options['db-user'] ? $options['db-user'] : false;
$dbPassword = $options['db-password'] ? $options['db-password'] : false;
$dbHost = $options['db-host'] ? $options['db-host'] : false;
$dbName = $options['db-database'] ? $options['db-database'] : false;
$dbImpl = $options['db-driver'] ? $options['db-driver'] : false;
$showSQL = $options['sql'] ? true : false;
$siteAccess = $options['siteaccess'] ? $options['siteaccess'] : false;
$cleanupSearch = $options['clean'] ? true : false;

if ( $siteAccess )
{
    changeSiteAccessSetting( $siteaccess, $siteAccess );
}

function changeSiteAccessSetting( &$siteaccess, $optionData )
{
    global $isQuiet;
    $cli = eZCLI::instance();
    if ( file_exists( 'settings/siteaccess/' . $optionData ) )
    {
        $siteaccess = $optionData;
        if ( !$isQuiet )
            $cli->notice( "Using siteaccess $siteaccess for search index update" );
    }
    else
    {
        if ( !$isQuiet )
            $cli->notice( "Siteaccess $optionData does not exist, using default siteaccess" );
    }
}

print( "Starting object re-indexing\n" );

$db = eZDB::instance();

if ( $dbHost or $dbName or $dbUser or $dbImpl )
{
    $params = array();
    if ( $dbHost !== false )
        $params['server'] = $dbHost;
    if ( $dbUser !== false )
    {
        $params['user'] = $dbUser;
        $params['password'] = '';
    }
    if ( $dbPassword !== false )
        $params['password'] = $dbPassword;
    if ( $dbName !== false )
        $params['database'] = $dbName;
    $db = eZDB::instance( $dbImpl, $params, true );
    eZDB::setInstance( $db );
}

$db->setIsSQLOutputEnabled( $showSQL );

if ( $cleanupSearch )
{
    print( "{eZSearchEngine: Cleaning up search data" );
    eZSearch::cleanup();
    print( "}$endl" );
}

// Get top node
$topNodeArray = eZPersistentObject::fetchObjectList( eZContentObjectTreeNode::definition(),
                                                     null,
                                                     array( 'parent_node_id' => 1,
                                                            'depth' => 1 ) );
$subTreeCount = 0;
foreach ( array_keys ( $topNodeArray ) as $key  )
{
    $subTreeCount += $topNodeArray[$key]->subTreeCount( array( 'Limitation' => false, 'MainNodeOnly' => true ) );
}

print( "Number of objects to index: $subTreeCount $endl" );

$i = 0;
$dotMax = 70;
$dotCount = 0;
$limit = 200;
$commitLimit = 1000;
$iCommit = 0;
$counter = 0;
$counterInterrupt = 10000000;

$searchEngine = new eZSolr;
$start=microtime_float();
foreach ( $topNodeArray as $node  )
{
    $offset = 0;
    while ( $subTree = $node->subTree( array( 'Offset' => $offset, 'Limit' => $limit,
                                              'Limitation' => array(),
                                              'MainNodeOnly' => true ) ) )
    {
        foreach ( $subTree as $innerNode )
        {
            $object = $innerNode->attribute( 'object' );
            if ( !$object )
            {
                continue;
            }
            //eZSearch::removeObject( $object );
            //pass false as we are going to do a commit at the end
            //
            $searchEngine->addObject( $object, false );
            ++$i;
            ++$iCommit;
            ++$dotCount;
            // counter: use for debugging, index first 200 objects
            ++$counter;
            if ($counter > $counterInterrupt) break 3;
            print( "." );
            if ( $dotCount >= $dotMax or $i >= $subTreeCount )
            {
                $dotCount = 0;
                $percent = (float)( ($i*100.0) / $subTreeCount );
                print( " " . $percent . "%" . $endl );
            }
            if ($iCommit > $commitLimit )
            {
                print ($endl . " ==intermediate optimize== " . $endl);
                $searchEngine->optimize();
                $iCommit = 0;
                eZContentObject::clearCache();
            }
        }
        $offset += $limit;
    }
}

if ( !( $searchEngine instanceof eZSolr ) )
{
    $script->shutdown( 1, 'The current search engine plugin is not eZSolr' );
}
$end_index = microtime_float();
print ($endl . "Start optimize and commit...");
$searchEngine->optimize( true );
print( $endl . "done" . $endl );
$end_all = microtime_float();
print ('Indexing took ' . ($end_index-$start) . ' secs (average: ' . ($counter / ($end_index-$start)) . ' objects/sec)' . $endl);
$script->shutdown();

?>

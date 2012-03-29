<?php
//
// Created on: <27-Nov-2008 15:28:15 pb>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2012 eZ Systems AS
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

/*! \file ezfindexcontent.php
*/

eZDebug::writeStrict( "This cronjob is deprecated in favor of the standard cronjobs/indexcontent.php provided by eZ Publish" );

if ( !$isQuiet )
{
    $cli->output( "Starting processing pending search engine modifications" );
}

// check that solr is enabled and used
$eZSolr = eZSearch::getEngine();
if ( !( $eZSolr instanceof eZSolr ) )
{
	$script->shutdown( 1, 'The current search engine plugin is not eZSolr' );
}

$contentObjects = array();
$db = eZDB::instance();

$offset = 0;
$limit = 50;

while( true )
{
    $entries = $db->arrayQuery( "SELECT param FROM ezpending_actions WHERE action = 'index_object'",
                                array( 'limit' => $limit,
                                       'offset' => $offset ) );

    if ( is_array( $entries ) && count( $entries ) != 0 )
    {
        $objectIDList = array();
        $db->begin();
        foreach ( $entries as $entry )
        {
            $objectID = $entry['param'];
            $objectIDList[] = (int)$objectID;

            $cli->output( "\tIndexing object ID #$objectID" );
            $object = eZContentObject::fetch( $objectID );
            if ( $object )
            {
                // delay commits with passing false for $commit parameter
                $eZSolr->addObject( $object, false );
            }
        }

        $paramInSQL = $db->generateSQLInStatement( $objectIDList, 'param' );

        $db->query( "DELETE FROM ezpending_actions WHERE action = 'index_object' AND $paramInSQL" );
        $db->commit();

        // finish up with commit
        $eZSolr->commit();
        // clear object cache to conserver memory
        eZContentObject::clearCache();
    }
    else
    {
        break; // No valid result from ezpending_actions
    }
}

if ( !$isQuiet )
{
    $cli->output( "Done" );
}

?>

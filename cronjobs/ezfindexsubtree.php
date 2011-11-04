<?php
//
// Created on: <27-Nov-2008 15:28:15 pb>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
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

if ( !$isQuiet )
{
    $cli->output( "Processing pending subtree re-index actions" );
}

// check that solr is enabled and used
$eZSolr = eZSearch::getEngine();
if ( !$eZSolr instanceof eZSolr )
{
    $script->shutdown( 1, 'The current search engine plugin is not eZSolr' );
}

$limit = 50;
$entries = eZPendingActions::fetchByAction( eZSolr::PENDING_ACTION_INDEX_SUBTREE );

if ( !empty( $entries ) )
{
    $parentNodeIDList = array();
    foreach ( $entries as $entry )
    {
        $parentNodeID = $entry->attribute( 'param' );
        $parentNodeIDList[] = (int)$parentNodeID;

        $offset = 0;
        while ( true )
        {
            $nodes = eZContentObjectTreeNode::subTreeByNodeID(
                array(
                    'IgnoreVisibility' => true,
                    'Offset' => $offset,
                    'Limit' => $limit
                ),
                $parentNodeID
            );

            if ( !empty( $nodes ) && is_array( $nodes ) )
            {
                foreach ( $nodes as $node )
                {
                    ++$offset;
                    $cli->output( "\tIndexing object ID #{$node->attribute( 'contentobject_id' )}" );
                    // delay commits with passing false for $commit parameter
                    $eZSolr->addObject( $node->attribute( 'object' ), false );
                }

                // finish up with commit
                $eZSolr->commit();
                // clear object cache to conserver memory
                eZContentObject::clearCache();
            }
            else
            {
                break; // No valid nodes
            }
        }
    }

    eZPendingActions::removeByAction(
        eZSolr::PENDING_ACTION_INDEX_SUBTREE,
        array(
            'param' => array( $parentNodeIDList )
        )
    );
}

if ( !$isQuiet )
{
    $cli->output( "Done" );
}

?>

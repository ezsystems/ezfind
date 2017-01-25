<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

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
                    'Limit' => $limit,
                    'Limitation' => array(),
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

<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

if ( !$isQuiet )
{
    $cli->output( "Starting solr index optimization" );
}

// check that solr is enabled and used
$eZSolr = eZSearch::getEngine();
if ( !( $eZSolr instanceof eZSolr ) )
{
	$script->shutdown( 1, 'The current search engine plugin is not eZSolr' );
}

$eZSolr->optimize( false );

if ( !$isQuiet )
{
    $cli->output( "Done" );
}

?>

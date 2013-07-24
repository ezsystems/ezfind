<?php
//
// Created on: <02-Apr-2009 16:53:00 bd>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2013 eZ Systems AS
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

/*! \file ezfoptimizeindex.php
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

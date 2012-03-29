#!/usr/bin/env php
<?php

//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
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

require 'autoload.php';

$cli = eZCLI::instance();

$scriptSettings = array();
$scriptSettings['description'] = 'Sends a commit update message to the Solr search server';
$scriptSettings['use-session'] = false;
$scriptSettings['use-modules'] = true;
$scriptSettings['use-extensions'] = true;

$script = eZScript::instance( $scriptSettings );
$script->startup();

$config = '';
$argumentConfig = '';
$optionHelp = false;
$arguments = false;
$useStandardOptions = true;

$options = $script->getOptions( $config, $argumentConfig, $optionHelp, $arguments, $useStandardOptions );
$script->initialize();

$searchEngine = eZSearch::getEngine();

if ( !( $searchEngine instanceof eZSolr ) )
{
    $script->shutdown( 1, 'The current search engine plugin is not eZSolr' );
}

$searchEngine->commit();

$script->shutdown( 0 );

?>

<?php
//
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

/**
 * File containing the eZFind module definition.
 *
 * @package eZFind
 */

$Module = array( 'name' => 'eZFind', 'variable_params' => true );

$ViewList = array();
$ViewList['elevate'] = array(
    'functions' => array( 'elevate' ),
    'default_navigation_part' => 'ezfindnavigationpart',
    'ui_context' => 'administration',
    'script' => 'elevate.php',
    'params' => array(),
    'unordered_params' => array( 'language'     => 'Language',
                                 'offset'       => 'Offset',
                                 'limit'        => 'Limit',
                                 'search_query' => 'SearchQuery',
                                 'fuzzy_filter' => 'FuzzyFilter' )
                            );

$ViewList['elevation_detail'] = array(
    'functions' => array( 'elevate' ),
    'default_navigation_part' => 'ezfindnavigationpart',
    'ui_context' => 'administration',
    'script' => 'elevation_detail.php',
    'params' => array( 'ObjectID' ),
    'unordered_params' => array( 'language'     => 'Language',
                                 'offset'       => 'Offset',
                                 'limit'        => 'Limit',
                                 'search_query' => 'SearchQuery',
                                 'fuzzy_filter' => 'FuzzyFilter' )
                                    );

$ViewList['remove_elevation'] = array(
    'functions' => array( 'elevate' ),
    'default_navigation_part' => 'ezfindnavigationpart',
    'ui_context' => 'administration',
    'script' => 'remove_elevation.php',
    'params' => array( 'ObjectID', 'SearchQuery', 'LanguageCode' )
                                    );

$FunctionList = array();
$FunctionList['elevate'] = array();
?>

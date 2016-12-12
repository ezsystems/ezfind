<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
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

$ViewList[ 'query' ] = array(
    'functions' => array( 'query_admin' ),
    'default_navigation_part' => 'ezfindnavigationpart',
    'ui_context' => 'administration',
    'script' => 'query.php',
    'params' => array(),
);

$FunctionList = array();
$FunctionList['elevate'] = array();
$FunctionList['query_admin'] = array();
?>

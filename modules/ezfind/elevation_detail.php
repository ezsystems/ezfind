<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$feedback = array();
$wildcard = eZFindElevateConfiguration::WILDCARD;
$viewParameters = array();
$thisUrl = '/ezfind/elevation_detail';

// Identify which object is concerned.
$object = false;

if ( $Params['ObjectID'] !== false and is_numeric( $Params['ObjectID'] ) )
    $object = eZContentObject::fetch( $Params['ObjectID'] );
elseif ( $http->hasPostVariable( 'ObjectID' ) and is_numeric( $http->postVariable( 'ObjectID' ) ) )
    $object = eZContentObject::fetch( $http->postVariable( 'ObjectID' ) );

if ( !$object )
{
    //error
    $feedback['missing_object'] = true;
}
else
{
    $thisUrl .= '/' . $object->attribute( 'id' );
    $tpl->setVariable( 'elevatedObject', $object );

    // check language
    $languageFilter = false;

    if ( $http->hasPostVariable( 'ezfind-elevationdetail-filter-language' ) )
        $languageFilter = $http->postVariable( 'ezfind-elevationdetail-filter-language' );
    elseif ( $Params['Language'] !== false and $Params['Language'] != '' )
        $languageFilter = $Params['Language'];

    if ( $languageFilter and $languageFilter != $wildcard )
    {
        $viewParameters = array_merge( $viewParameters, array( 'language' => htmlspecialchars( $languageFilter, ENT_QUOTES ) ) );
        $tpl->setVariable( 'selectedLocale', eZContentLanguage::fetchByLocale( $languageFilter ) );
    }

    // check fuzzy filter
    $fuzzyFilter = false;

    if ( $http->hasPostVariable( 'ezfind-elevationdetail-filter-fuzzy' ) )
        $fuzzyFilter = true;
    elseif ( $Params['FuzzyFilter'] !== false )
        $fuzzyFilter = true;

    if ( $fuzzyFilter )
    {
        $viewParameters = array_merge( $viewParameters, array( 'fuzzy_filter' => $fuzzyFilter ) );
    }

    // check offset
    $viewParameters = array_merge( $viewParameters, array( 'offset' => ( isset( $Params['Offset'] ) and is_numeric( $Params['Offset'] ) ) ? $Params['Offset'] : 0 ) );


    // check limit
    $limitHint = array( 10, 10, 25, 50 );
    $viewParameters = array_merge( $viewParameters, array( 'limit' => $limitHint[eZPreferences::value( 'ezfind_elevate_preview_configurations' )] ) ) ;

    $limitArray = array( 'offset' => $viewParameters['offset'],
                         'limit'  => $viewParameters['limit'] );

    // check search query filter
    $searchQuery = false;
    $searchQueryArray = null;

    if ( $http->hasPostVariable( 'ezfind-elevationdetail-filter-searchquery' ) )
        $searchQuery = $http->postVariable( 'ezfind-elevationdetail-filter-searchquery' );
    elseif ( $Params['SearchQuery'] !== false )
        $searchQuery = $Params['SearchQuery'];

    if ( $searchQuery )
    {
        $searchQuery = htmlspecialchars( $searchQuery, ENT_QUOTES );
        $searchQueryArray = array( 'searchQuery' => $searchQuery,
                                   'fuzzy'       => $fuzzyFilter );
        $viewParameters = array_merge( $viewParameters, array( 'search_query' => $searchQuery ) );
    }

    // fetch configurations associated to the object :
    $configurations = eZFindElevateConfiguration::fetchConfigurationForObject( $object->attribute( 'id' ), false, @$viewParameters['language'], $limitArray, false, $searchQueryArray );
    $configurationsCount = eZFindElevateConfiguration::fetchConfigurationForObject( $object->attribute( 'id' ), false, @$viewParameters['language'], null, true, $searchQueryArray  );


    $tpl->setVariable( 'configurations', $configurations );
    $tpl->setVariable( 'configurations_count', $configurationsCount );
}

//$viewParameters = array_merge( $viewParameters, array( 'offset' => ( isset( $Params['Offset'] ) and is_numeric( $Params['Offset'] ) ) ? $Params['Offset'] : 0,
//                                                       'limit'  => $Params['Limit'] ) );
$tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'feedback', $feedback );
$tpl->setVariable( 'language_wildcard', $wildcard );
$tpl->setVariable( 'baseurl', $thisUrl );

$Result = array();
$Result['content'] = $tpl->fetch( "design:ezfind/elevation_detail.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'eZFind' ) ),
                         array( 'url' => 'ezfind/elevate',
                                'text' => ezpI18n::tr( 'extension/ezfind', 'Elevation' ) ) );

if ( $object instanceof eZContentObject )
{
	$Result['path'][] = array( 'url' => false,
                                'text' => $object->attribute( 'name' ) );
}

$Result['left_menu'] = "design:ezfind/backoffice_left_menu.tpl";
?>

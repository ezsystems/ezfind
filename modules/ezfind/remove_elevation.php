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
$thisUrl = '/ezfind/remove_elevation';

// Identify which object is concerned.
$object = false;

if ( $Params['ObjectID'] !== false and is_numeric( $Params['ObjectID'] ) )
    $object = eZContentObject::fetch( $Params['ObjectID'] );

if ( !$object )
{
    //error. Redirect to the elevate configuration landing page.
    $module->redirectTo( '/ezfind/elevate' );
}
// One cancelled removal :
elseif ( $object and $http->hasPostVariable( 'ezfind-removeelevation-cancel' ) )
{
    // Redirect to the detail elevate configuration page for this object :
    $module->redirectTo( '/ezfind/elevation_detail/' . $object->attribute( 'id' ) );
}
// One confirmed removal :
elseif ( $object and $http->hasPostVariable( 'ezfind-removeelevation-do' ) )
{
    $tpl->setVariable( 'elevatedObject', $object );
    $searchQuery = htmlspecialchars( $http->postVariable( "ezfind-removeelevation-searchquery" ), ENT_QUOTES );
    $languageCode = htmlspecialchars( $http->postVariable( "ezfind-removeelevation-languagecode" ), ENT_QUOTES );
    eZFindElevateConfiguration::purge( $searchQuery , $object->attribute( 'id' ), $languageCode );

    $feedback['removal_back_link'] = '/ezfind/elevate/';
    $feedback['confirm_remove'] = array( 'contentobject_id' => $object->attribute( 'id' ),
                                         'search_query'     => $searchQuery,
                                         'language_code'    => $languageCode  );
}
else
{
    $thisUrl .= '/' . $object->attribute( 'id' );
    $tpl->setVariable( 'elevatedObject', $object );

    // check search query
    $searchQuery = false;

    if ( $Params['SearchQuery'] !== false and $Params['SearchQuery'] != '' )
    {
        $searchQuery = $Params['SearchQuery'];
        $thisUrl .= '/' . $searchQuery;
    }
    else
    {
        // error, redirect to the detail elevate configuration page for this object :
        $module->redirectTo( '/ezfind/elevation_detail/' . $object->attribute( 'id' ) );
    }

    if ( $Params['LanguageCode'] !== false and $Params['LanguageCode'] != '' )
    {
        // Ask for removal confirmation
        $feedback['confirm_remove'] = array( 'contentobject_id' => $object->attribute( 'id' ),
                                             'search_query'     => $searchQuery,
                                             'language_code'    => $Params['LanguageCode']  );
    }
    else
    {
        // Display all existing elevate configurations for the current object and the current search_query :
        $module->redirectTo( '/ezfind/elevation_detail/' . $object->attribute( 'id' ) . '/(search_query)/' . $searchQuery );
    }
}

// $tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'feedback', $feedback );
$tpl->setVariable( 'language_wildcard', $wildcard );
$tpl->setVariable( 'baseurl', $thisUrl );

$Result = array();
$Result['content'] = $tpl->fetch( "design:ezfind/remove_elevation.tpl" );
$Result['left_menu'] = "design:ezfind/backoffice_left_menu.tpl";
$Result['path'] = array( array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'eZFind' ) ),
                         array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'Remove Elevation' ) ) );
?>

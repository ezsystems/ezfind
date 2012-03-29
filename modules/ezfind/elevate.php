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
 * File containing the elevate view of the ezfind module.
 *
 * @package eZFind
 */

require_once( "kernel/common/template.php" );

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = templateInit();
$feedback = array();
$wildcard = eZFindElevateConfiguration::WILDCARD;
$viewParameters = array();
$availableTranslations = eZContentLanguage::fetchList();
$thisUrl = '/ezfind/elevate';

// Elevation was triggered from the javascript menu ( content structure menu OR subitems menu )
if ( $http->hasPostVariable( 'ObjectIDFromMenu' ) and is_numeric( $http->postVariable( 'ObjectIDFromMenu' ) ) )
{
    $elevatedObject = eZContentObject::fetch( $http->postVariable( 'ObjectIDFromMenu' ) );

    $tpl->setVariable( 'back_from_browse', true );
    $tpl->setVariable( 'elevatedObject', $elevatedObject );
    $tpl->setVariable( 'elevateSearchQuery', '' );
}

// back from browse
else if (
    $http->hasPostVariable( 'BrowseActionName' ) and
    ( $http->postVariable( 'BrowseActionName' ) == ( 'ezfind-elevate-browseforobject' ) or $http->postVariable( 'BrowseActionName' ) == ( 'ezfind-searchelevateconfigurations-browse' ) ) and
    $http->hasPostVariable( "SelectedNodeIDArray" )
      )
{
    if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
    {
        $selectedNodeID = $http->postVariable( "SelectedNodeIDArray" );
        $selectedNodeID = $selectedNodeID[0];
        $elevatedObject = eZContentObject::fetchByNodeID( $selectedNodeID );

        // Browse was triggered to pick an object for elevation
        if ( $http->postVariable( 'BrowseActionName' ) == ( 'ezfind-elevate-browseforobject' ) )
        {
            $tpl->setVariable( 'back_from_browse', true );
            $tpl->setVariable( 'elevatedObject', $elevatedObject );
            $tpl->setVariable( 'elevateSearchQuery', $http->postVariable( 'elevateSearchQuery' ) );
        }
        else
        {
            // Browsing for an object, the elevate configuration of which we would like to inspect.
            $objectID = $elevatedObject->attribute( 'id' );
            // Redirect the to detail page for the object's elevation configuration
            $module->redirectToView( 'elevation_detail', array( $objectID ) );
        }
    }
}

// From elevate's landing page, trigger browsing for an object to elevate, or to search elevation for
elseif ( $http->hasPostVariable( 'ezfind-elevate-browseforobject' ) or
         $http->hasPostVariable( 'ezfind-searchelevateconfigurations-browse' ) )
{
    $actionName = $http->hasPostVariable( 'ezfind-elevate-browseforobject' ) ? 'ezfind-elevate-browseforobject' : 'ezfind-searchelevateconfigurations-browse';
    $elevateSearchQuery =  $http->hasPostVariable( 'ezfind-elevate-searchquery' ) ? $http->postVariable( 'ezfind-elevate-searchquery' ): '';
    $browseType = 'SelectObjectRelationNode';
    eZContentBrowse::browse( array( 'action_name' => $actionName,
                                    'type' =>  $browseType,
                                    'from_page' => $module->currentRedirectionURI(),
                                    'persistent_data' => array( 'elevateSearchQuery' => $elevateSearchQuery ) ),
                             $module );
}

// Store the actual Elevate configuration
else if ( $http->hasPostVariable( 'ezfind-elevate-do' ) )
{
    $doStorage = true;

    // Check if we have all required data
    // Validate ObjectID
    if ( !$http->hasPostVariable( 'elevateObjectID' ) or
         ( $elevatedObject = eZContentObject::fetch( $http->postVariable( 'elevateObjectID' ) ) ) === null
       )
    {
        $feedback['missing_object'] = true;
        $doStorage = false;
    }
    else
    {
        $tpl->setVariable( 'back_from_browse', true );
        $tpl->setVariable( 'elevatedObject', $elevatedObject );
    }

    // validate elevation string
    if ( !$http->hasPostVariable( 'ezfind-elevate-searchquery' ) or $http->postVariable( 'ezfind-elevate-searchquery' ) == '' )
    {
        $feedback['missing_searchquery'] = true;
        $doStorage = false;
    }
    else
    {
        $tpl->setVariable( 'elevateSearchQuery', $http->postVariable( 'ezfind-elevate-searchquery' ) );
    }

    // validate elevation language
    if ( !$http->hasPostVariable( 'ezfind-elevate-language' ) )
    {
        $feedback['missing_language'] = true;
        $doStorage = false;
    }

    // Do storage, and create the associated feedback
    if ( $doStorage )
    {
        // Filter the not yet filtered fields
        $queryString = htmlspecialchars( $http->postVariable( 'ezfind-elevate-searchquery' ), ENT_QUOTES );
        $languageCode = htmlspecialchars( $http->postVariable( 'ezfind-elevate-language' ), ENT_QUOTES );

        // Do actual storage here.
        $conf = eZFindElevateConfiguration::add( $queryString, $http->postVariable( 'elevateObjectID' ), $languageCode );

        // Give feedback message
        if ( $conf instanceof eZFindElevateConfiguration )
        {
            $feedback['creation_ok'] = $conf;
            $tpl->resetVariables();

            // redirect to the original page if any.
            // Useful to not duplicate storage code and input validation : the storage form can be submitted from another place.
            //    Pseudo-code example:   redirectURI='/ezfind/elevation_detail/86'
            if ( $http->hasPostVariable( 'redirectURI' ) )
            {
                $module->redirectTo( $http->postVariable( 'redirectURI' ) );
            }
        }
        else
            $feedback['creation_error'] = array( 'elevatedObject' => $elevatedObject,
                                                 'language' => $languageCode,
                                                 'queryString' => $queryString );
    }
}

// Searching for elevate configurations, directly from clicking the action button, or from previous results' pagination links ( Next, Previous, 1, 2, 3 ... )
else if ( $http->hasPostVariable( 'ezfind-searchelevateconfigurations-do' ) or
        $Params['SearchQuery'] !== false )
{
    // Check for search query first
    if ( $http->hasPostVariable( 'ezfind-searchelevateconfigurations-searchquery' ) and
         $http->postVariable( 'ezfind-searchelevateconfigurations-searchquery' ) != '' )
    {
        $searchQuery = htmlspecialchars( $http->postVariable( 'ezfind-searchelevateconfigurations-searchquery' ), ENT_QUOTES );
        // Pass the search query on to the template, search will occur there.
        $viewParameters = array_merge( $viewParameters, array( 'search_query' => $searchQuery ) );
    }
    else if ( $Params['SearchQuery'] != '' )
    {
        $searchQuery = htmlspecialchars( $Params['SearchQuery'], ENT_QUOTES );
        // Pass the search query on to the template, search will occur there.
        $viewParameters = array_merge( $viewParameters, array( 'search_query' => $searchQuery ) );
    }
    else
    {
        $feedback['missing_searchquery'] = true;
    }

    // Check language filter
    $languageFilter = false;

    if ( $http->hasPostVariable( 'ezfind-searchelevateconfigurations-language' ) )
        $languageFilter = $http->postVariable( 'ezfind-searchelevateconfigurations-language' );
    elseif ( $Params['Language'] !== false and $Params['Language'] != '' )
        $languageFilter = $Params['Language'];

    // Pass the language filter on to the template, search will occur there.
    if ( $languageFilter and $languageFilter != $wildcard )
        $viewParameters = array_merge( $viewParameters, array( 'language' => htmlspecialchars( $languageFilter, ENT_QUOTES ) ) );


    // Check fuzzy filter
    $fuzzyFilter = false;

    if ( $http->hasPostVariable( 'ezfind-searchelevateconfigurations-fuzzy' ) )
        $fuzzyFilter = true;
    elseif ( $Params['FuzzyFilter'] !== false )
        $fuzzyFilter = true;

    // Pass the fuzzy filter on to the template, search will occur there.
    if ( $fuzzyFilter )
        $viewParameters = array_merge( $viewParameters, array( 'fuzzy_filter' => $fuzzyFilter ) );
}

// Synchronise Elevate configuration with Solr :
else if ( $http->hasPostVariable( 'ezfind-elevate-synchronise' ) )
{
    $solr = new eZSolr();
    //if ( eZFindElevateConfiguration::synchronizeWithSolr() )
    if ( $solr->pushElevateConfiguration() )
    {
        $feedback['synchronisation_ok'] = true;
    }
    else
    {
        $feedback['synchronisation_fail'] = true;
        //TODO: aggregate error messages from multiple shards in eZSolr->pushElevateConfiguration()
        //then change here accordingly
        $feedback['synchronisation_fail_message'] = eZFindElevateConfiguration::$lastSynchronizationError;
    }
}

$viewParameters = array_merge( $viewParameters, array( 'offset' => ( isset( $Params['Offset'] ) and is_numeric( $Params['Offset'] ) ) ? $Params['Offset'] : 0 ) );
$tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'feedback', $feedback );
$tpl->setVariable( 'language_wildcard', $wildcard );
$tpl->setVariable( 'available_translations', $availableTranslations );
$tpl->setVariable( 'baseurl', $thisUrl );

$Result = array();
$Result['content'] = $tpl->fetch( "design:ezfind/elevate.tpl" );
$Result['left_menu'] = "design:ezfind/backoffice_left_menu.tpl";
$Result['path'] = array( array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'eZFind' ) ),
                         array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'Elevation' ) ) );

?>

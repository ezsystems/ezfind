{if is_set( $back_from_browse )|not}
    {def $back_from_browse=false()}
{/if}
{if is_set( $elevateSearchQuery )|not}
    {def $elevateSearchQuery=''}
{/if}

<div class="menu-block">
{def $li_width="_25"}
<ul>
    {* Existing configurations toggle. *}
    {if ezpreference( 'ezfind_elevate_preview_configurations' )}
    <li class="enabled {$li_width}">
    <div class="button-bc"><div class="button-tl"><div class="button-tr"><div class="button-br">
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/0'|ezurl} title="{'Hide preview of existing elevate configurations.'|i18n( 'extension/ezfind/elevate' )}">{'Preview existing configurations'|i18n( 'extension/ezfind/elevate' )}</a>
    </div></div></div></div>
    </li>
    {else}
    <li class="disabled {$li_width}">
    <div class="button-bc"><div class="button-tl"><div class="button-tr"><div class="button-br">
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/1'|ezurl} title="{'Show preview of existing elevate configurations.'|i18n( 'extension/ezfind/elevate' )}">{'Preview existing configurations'|i18n( 'extension/ezfind/elevate' )}</a>
    </div></div></div></div>
    </li>
    {/if}
</ul>
</div>
<div class="break"></div>


<form name="ezfindelevateform" method="post" action={"/ezfind/elevate/"|ezurl}>

{* Title. *}
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">{'Elevation'|i18n( 'extension/ezfind/elevate' )}</h1>
{* DESIGN: Mainline *}<div class="header-mainline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content" style="padding-top: 2px;">
   {* 
    Feedbacks. 
   *}
   
   {if or( is_set( $feedback.missing_searchquery ), is_set( $feedback.missing_object ) )}
       <div class="message-warning">
       <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
       {if is_set( $feedback.missing_searchquery )}
            {'Please enter a search query.'|i18n( 'extension/ezfind/elevate' )}<br />
       {/if}
       {if is_set( $feedback.missing_object )}
           {'Please choose a valid object.'|i18n( 'extension/ezfind/elevate' )}
       {/if}
       </h2>
       </div>
   {/if}
   
   {if is_set( $feedback.missing_language )}
       <div class="message-warning">
       <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
           {'Missing language information.'|i18n( 'extension/ezfind/elevate' )}
       </h2>
       </div>
   {/if}
   
   {if is_set( $feedback.creation_ok )}
       <div class="message-feedback">
       <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
           {'Successful creation of the following Elevate configuration'|i18n( 'extension/ezfind/elevate' )}
            <a href={concat( '/ezfind/elevation_detail/', $feedback.creation_ok.contentobject_id )|ezurl}>({'Details'|i18n( 'extension/ezfind/elevate' )})</a>
       </h2>
       </div>
   {/if}

    {if is_set( $feedback.synchronisation_ok )}
       <div class="message-feedback">
       <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
           {"Successful synchronization of the local Elevate configuration with Solr's."|i18n( 'extension/ezfind/elevate' )}
       </h2>
       </div>
    {/if}

    {if is_set( $feedback.synchronisation_fail )}
        <div class="message-warning">
        <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
            {$feedback.synchronisation_fail_message}
        </h2>
        </div>
    {/if}
        
    {* Synchronise configuration witih Solr *}
    <div class="block">
        {'Synchronise Elevate configuration with Solr'|i18n( 'extension/ezfind/elevate' )}
        <div class="button-right">
            <input class="button" type="submit" name="ezfind-elevate-synchronise" value="{'Synchronise'|i18n( 'extension/ezfind/elevate' )}" title="{'Synchronise the Elevate configuration with Solr.'|i18n( 'extension/ezfind/elevate' )}"/>
        </div>
    </div>

{* DESIGN: Content END *}</div></div></div></div></div></div>
</div>



<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h2 class="context-title">
    <span {if $back_from_browse}style="color: orange;"{/if}>{'Elevate an object'|i18n( 'extension/ezfind/elevate' )}</span>
</h2>
{* DESIGN: Mainline *}<div class="header-subline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content" style="padding-top: 2px;">

<div class="block">
   {'"Elevating an object" for a given search query means that this object will be returned among the very first search results when a search is triggered using this search query.'|i18n( 'extension/ezfind/elevate' )}
</div>
<div class="block">
    <div class="button-left">
       {if $back_from_browse|not}
           <label for="ezfind-elevate-searchquery" style="display: inline; font-weight:normal;">{'Search query'|i18n( 'extension/ezfind/elevate' )}:</label>
           <input type="text" id="ezfind-elevate-searchquery" name="ezfind-elevate-searchquery" size="15" value="" title="{'Search query to elevate the object for.'|i18n( 'extension/ezfind/elevate' )}"/>&nbsp;
           <input class="button" type="submit" name="ezfind-elevate-browseforobject" value="{'Elevate object'|i18n( 'extension/ezfind/elevate' )}" title="{'Browse for the object to associate elevation to.'|i18n( 'extension/ezfind/elevate' )}"/>
       {else}
           {'Elevate %objectlink with &nbsp;  %searchquery &nbsp;  for language:'|i18n( 'extension/ezfind/elevate', '', 
                                                                                        hash( '%objectlink',  concat( '<a href=', $elevatedObject.main_node.url_alias|ezurl, '>', $elevatedObject.main_node.name, '</a>' ),
                                                                                              '%searchquery', concat( '<input type="text" name="ezfind-elevate-searchquery" size="15" value="', $elevateSearchQuery|wash, '" title="', 'Search query to elevate the object for.'|i18n( 'extension/ezfind/elevate' ) , '"/>' ) ))}
                                                                                                              
            <select name="ezfind-elevate-language">
                   <option value="{$language_wildcard}">{'All'|i18n( 'extension/ezfind/elevate' )}</option>
                   {foreach $elevatedObject.languages as $lang}
                       <option value="{$lang.locale}">{$lang.name}</option>
                   {/foreach}
            </select>     
            
            <input type="hidden" name="elevateObjectID" value="{$elevatedObject.id}">            
            <input class="button" type="submit" name="ezfind-elevate-do" value="{'Elevate'|i18n( 'extension/ezfind/elevate' )}" title="{'Store elevation'|i18n( 'extension/ezfind/elevate' )}"/>
            <input class="button" type="submit" name="ezfind-elevate-cancel" value="{'Cancel'|i18n( 'extension/ezfind/elevate' )}" title="{'Cancel elevation'|i18n( 'extension/ezfind/elevate' )}"/>
       {/if}
    </div>
    <div class="break"></div>
</div>
{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>



{* Search for elevated objects window *}
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h2 class="context-title">{'Search for elevated objects'|i18n( 'extension/ezfind/elevate' )}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="block">
    <fieldset>
    <legend>{'By search query'|i18n( 'extension/ezfind/elevate' )}</legend>
    <div class="block">

    <div class="button-left">
        <label for="ezfind-searchelevateconfigurations-searchquery" style="display: inline; font-weight:normal;">{'Search query'|i18n( 'extension/ezfind/elevate' )}:
        <input type="text" id="ezfind-searchelevateconfigurations-searchquery" name="ezfind-searchelevateconfigurations-searchquery" size="15" value="{if is_set( $view_parameters.search_query )}{$view_parameters.search_query}{/if}" title="{'Search query to elevate the object for.'|i18n( 'extension/ezfind/elevate' )}" />
        </label>
    </div>
    
    <div class="button-left">
        <label style="display: inline; font-weight:normal;">{'Language'|i18n( 'extension/ezfind/elevate' )}:        
        <select name="ezfind-searchelevateconfigurations-language" title="{'Select a translation to narrow down the search.'|i18n( 'extension/ezfind/elevate' )}">
           <option value="{$language_wildcard}" {if is_set( $view_parameters.language )|not}selected="selected"{/if} >{'All'|i18n( 'extension/ezfind/elevate' )}</option>
           {foreach $available_translations as $translation}
               <option value="{$translation.locale}" {if and( is_set( $view_parameters.language ), eq( $view_parameters.language, $translation.locale ))}selected="selected"{/if}>{$translation.name}</option>
           {/foreach}
        </select>
        </label>
    </div>
    
    <div class="button-left">
        <label for="ezfind-searchelevateconfigurations-fuzzy" style="display: inline; font-weight:normal;">{'Fuzzy match'|i18n( 'extension/ezfind/elevate' )}:
        <input type="checkbox" id="ezfind-searchelevateconfigurations-fuzzy" name="ezfind-searchelevateconfigurations-fuzzy" {if is_set( $view_parameters.fuzzy_filter )}checked="checked"{/if} title="{'Fuzzy match on the search query.'|i18n( 'extension/ezfind/elevate' )}"/>
        </label>
    </div>
    
    <div class="button-left">
       <input class="button" type="submit" name="ezfind-searchelevateconfigurations-do" value="{'Filter'|i18n( 'extension/ezfind/elevate' )}" title="{'Find elevate configurations matching the search query entered.'|i18n( 'extension/ezfind/elevate' )}"/>
    </div>

    </div>
    </fieldset>
</div>
<div class="block">
    <fieldset>
       <legend>{'By object'|i18n( 'extension/ezfind/elevate' )}</legend>       
       <input class="button" type="submit" name="ezfind-searchelevateconfigurations-browse" value="{'Browse'|i18n( 'extension/ezfind/elevate' )}" title="{'Find elevate configurations matching the search query entered.'|i18n( 'extension/ezfind/elevate' )}"/>
   </fieldset>
</div>
{* DESIGN: Content END *}</div></div></div></div></div></div>
</div>


{* Existing configurations *}

{def $limit = min( ezpreference( 'ezfind_elevate_preview_configurations' ), 3)|choose( 10, 10, 25, 50 )
     $params = hash( 'offset', $view_parameters.offset,
                     'limit',  $limit )
     $paramsForCount = hash( 'countOnly', true() )}
     
{* Searching for elevate configurations for a given search query, alter the fetch parameters *}
{if is_set( $view_parameters.search_query )}

    {def $searchQueryHash = hash( 'searchQuery', $view_parameters.search_query )}
    {if is_set( $view_parameters.fuzzy_filter )}
        {set $searchQueryHash = $searchQueryHash|merge( hash( 'fuzzy', true() ) )}
    {/if}
    
    {set $params = $params|merge( hash( 'searchQuery', $searchQueryHash ) )}
    {set $paramsForCount = $paramsForCount|merge( hash( 'searchQuery', $searchQueryHash ) )}
{/if}

{* Searching for elevate configurations, filtering on a given languageCode, alter the fetch parameters *}
{if is_set( $view_parameters.language )}
    {set $params = $params|merge( hash( 'languageCode', $view_parameters.language ) )}
    {set $paramsForCount = $paramsForCount|merge( hash( 'languageCode', $view_parameters.language ) )}
{/if}


{if or( ezpreference( 'ezfind_elevate_preview_configurations' ), is_set( $view_parameters.search_query ) )}
   {def $configurations = fetch( 'ezfind', 'elevateConfiguration',  $params )
        $configurations_count = fetch( 'ezfind', 'elevateConfiguration', $paramsForCount )}

<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h2 class="context-title">
    {if and( is_set( $view_parameters.search_query ), ne( $view_parameters.search_query, '' ) )}
        <span style="color: orange;">
        {'Objects elevated by "%search_query"'|i18n( 'extension/ezfind/elevate', '', hash( '%search_query', $view_parameters.search_query ) )}
        {if is_set( $view_parameters.fuzzy_filter )}
            ({'fuzzy match'|i18n( 'extension/ezfind/elevate' )})        
        {/if}
        </span>
    {else}
        {'Existing configurations'|i18n( 'extension/ezfind/elevate' )}    
    {/if}
</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

{* Items per page and view mode selector. *}
<div class="context-toolbar">
<div class="button-left">
    <p class="table-preferences">
    {switch match=$limit}
        {case match=25}
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a>
        <span class="current">25</span>
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a>
        {/case}

        {case match=50}
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a>
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a>
        <span class="current">50</span>
        {/case}

        {case}
        <span class="current">10</span>
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a>
        <a href={'/user/preferences/set/ezfind_elevate_preview_configurations/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a>
        {/case}

    {/switch}
    </p>
</div>
<div class="break"></div>
</div>


<table class="list" cellspacing="0">
{if eq( $configurations_count, 0 )}
    <tr class="bgdark">
        <th>{'No existing Elevate configuration.'|i18n( 'extension/ezfind/elevate' )}</th>
    </tr>
    </table>
{else}
   {if is_set( $view_parameters.search_query )}
        <tr class="bgdark">
            <th>{'Content object'|i18n( 'extension/ezfind/elevate' )}</th>
            <th>{'Actions'|i18n( 'extension/ezfind/elevate' )}</th>
        </tr>  
        {foreach $configurations as $object sequence array( 'bglight', 'bgdark' ) as $tdClass }
           <tr class="{$tdClass}">
           <td>
               {node_view_gui content_node=$object.main_node view='line'}
           </td>
           <td width="10%">
              {if is_set( $view_parameters.fuzzy_filter )}
                   <a href={concat( '/ezfind/elevation_detail/', $object.id, '/(search_query)/', $view_parameters.search_query, '/(fuzzy_filter)/', $view_parameters.fuzzy_filter )|ezurl} title="{'See elevate configuration details for \'%objectName\''|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $object.name ) )}"><img src={'edit.gif'|ezimage} /></a>
                   <a href={concat( '/ezfind/elevation_detail/', $object.id, '/(search_query)/', $view_parameters.search_query, '/(fuzzy_filter)/', $view_parameters.fuzzy_filter )|ezurl} title="{'Remove elevation by \'%searchQuery\' for \'%objectName\'.'|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $object.name, '%searchQuery', $view_parameters.search_query ) )}"><img src={'trash-icon-16x16.gif'|ezimage} /></a>                   
               {else}
                  <a href={concat( '/ezfind/elevation_detail/', $object.id, '/(search_query)/', $view_parameters.search_query )|ezurl} title="{'See elevate configuration details for \'%objectName\''|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $object.name ) )}"><img src={'edit.gif'|ezimage} /></a>
                  <a href={concat( '/ezfind/remove_elevation/', $object.id, '/', $view_parameters.search_query )|ezurl} title="{'Remove elevation by \'%searchQuery\' for \'%objectName\'.'|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $object.name, '%searchQuery', $view_parameters.search_query ) )}"><img src={'trash-icon-16x16.gif'|ezimage} /></a>               
               {/if}              
           </td>           
           </tr>
        {/foreach} 
   {else}
       <tr class="bgdark">
           <th>{'Search query'|i18n( 'extension/ezfind/elevate' )}</th>
           <th>{'Content object'|i18n( 'extension/ezfind/elevate' )}</th>
           <th>{'Language'|i18n( 'extension/ezfind/elevate' )}</th>
           <th>{'Actions'|i18n( 'extension/ezfind/elevate' )}</th>
       </tr>
       
       {def $tmp_obj=false()}
       {foreach $configurations as $conf sequence array( 'bglight', 'bgdark' ) as $tdClass }
          {set $tmp_obj=fetch( 'content', 'object', hash( 'object_id', $conf.contentobject_id ) )}
          <tr class="{$tdClass}">
              <td><a href={concat( $baseurl, '/(search_query)/', $conf.search_query )|ezurl} title="{'See all objects elevated by \'%searchQuery\''|i18n( 'extension/ezfind/elevate', '', hash( '%searchQuery', $conf.search_query ) )}">{$conf.search_query}</a></td>
              <td><a href={$tmp_obj.main_node.url_alias|ezurl}>{$tmp_obj.name|wash}</a></td>
              <td>
                  {if eq( $conf.language_code, $language_wildcard )}
                     <em>{'All'|i18n( 'extension/ezfind/elevate' )}</em>
                  {else}
                     {$conf.language_code}
                  {/if}
              </td>
              <td width="10%">
                 <a href={concat( '/ezfind/elevation_detail/', $tmp_obj.id )|ezurl} title="{'See elevate configuration details for \'%objectName\''|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $tmp_obj.name ) )}"><img alt="{'Edit'|i18n( 'extension/ezfind/elevate' )}" src={'edit.gif'|ezimage} /></a>
                  <a href={concat( '/ezfind/remove_elevation/', $tmp_obj.id, '/', $conf.search_query, '/', $conf.language_code )|ezurl} title="{'Remove elevation by \'%searchQuery\' for \'%objectName\'.'|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $tmp_obj.name, '%searchQuery', $conf.search_query ) )}"><img alt="{'Trash'|i18n( 'extension/ezfind/elevate' )}" src={'trash-icon-16x16.gif'|ezimage} /></a>
              </td>
          </tr>        
       {/foreach}
   {/if}
    </table>
        
    
   <div class="context-toolbar">
   {include name=navigator
            uri='design:navigator/alphabetical.tpl'
            page_uri='/ezfind/elevate'
            item_count=$configurations_count
            view_parameters=$view_parameters
            item_limit=$limit}
   </div>
{/if}

{* DESIGN: Content END *}</div></div></div></div></div></div>
</div>
{/if}
</form>
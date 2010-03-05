{def $i18n_object_name = hash( '%objectName', concat( '&lt;', $elevatedObject.name|wash, '&gt;' ) )}

{* Title. *}
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">{'Elevation detail for object %objectName'|i18n( 'extension/ezfind/elevate', '', $i18n_object_name )}</h1>
{* DESIGN: Mainline *}<div class="header-mainline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content" style="padding-top: 2px;">
   {* 
    Feedbacks. 
   *}
    {if is_set( $feedback.missing_object )}
       <div class="message-warning">
       <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
           {"Missing content object."|i18n( 'extension/ezfind/elevate' )}
       </h2>
       </div>
    {/if}
{* DESIGN: Content END *}</div></div></div></div></div></div>
</div>


{if is_unset( $feedback.missing_object )}
{def $limit = $view_parameters.limit}

<form name="ezfindelevationdetailform" method="post" action={"/ezfind/elevate/"|ezurl}>
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h2 class="context-title">
    {'Elevate %objectName'|i18n( 'extension/ezfind/elevate', '', $i18n_object_name )}
</h2>
{* DESIGN: Mainline *}<div class="header-subline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="block">
    {'"Elevating an object" for a given search query means that this object will be returned among the very first search results when a search is triggered using this search query.'|i18n( 'extension/ezfind/elevate' )}
</div>

<fieldset>
    <legend>{'Elevate'|i18n( 'extension/ezfind/elevate' )}</legend>
    <div class="block">
       <div class="button-left">
            <label style="display: inline; font-weight:normal;">{'Elevate %objectlink with:'|i18n( 'extension/ezfind/elevate', '', hash( '%objectlink',  concat( '<a href=', $elevatedObject.main_node.url_alias|ezurl, '>', $elevatedObject.main_node.name, '</a>' ) ))}
            <input type="text" name="ezfind-elevate-searchquery" size="15" value="{$elevateSearchQuery|wash}" title="{'Search query to elevate the object for.'|i18n( 'extension/ezfind/elevate' )}"/>
            </label>
       </div>
       <div class="button-left">
            <label style="display: inline; font-weight:normal;">{'for language:'|i18n( 'extension/ezfind/elevate')}
            <select name="ezfind-elevate-language">
                   <option value="{$language_wildcard}">{'All'|i18n( 'extension/ezfind/elevate' )}</option>
                   {foreach $elevatedObject.languages as $lang}
                       <option value="{$lang.locale}">{$lang.name}</option>
                   {/foreach}
            </select>     
            </label>
        </div>
        <div class="button-left">
            <input type="hidden" name="elevateObjectID" value="{$elevatedObject.id}" />
            <input class="button" type="submit" name="ezfind-elevate-do" value="{'Elevate'|i18n( 'extension/ezfind/elevate' )}" title="{'Store elevation'|i18n( 'extension/ezfind/elevate' )}"/>
            <input type="hidden" name="redirectURI" value="{$baseurl}" />
       </div>
    </div>
</fieldset>

{* DESIGN: Content END *}</div></div></div></div></div></div>
</div>
</form>

{* Existing configurations *}
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h2 class="context-title">
    {'Existing configurations for %objectName'|i18n( 'extension/ezfind/elevate', '', $i18n_object_name )}
    {if is_set( $view_parameters.language )}{'in %selectedLanguage'|i18n( 'extension/ezfind/elevate', '', hash( '%selectedLanguage', $selectedLocale.name ))}{/if}
    {if is_set( $view_parameters.search_query )}{'containing \'%searchQuery\''|i18n( 'extension/ezfind/elevate', '', hash( '%searchQuery', $view_parameters.search_query ))}{/if}
    {if is_set( $view_parameters.fuzzy_filter )}
        ({'fuzzy match'|i18n( 'extension/ezfind/elevate' )})        
    {/if}        
</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

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
   <tr class="bgdark">
       <th>{'Search query'|i18n( 'extension/ezfind/elevate' )}</th>
       <th>{'Language'|i18n( 'extension/ezfind/elevate' )}</th>
       <th>{'Actions'|i18n( 'extension/ezfind/elevate' )}</th>
   </tr>
   
   {foreach $configurations as $conf sequence array( 'bglight', 'bgdark' ) as $tdClass }
      <tr class="{$tdClass}">
           <td><a href={concat( $baseurl, '/(search_query)/', $conf.search_query )|ezurl} title="{'See all objects elevated by \'%searchQuery\''|i18n( 'extension/ezfind/elevate', '', hash( '%searchQuery', $conf.search_query ) )}">{$conf.search_query}</a></td>
          <td>
              {if eq( $conf.language_code, $language_wildcard )}
                 <em>{'All'|i18n( 'extension/ezfind/elevate' )}</em>
              {else}
                 {$conf.language_code}
              {/if}
          </td>
           <td width="10%">
              <a href={concat( '/ezfind/remove_elevation/', $elevatedObject.id, '/', $conf.search_query, '/', $conf.language_code )|ezurl} title="{'Remove elevation by \'%searchQuery\' for \'%objectName\'.'|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', $elevatedObject.name, '%searchQuery', $conf.search_query ) )}"><img alt="{'Trash'|i18n( 'extension/ezfind/elevate' )}" src={'trash-icon-16x16.gif'|ezimage} /></a>
           </td>          
      </tr>        
   {/foreach}
    </table>
    
   <div class="context-toolbar">
   {include name=navigator
            uri='design:navigator/alphabetical.tpl'
            page_uri=$baseurl
            item_count=$configurations_count
            view_parameters=$view_parameters
            item_limit=$limit}
   </div>
{/if}

{* DESIGN: Content END *}</div></div></div>
<form name="ezfindelevateform" method="post" action={$baseurl|ezurl}>

<div class="controlbar subitems-controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">

<div class="block">
    <div class="button-left">
    <label for="ezfind-elevationdetail-filter-searchquery" style="display: inline;">{'Search query'|i18n( 'extension/ezfind/elevate' )}:
    <input type="text" id="ezfind-elevationdetail-filter-searchquery" name="ezfind-elevationdetail-filter-searchquery" size="15" value="{$view_parameters.search_query}" title="{'Search query to filter the result set on.'|i18n( 'extension/ezfind/elevate' )}"/>
    </label>
    </div>

    <div class="button-left">
    <label for="ezfind-elevationdetail-filter-language" style="display: inline;">{'Language'|i18n( 'extension/ezfind/elevate' )}:
    <select name="ezfind-elevationdetail-filter-language" id="ezfind-elevationdetail-filter-language">
           <option value="{$language_wildcard}" {if is_set( $view_parameters.language )|not}selected="selected"{/if}>{'All'|i18n( 'extension/ezfind/elevate' )}</option>
           {foreach $elevatedObject.languages as $lang}
               <option value="{$lang.locale}" {if and( is_set( $view_parameters.language ), eq( $view_parameters.language, $lang.locale ))}selected="selected"{/if}>{$lang.name}</option>
           {/foreach}
    </select>
    </label>
    </div>

    <div class="button-left">
    <label for="ezfind-elevationdetail-filter-fuzzy" style="display: inline;">{'Fuzzy match'|i18n( 'extension/ezfind/elevate' )}:
    <input type="checkbox" id="ezfind-elevationdetail-filter-fuzzy" name="ezfind-elevationdetail-filter-fuzzy" {if is_set( $view_parameters.fuzzy_filter )}checked="checked"{/if} title="{'Fuzzy match on the search query.'|i18n( 'extension/ezfind/elevate' )}"/>&nbsp;    
    </label>
    <input class="button" type="submit" name="ezfind-elevationdetail-filter-do" value="{'Filter'|i18n( 'extension/ezfind/elevate' )}" title="{'Filter configurations by language'|i18n( 'extension/ezfind/elevate' )}"/>
    </div>
    
    <div class="break"></div>
</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>
</form>

</div>
{/if} {* END if is_set( $feedback.missing_object )|not *}
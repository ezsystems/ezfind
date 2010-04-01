{* Title. *}
<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">
{if is_set( $feedback.removal_back_link )}
    {"Removal confirmed ( for %objectName ) :"|i18n( 'extension/ezfind/elevate', '', hash( '%objectName', concat( '<a href=', $elevatedObject.main_node.url_alias|ezurl, '>', $elevatedObject.name, '</a>' ) ) )}
{else}
   {"Confirm removal of the following Elevate configuration ( for %objectName ) :"|i18n( 'extension/ezfind/elevate', '', 
                                                                                         hash( '%objectName', concat( '<a href=', $elevatedObject.main_node.url_alias|ezurl, '>', $elevatedObject.name, '</a>' ) ) )}
{/if}                                                                                      
</h1>
{* DESIGN: Mainline *}<div class="header-mainline"></div>
{* DESIGN: Header END *}</div></div></div></div></div></div>
{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr">{* <div class="box-bl"><div class="box-br"> *}<div class="box-content" style="padding-top: 2px;">

<form name="ezfindremoveelevationform" method="post" action={concat( "/ezfind/remove_elevation/", $elevatedObject.id )|ezurl}>
<input type="hidden" name="ezfind-removeelevation-objectid" value="{$elevatedObject.id}" />
<input type="hidden" name="ezfind-removeelevation-searchquery" value="{$feedback.confirm_remove.search_query|wash}" />
<input type="hidden" name="ezfind-removeelevation-languagecode" value="{$feedback.confirm_remove.language_code|wash}" />

<table class="list cache" cellspacing="0">
   <tr>
       <th>{'Search query'|i18n( 'extension/ezfind/elevate' )}</th>
       <th>{'Language'|i18n( 'extension/ezfind/elevate' )}</th>
   </tr>
    <tr class="bgdark">
        <td><a href={concat( '/ezfind/elevate/(search_query)/', $feedback.confirm_remove.search_query|wash )|ezurl} title="{'See all objects elevated by \'%searchQuery\''|i18n( 'extension/ezfind/elevate', '', hash( '%searchQuery', $feedback.confirm_remove.search_query|wash ) )}">{$feedback.confirm_remove.search_query|wash}</a></td>
        <td>
            {if eq( $feedback.confirm_remove.language_code, $language_wildcard )}
               <em>{'All'|i18n( 'extension/ezfind/elevate' )}</em>
            {else}
               {$feedback.confirm_remove.language_code}
            {/if}
        </td>
    </tr> 
</table>

{* DESIGN: Content END *}</div></div></div>{* </div></div> *}</div>
<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
    {if is_set( $feedback.removal_back_link )}
        <a href={$feedback.removal_back_link|ezurl}>{'Back'|i18n( 'extension/ezfind/elevate' )}</a>
    {else}
       <input class="button" type="submit" name="ezfind-removeelevation-do" value="{'Remove'|i18n( 'extension/ezfind/elevate' )}" title="{'Confirm removal of the elevate configuration'|i18n( 'extension/ezfind/elevate' )}"/>
       <input class="button" type="submit" name="ezfind-removeelevation-cancel" value="{'Cancel'|i18n( 'extension/ezfind/elevate' )}" title="{'Cancel removal of the elevate configuration'|i18n( 'extension/ezfind/elevate' )}"/>
    {/if}    
</div>

</form>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>
</div>
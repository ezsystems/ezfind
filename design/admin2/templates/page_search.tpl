{def $search_node_id = first_set( $search_subtree_array[0], $module_result.path[0].node_id, 1 )
     $search_title = "Search in all content"|i18n( 'design/admin/pagelayout' )}
<div class="searchblock">
<form action={'/content/search/'|ezurl} method="get">
    {if $ui_context_edit}
        <input id="searchtext" name="SearchText" class="disabled" type="text" size="20" value="{if is_set( $search_text )}{$search_text|wash}{/if}" disabled="disabled" title="{$search_title|wash}" />
        <input type="hidden" name="SubTreeArray" value="{$search_node_id}" />
        <input id="searchbutton" class="button-disabled hide" name="SearchButton" type="submit" value="{'Search'|i18n( 'design/admin/pagelayout' )}" disabled="disabled" />
        <p class="advanced hide"><span class="disabled">{'Advanced'|i18n( 'design/admin/pagelayout' )}</span></p>
    {else}
        {if $search_node_id|gt( 1 )}
            {set $search_title = "Search in '%node'"|i18n( 'design/admin/pagelayout',, hash( '%node', fetch( 'content', 'node', hash( 'node_id', $search_node_id ) ).name ) )}
        {/if}
        <div id="ezautocomplete">
            <input id="searchtext" name="SearchText" type="text" size="20" value="{if is_set( $search_text )}{$search_text|wash}{/if}" title="{$search_title|wash}" />
            <input type="hidden" name="SubTreeArray" value="{$search_node_id}" />
            <input id="searchbutton" class="button hide" name="SearchButton" type="submit" value="{'Search'|i18n( 'design/admin/pagelayout' )}" />
            <div id="ezautocompletecontainer"></div>
        {if eq( $ui_context, 'browse' ) }
            <input name="Mode" type="hidden" value="browse" />
            <input name="BrowsePageLimit" type="hidden" value="{min( ezpreference( 'admin_list_limit' ), 3)|choose( 10, 10, 25, 50 )}" />
            <p class="advanced hide"><span class="disabled">{'Advanced'|i18n( 'design/admin/pagelayout' )}</span></p>
        {else}
            <p class="advanced hide"><a href={'/content/advancedsearch'|ezurl} title="{'Advanced search.'|i18n( 'design/admin/pagelayout' )}">{'Advanced'|i18n( 'design/admin/pagelayout' )}</a></p>
        {/if}
            <div class="searchbuttonfield" id="searchbuttonfield"></div>
        </div>
    {/if}
</form>
</div>

{ezscript_require( array('ezjsc::jquery', 'ezjsc::yui2', 'ezajax_autocomplete.js') )}
<script type="text/javascript">
jQuery('#ezautocompletecontainer').css('width', jQuery('input#searchtext').width() + 25);
var ezAutoHeader = eZAJAXAutoComplete();
ezAutoHeader.init({ldelim}

    url: "{'ezjscore/call/ezfind::autocomplete'|ezurl('no')}",
    inputid: 'searchtext',
    containerid: 'ezautocompletecontainer',
    minquerylength: {ezini( 'AutoCompleteSettings', 'MinQueryLength', 'ezfind.ini' )},
    resultlimit: {ezini( 'AutoCompleteSettings', 'Limit', 'ezfind.ini' )}

{rdelim});

{literal}
(function($) {
	if ( document.getElementById('searchbuttonfield') ) {

		jQuery('#searchbuttonfield').click(function() {
            if ( jQuery('#searchtext').val() === jQuery('#searchtext').attr('title') )
                return;

        	jQuery('#searchbutton').click();
        });
    }
})( jQuery );
{/literal}
</script>
{undef $search_node_id}

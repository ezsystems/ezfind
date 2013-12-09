<div id="searchbox">
  <form action={"/content/search"|ezurl}>
    <label for="searchtext" class="hide">{'Search text:'|i18n('design/ezwebin/pagelayout')}</label>
    {if $pagedata.is_edit}
    <input id="searchtext" name="SearchText" type="text" value="" size="12" disabled="disabled" />
    <input id="searchbutton" class="button-disabled" type="submit" value="{'Search'|i18n('design/ezwebin/pagelayout')}" alt="{'Search'|i18n('design/ezwebin/pagelayout')}" disabled="disabled" />
    {else}
    <div class="yui3-skin-sam ez-autocomplete">
        <input id="searchtext" name="SearchText" type="text" value="" size="12" />
        <input id="searchbutton" class="button" type="submit" value="{'Search'|i18n('design/ezwebin/pagelayout')}" alt="{'Search'|i18n('design/ezwebin/pagelayout')}" />
    </div>
    {if eq( $ui_context, 'browse' )}
     <input name="Mode" type="hidden" value="browse" />
    {/if}

    {/if}
  </form>
</div>

{if $pagedata.is_edit|not()}

{ezscript_require( array('ezjsc::yui3', 'ezajax_autocomplete.js') )}
<script type="text/javascript">

YUI(YUI3_config).use('ezfindautocomplete', function (Y) {ldelim}
    Y.eZ.initAutoComplete({ldelim}
        url: "{'ezjscore/call/ezfind::autocomplete'|ezurl('no')}",
        inputSelector: '#searchtext',
        minQueryLength: {ezini( 'AutoCompleteSettings', 'MinQueryLength', 'ezfind.ini' )},
        resultLimit: {ezini( 'AutoCompleteSettings', 'Limit', 'ezfind.ini' )}
    {rdelim});
{rdelim});

</script>

{/if}

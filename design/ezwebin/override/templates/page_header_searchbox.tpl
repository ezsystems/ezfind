<div id="searchbox">
  <form action={"/content/search"|ezurl}>
    <label for="searchtext" class="hide">{'Search text:'|i18n('design/ezwebin/pagelayout')}</label>
    {if $pagedata.is_edit}
    <input id="searchtext" name="SearchText" type="text" value="" size="12" disabled="disabled" />
    <input id="searchbutton" class="button-disabled" type="submit" value="{'Search'|i18n('design/ezwebin/pagelayout')}" alt="{'Search'|i18n('design/ezwebin/pagelayout')}" disabled="disabled" />
    {else}
    <div id="ezautocomplete">
        <input id="searchtext" name="SearchText" type="text" value="" size="12" />
        <input id="ezautocompletebutton" class="button" type="submit" value="{'Search'|i18n('design/ezwebin/pagelayout')}" alt="{'Search'|i18n('design/ezwebin/pagelayout')}" />
        <div id="ezautocompletecontainer"></div>
    </div>
    {if eq( $ui_context, 'browse' )}
     <input name="Mode" type="hidden" value="browse" />
    {/if}
    
    {/if}
  </form>
</div>

{if $pagedata.is_edit|not()}

{ezscript_require( array('ezjsc::yui2', 'ezajax_autosuggest.js') )}
<script type="text/javascript">
var cfg = {ldelim}
                url: "{'ezjscore/call/ezfind::autocomplete'|ezurl('no')}",
                minquerylength: 1,
                resultlimit: 20
           {rdelim};
ezajaxautosuggest.init(cfg);
</script>

{/if}
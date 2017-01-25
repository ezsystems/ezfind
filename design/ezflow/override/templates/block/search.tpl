<h2>{$block.name|wash()}</h2>
<form id="search-form-{$block.id}" action="{'content/search'|ezurl('no')}" method="post">
<div class="yui3-skin-sam ez-autocomplete">
    <input id="search-string-{$block.id}" type="text" name="SearchStr" value="" />
    <input id="search-button-{$block.id}" class="button" type="submit" name="SearchButton" value="{'Search'|i18n( 'design/ezflow/block/search' )}" />
    {def $customAttributesString='['}
    {foreach $block.custom_attributes as $name => $value}
        <input id="search-custom-attribute-{$name}-{$block.id}" type="hidden" name="{$name}" value="{$value}" />
        {set $customAttributesString = concat( $customAttributesString, '"#search-custom-attribute-', $name, '-', $block.id, '", ' )}
    {/foreach}
    {set $customAttributesString=concat( $customAttributesString|trim( ' ,' ), ']' )}
</div>
</form>

<div id="search-results-{$block.id}"></div>
{ezscript_require( array( 'ezjsc::jquery', 'ezjsc::yui3', 'ezjsc::yui3io', 'ezjsc::yui2', 'ezajaxsearch.js', 'ezajax_autocomplete.js' ) )}
{ezcss_require( 'ezajax_autocomplete.css' )}


<script type="text/javascript">

YUI(YUI3_config).use('ezfindautocomplete', function (Y) {ldelim}
    Y.eZ.initAutoComplete({ldelim}
        url: "{'ezjscore/call/ezfind::autocomplete'|ezurl('no')}",
        inputSelector: '#search-string-{$block.id}',
        minQueryLength: {ezini( 'AutoCompleteSettings', 'MinQueryLength', 'ezfind.ini' )},
        resultLimit: {ezini( 'AutoCompleteSettings', 'Limit', 'ezfind.ini' )}
    {rdelim});
{rdelim});


eZAJAXSearch.cfg = {ldelim}
                        //backendUri: 'ezfind::search',
                        customSearchAttributes: {$customAttributesString},
                        noresultstring: '{'No search results...'|i18n( 'extension/ezfind/ajax-search' )}',
                        searchstring: '#search-string-{$block.id}',
                        searchbutton: '#search-button-{$block.id}',
                        searchresults: '#search-results-{$block.id}',
                        dateformattype: 'shortdatetime',
                        spellcheck: '#spellcheck-{$block.id} a',
                        spellchecktemplate: '<div id="spellcheck-{$block.id}" class="ajax-search-spellcheck">{'Did you mean'|i18n( 'extension/ezfind/ajax-search' )}"<a>{ldelim}spellcheck{rdelim}</a>"?</div>',
                        facetsmainlisttemplate: '<div class="ajax-search-facets" id="facets-{$block.id}">{'Refine with facets'|i18n( 'extension/ezfind/ajax-search' )}<ul>{ldelim}inner_facet_list{rdelim}</ul></div>',
                        facetsinnerlisttemplate: '<li>{ldelim}facet_name{rdelim}</li><ul>{ldelim}facet_element{rdelim}</ul>',
                        facetselementtemplate: '<li><a href="{ldelim}link{rdelim}">{ldelim}value{rdelim}</a> {ldelim}count{rdelim}</li>',
                        resulttemplate: '<div class="result-item float-break"><div class="item-title"><a href="{ldelim}url_alias{rdelim}">{ldelim}title{rdelim}</a></div><div class="item-published-date">[{ldelim}class_name{rdelim}] {ldelim}date{rdelim}</div></div>'
                   {rdelim};
eZAJAXSearch.init();
</script>

{def $search=false()}
{if $use_template_search}
    {set $page_limit=10}
    {set $search=fetch(content,search,
                       hash(text,$search_text,
                            section_id,$search_section_id,
                            subtree_array,$search_subtree_array,
                            sort_by,array('modified',false()),
                            offset,$view_parameters.offset,
                            limit,$page_limit))}
    {set $search_result=$search['SearchResult']}
    {set $search_count=$search['SearchCount']}
    {def $search_extras=$search['SearchExtras']}
    {set $stop_word_array=$search['StopWordArray']}
    {set $search_data=$search}
{/if}
<div class="content-search">

<form action={"/content/search/"|ezurl} method="get">

<h1>{"Search"|i18n("design/base")}</h1>

    <input class="halfbox" type="text" size="20" name="SearchText" id="Search" value="{$search_text|wash}" />
    <input class="button" name="SearchButton" type="submit" value="{'Search'|i18n('design/base')}" />

    {def $adv_url=concat('/content/advancedsearch/',$search_text|count_chars()|gt(0)|choose('',concat('?SearchText=',$search_text|urlencode)))|ezurl}
    <label>{"For more options try the %1Advanced search%2"|i18n("design/base","The parameters are link start and end tags.",array(concat("<a href=",$adv_url,">"),"</a>"))}</label>

{if $stop_word_array}
    <p>
    {"The following words were excluded from the search"|i18n("design/base")}:
    {foreach $stop_word_array as $stopWord}
        {$stopWord.word|wash}
        {delimiter}, {/delimiter}
    {/foreach}
    </p>
{/if}

{switch name=Sw match=$search_count}
  {case match=0}
  <div class="warning">
  <h2>{'No results were found when searching for "%1"'|i18n("design/base",,array($search_text|wash))}</h2>
  {if $search_extras.hasError}
      {$search_extras.error|wash}
  {/if}
  </div>
    <p>{'Search tips'|i18n('design/base')}</p>
    <ul>
        <li>{'Check spelling of keywords.'|i18n('design/base')}</li>
        <li>{'Try changing some keywords eg. car instead of cars.'|i18n('design/base')}</li>
        <li>{'Try more general keywords.'|i18n('design/base')}</li>
        <li>{'Fewer keywords gives more results, try reducing keywords until you get a result.'|i18n('design/base')}</li>
    </ul>
  {/case}
  {case}
  <div class="feedback">
  <h2>{'Search for "%1" returned %2 matches'|i18n("design/base",,array($search_text|wash,$search_count))}</h2>
  <p>{'Core search time: %1 msecs'|i18n( 'ezfind',,array( $search_extras.responseHeader.QTime|wash ) )}</p>
  </div>
  {/case}
{/switch}
{* Experimental *}
{*
<h3>Categories matched</h3>
<p>
{foreach $search_extras.FacetArray.facet_fields.m_class_name as $keyword_name => $keyword_count}
{$keyword_name}({$keyword_count})&nbsp;

{/foreach}
</p>
*}
<table style="width: 100%;margin-bottom:2ex;margin-top:2ex;">
{if $search_result }
    {foreach $search_result as $result
             sequence array('bglight','bgdark') as $bgColor}
        <tr class="{$bgColor}">
        {node_view_gui view=search sequence=$bgColor content_node=$result}
        </tr>
    {/foreach}
{/if}
</table>

{include name=Navigator
         uri='design:navigator/google.tpl'
         page_uri='/content/search'
         page_uri_suffix=concat('?SearchText=',$search_text|urlencode,$search_timestamp|gt(0)|choose('',concat('&SearchTimestamp=',$search_timestamp)))
         item_count=$search_count
         view_parameters=$view_parameters
         item_limit=$page_limit}

</form>
<p class="small"><i>{$search_extras.engine}</i></p>
{*$search_extras|attribute(show)*}
</div>

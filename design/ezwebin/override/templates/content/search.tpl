{def $search=false()}
{if $use_template_search}
    {set $page_limit=10}
    {set $search=fetch( ezfind,search,
                        hash( 'query', $search_text,
                              'offset', $view_parameters.offset,
                              'limit', $page_limit,
                              'sort_by', hash( 'score', 'desc' ),
                              'facet', fetch( ezfind, facetParameters ),
                              'filter', fetch( ezfind, filterParameters ) ) )}
    {set $search_result=$search['SearchResult']}
    {set $search_count=$search['SearchCount']}
    {def $search_extras=$search['SearchExtras']}
    {def $facetField=$search_extras.facet.main.field}
    {set $stop_word_array=$search['StopWordArray']}
    {set $search_data=$search}
{/if}
{def $baseURI=concat( '/content/search?SearchText=', $search_text )}
<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-search">

<form action={"/content/search/"|ezurl} method="get">

<div class="attribute-header">
    <h1 class="long">{"Search"|i18n("design/ezwebin/content/search")}</h1>
</div>

<p>
    <input class="halfbox" type="text" size="20" name="SearchText" id="Search" value="{$search_text|wash}" />
    <input class="button" name="SearchButton" type="submit" value="{'Search'|i18n('design/ezwebin/content/search')}" />
</p>

    {def $adv_url=concat('/content/advancedsearch/',$search_text|count_chars()|gt(0)|choose('',concat('?SearchText=',$search_text|urlencode)))|ezurl}
    <label>{"For more options try the %1Advanced search%2"|i18n("design/ezwebin/content/search","The parameters are link start and end tags.",array(concat("<a href=",$adv_url,">"),"</a>"))}</label>

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
  <h2>{'No results were found when searching for "%1".'|i18n("design/ezwebin/content/search",,array($search_text|wash))}</h2>
  {if $search_extras.hasError}
      {$search_extras.error|wash}
  {/if}
  </div>
    <p>{'Search tips'|i18n('design/ezwebin/content/search')}</p>
    <ul>
        <li>{'Check spelling of keywords.'|i18n('design/ezwebin/content/search')}</li>
        <li>{'Try changing some keywords (eg, "car" instead of "cars").'|i18n('design/ezwebin/content/search')}</li>
        <li>{'Try searching with less specific keywords.'|i18n('design/ezwebin/content/search')}</li>
        <li>{'Reduce number of keywords to get more results.'|i18n('design/ezwebin/content/search')}</li>
    </ul>
  {/case}
  {case}
  <div class="feedback">
  <h2>{'Search for "%1" returned %2 matches'|i18n("design/ezwebin/content/search",,array($search_text|wash,$search_count))}</h2>
      <fieldset>
          <legend>{'Facets'|i18n( 'design/ezwebin/content/search' )}</legend>
          <div class="block"><strong>{'Facets'|i18n( 'design/ezwebin/content/search' )}:</strong>&nbsp;
              {if $facetField|eq( 'class' )}
                  <span style="background-color: #F2F1ED">{'Class'|i18n( 'design/ezwebin/content/search' )}</span>
              {else}
                  <a href={concat( $baseURI, '&facet_field=class' )|ezurl}>{'Class'|i18n( 'design/ezwebin/content/search' )}</a>
              {/if}&nbsp;
              {if $facetField|eq( 'author' )}
                  <span style="background-color: #F2F1ED">{'Author'|i18n( 'design/ezwebin/content/search' )}</span>
              {else}
                  <a href={concat( $baseURI, '&facet_field=author' )|ezurl}>{'Author'|i18n( 'design/ezwebin/content/search' )}</a>
              {/if}&nbsp;
              {if $facetField|eq( 'translation' )}
                  <span style="background-color: #F2F1ED">{'Translation'|i18n( 'design/ezwebin/content/search' )}</span>
              {else}
                  <a href={concat( $baseURI, '&facet_field=translation' )|ezurl}>{'Translation'|i18n( 'design/ezwebin/content/search' )}</a>
              {/if}&nbsp;
          </div>
          <div class="block"><strong>{'Groups'|i18n( 'design/ezwebin/content/search' )}:</strong>&nbsp;
              <a href={concat( $baseURI, '&facet_field=', $facetField|wash )|ezurl}>{'All'|i18n( 'design/ezwebin/content/search' )}</a> -
              {foreach $search_extras.facet.main.nameList as $facetID => $name}
                  <a href={concat( $baseURI, '&facet_field=', $facetField|wash, '&filter[]=', $search_extras.facet.main.queryLimit[$facetID]|wash )|ezurl}>{$name|wash}</a>({$search_extras.facet.main.countList[$facetID]})
              {delimiter}-{/delimiter}
              {/foreach}
          </div>
      </fieldset>

  <p>{'Search time: %1 msecs'|i18n('ezfind',,array($search_extras.responseHeader.QTime|wash))}</p>

  </div>
  {/case}
{/switch}

{foreach $search_result as $result
         sequence array(bglight,bgdark) as $bgColor}
   {node_view_gui view=ezfind_line sequence=$bgColor use_url_translation=$use_url_translation content_node=$result}
{/foreach}

{include name=Navigator
         uri='design:navigator/google.tpl'
         page_uri='/content/search'
         page_uri_suffix=concat('?SearchText=',$search_text|urlencode,$search_timestamp|gt(0)|choose('',concat('&SearchTimestamp=',$search_timestamp)))
         item_count=$search_count
         view_parameters=$view_parameters
         item_limit=$page_limit}

</form>

</div>

<p class="small"><i>{$search_extras.engine}</i></p>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>

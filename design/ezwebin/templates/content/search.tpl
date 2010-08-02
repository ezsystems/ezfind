{def $search=false()}
{if $use_template_search}
    {set $page_limit=10}
    
    {def $activeFacetParameters = array()}
    {if ezhttp_hasvariable( 'activeFacets', 'get' )}
        {set $activeFacetParameters = ezhttp( 'activeFacets', 'get' )}
    {/if}
    
    {def $dateFilter=0}
    {if ezhttp_hasvariable( 'dateFilter', 'get' )}
        {set $dateFilter = ezhttp( 'dateFilter', 'get' )}
		{switch match=$dateFilter}
		   {case match=1}
		      {def $dateFilterLabel="Last day"|i18n("design/standard/content/search")}
		   {/case}
           {case match=2}
              {def $dateFilterLabel="Last week"|i18n("design/standard/content/search")}
           {/case}
           {case match=3}
              {def $dateFilterLabel="Last month"|i18n("design/standard/content/search")}
           {/case}      
           {case match=4}
              {def $dateFilterLabel="Last three months"|i18n("design/standard/content/search")}
           {/case}      
           {case match=5}
              {def $dateFilterLabel="Last year"|i18n("design/standard/content/search")}
           {/case}                 
		{/switch}        
    {/if}    
    
    {def $filterParameters = fetch( 'ezfind', 'filterParameters' )
         $defaultSearchFacets = fetch( 'ezfind', 'getDefaultSearchFacets' )}
    {* def $facetParameters=$defaultSearchFacets|array_merge_recursive( $activeFacetParameters ) *}
    
    {set $search=fetch( ezfind,search,
                        hash( 'query', $search_text,
                              'offset', $view_parameters.offset,
                              'limit', $page_limit,
                              'sort_by', hash( 'score', 'desc' ),
                              'facet', $defaultSearchFacets,
                              'filter', $filterParameters,
                              'publish_date', $dateFilter,
                              'spell_check', array( true() ) 
                             ))}
    {set $search_result=$search['SearchResult']}
    {set $search_count=$search['SearchCount']}
    {def $search_extras=$search['SearchExtras']}
    {set $stop_word_array=$search['StopWordArray']}
    {set $search_data=$search}
    {debug-log var=$search_extras.facet_fields msg='$search_extras.facet_fields'}
{/if}
{def $baseURI=concat( '/content/search?SearchText=', $search_text )}

{* Build the URI suffix, used throughout all URL generations in this page *}
{def $uriSuffix = ''}
{foreach $activeFacetParameters as $facetField => $facetValue}
    {set $uriSuffix = concat( $uriSuffix, '&activeFacets[', $facetField, ']=', $facetValue )}
{/foreach}

{foreach $filterParameters as $name => $value}
    {set $uriSuffix = concat( $uriSuffix, '&filter[]=', $name, ':', $value )}
{/foreach}

{if gt( $dateFilter, 0 )}
    {set $uriSuffix = concat( $uriSuffix, '&dateFilter=', $dateFilter )}
{/if}
    
<script language="JavaScript" type="text/javascript">
<!--{literal}
    // toggle block
    function ezfToggleBlock( id )
    {
        var value = (document.getElementById(id).style.display == 'none') ? 'block' : 'none';
		ezfSetBlock( id, value );
        ezfSetCookie( id, value );
    }

    function ezfSetBlock( id, value )
    {
        var el = document.getElementById(id);
        if ( el != null )
        {
		    el.style.display = value;
		}
    }

    function ezfTrim( str )
    {
        return str.replace(/^\s+|\s+$/g, '') ;
    }

    function ezfGetCookie( name )
    {
	var cookieName = 'eZFind_' + name;
	var cookie = document.cookie;

	var cookieList = cookie.split( ";" );

    for( var idx in cookieList )
    {
		cookie = cookieList[idx].split( "=" );

		if ( ezfTrim( cookie[0] ) == cookieName )
        {
			return( cookie[1] );
		}
	}

	return 'none';
    }

    function ezfSetCookie( name, value )
    {
	var cookieName = 'eZFind_' + name;
	var expires = new Date();

	expires.setTime( expires.getTime() + (365 * 24 * 60 * 60 * 1000));

	document.cookie = cookieName + "=" + value + "; expires=" + expires + ";";
    }
{/literal}--></script>

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-search">

<form action={"/content/search/"|ezurl} method="get">

<div class="attribute-header">
    <h1 class="long">{"Search"|i18n("design/ezwebin/content/search")}</h1>
</div>

<p>
<div id="ezautocomplete">
    <input class="halfbox" type="text" size="20" name="SearchText" id="Search" value="{$search_text|wash}" />
    <input class="button" name="SearchButton" type="submit" value="{'Search'|i18n('design/ezwebin/content/search')}" />
    <div id="ezautocompletecontainer"></div>
</div>
</p>
{if $search_extras.spellcheck_collation}
     {def $spell_url=concat('/content/search/',$search_text|count_chars()|gt(0)|choose('',concat('?SearchText=',$search_extras.spellcheck_collation|urlencode)))|ezurl}
     <p>{'Spell check suggestion: did you mean'|i18n('design/ezfind/search')} <b>{concat("<a href=",$spell_url,">")}{$search_extras.spellcheck_collation}</a></b> ?</p>
{/if}

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
  {*if $search_extras.spellcheck_collation}
     <b>Did you mean {$search_extras.spellcheck_collation} ?</b>
  {/if*}
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
          <legend onclick="ezfToggleBlock( 'ezfHelp' );">{'Help'|i18n( 'design/ezwebin/content/search' )} [+/-]</legend>
          <div id="ezfHelp" style="display: none;">
              <ul>
                  <li>{'The search is case insensitive. Upper and lower case characters may be used.'|i18n( 'design/ezfind/search' )}</li>
                  <li>{'The search result contains all search terms.'|i18n( 'design/ezfind/search' )}</li>
                  <li>{'Phrase search can be achieved by using quotes, example: "Quick brown fox jumps over the lazy dog"'|i18n( 'design/ezfind/search' )}</li>
                  <li>{'Words may be excluded by using a minus ( - ) character, example: lazy -dog'|i18n( 'design/ezfind/search' )}</li>
              </ul>
          </div>
      </fieldset>
  </div>
    
  <div {*class="feedback"*} id="search_controls">
      <fieldset>
          <legend>{'Refine your search'|i18n( 'design/ezwebin/content/search' )}</legend>
         
          {def $activeFacetsCount=0}
          <ul id="active-facets-list">
          {foreach $defaultSearchFacets as $key => $defaultFacet}
              {if array_keys( $activeFacetParameters )|contains( concat( $defaultFacet['field'], ':', $defaultFacet['name']  ) )}
                  {def $facetData=$search_extras.facet_fields.$key}
                  
                  {foreach $facetData.nameList as $key2 => $facetName}                  
                      {if eq( $activeFacetParameters[concat( $defaultFacet['field'], ':', $defaultFacet['name'] )], $facetName )}
                          {def $activeFacetsCount=sum( $key, 1 )}
                          {def $suffix=$uriSuffix|explode( concat( '&filter[]=', $facetData.queryLimit[$key2]|wash ) )|implode( '' )|explode( concat( '&activeFacets[', $defaultFacet['field'], ':', $defaultFacet['name'], ']=', $facetName ) )|implode( '' )}
			              <li>
			                  <a href={concat( $baseURI, $suffix )|ezurl}>[x]</a> <strong>{$defaultFacet['name']}</strong>: {$facetName}
			              </li>                        
                      {/if}
                  {/foreach}
              {/if}          
          {/foreach}
          
          {* handle date filter here, manually for now. Should be a facet later on *}
          {if gt( $dateFilter, 0 )}
              <li>
                 {set $activeFacetsCount=$activeFacetsCount|inc}
                 {def $suffix=$uriSuffix|explode( concat( '&dateFilter=', $dateFilter ) )|implode( '' )}
                  <a href={concat( $baseURI, $suffix )|ezurl}>[x]</a> <strong>{'Creation time'|i18n( 'extension/ezfind/facets' )}</strong>: {$dateFilterLabel}
              </li>          
          {/if}
          
          {if ge( $activeFacetsCount, 2 )}
              <li>
                  <a href={$baseURI|ezurl}>[x]</a> <em>{'Clear all'|i18n( 'extension/ezfind/facets' )}</em>
              </li>              
          {/if}
          </ul>
          
          <ul id="facet-list">
          {foreach $defaultSearchFacets as $key => $defaultFacet}
	          {if array_keys( $activeFacetParameters )|contains( concat( $defaultFacet['field'], ':', $defaultFacet['name']  ) )|not}
	          <li>
	            {def $facetData=$search_extras.facet_fields.$key}
	              <span {*style="background-color: #F2F1ED"*}><strong>{$defaultFacet['name']}</strong></span>
	              <ul>
	                {foreach $facetData.nameList as $key2 => $facetName}
		                {if ne( $key2, '' )}
		                <li>
		                    <a href={concat( $baseURI, '&filter[]=', $facetData.queryLimit[$key2]|wash, '&activeFacets[', $defaultFacet['field'], ':', $defaultFacet['name'], ']=', $facetName, $uriSuffix )|ezurl}>                
		                    {$facetName}</a> ({$facetData.countList[$key2]})
		                </li>
		                {/if}
	                {/foreach}
	              </ul>
	          </li>
	          {/if}
          {/foreach}
          
          {* date filtering here. Using a simple filter for now. Should use the date facets later on *}
          {if eq( $dateFilter, 0 )}
              <li>
                  <span {*style="background-color: #F2F1ED"*}><strong>{'Creation time'|i18n( 'extension/ezfind/facets' )}</strong></span>
                  <ul>
                    <li>
                        <a href={concat( $baseURI, '&dateFilter=1', $uriSuffix )|ezurl}>{"Last day"|i18n("design/standard/content/search")}</a>                    
                    </li>
                    <li>
                        <a href={concat( $baseURI, '&dateFilter=2', $uriSuffix )|ezurl}>{"Last week"|i18n("design/standard/content/search")}</a>                    
                    </li>
                    <li>
                        <a href={concat( $baseURI, '&dateFilter=3', $uriSuffix )|ezurl}>{"Last month"|i18n("design/standard/content/search")}</a>                    
                    </li>                    
                    <li>
                        <a href={concat( $baseURI, '&dateFilter=4', $uriSuffix )|ezurl}>{"Last three months"|i18n("design/standard/content/search")}</a>                    
                    </li>                    
                    <li>
                        <a href={concat( $baseURI, '&dateFilter=5', $uriSuffix )|ezurl}>{"Last year"|i18n("design/standard/content/search")}</a>                    
                    </li>                    
                  </ul>
              </li>          
           {/if}
          </ul>
          
      </fieldset>
  </div>
  {/case}
{/switch}

  <div id="search_results">
    {include name=Navigator
             uri='design:navigator/google.tpl'
             page_uri='/content/search'
             page_uri_suffix=concat('?SearchText=',$search_text|urlencode,$search_timestamp|gt(0)|choose('',concat('&SearchTimestamp=',$search_timestamp)), $uriSuffix )
             item_count=$search_count
             view_parameters=$view_parameters
             item_limit=$page_limit}
             
	{foreach $search_result as $result
	         sequence array(bglight,bgdark) as $bgColor}
	   {node_view_gui view=ezfind_line sequence=$bgColor use_url_translation=$use_url_translation content_node=$result}
	{/foreach}
	
	{include name=Navigator
	         uri='design:navigator/google.tpl'
	         page_uri='/content/search'
	         page_uri_suffix=concat('?SearchText=',$search_text|urlencode,$search_timestamp|gt(0)|choose('',concat('&SearchTimestamp=',$search_timestamp)), $uriSuffix )
	         item_count=$search_count
	         view_parameters=$view_parameters
	         item_limit=$page_limit}
  </div>
</form>

</div>

<p class="small"><em>{'Search took: %1 msecs, using '|i18n('ezfind',,array($search_extras.responseHeader.QTime|wash))}{$search_extras.engine}</em></p>
{*$search|attribute(show,2)*}
</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>


<script language="JavaScript" type="text/javascript">
jQuery('#ezautocompletecontainer').css('width', jQuery('input#Search').width());
var ezAutoHeader = eZAJAXAutoComplete();
ezAutoHeader.init({ldelim}

    url: "{'ezjscore/call/ezfind::autocomplete'|ezurl('no')}",
    inputid: 'Search',
    containerid: 'ezautocompletecontainer',
    minquerylength: {ezini( 'AutoCompleteSettings', 'MinQueryLength', 'ezfind.ini' )},
    resultlimit: {ezini( 'AutoCompleteSettings', 'Limit', 'ezfind.ini' )}

{rdelim});

<!--{literal}
ezfSetBlock( 'ezfFacets', ezfGetCookie( 'ezfFacets' ) );
ezfSetBlock( 'ezfHelp', ezfGetCookie( 'ezfHelp' ) );
{/literal}--></script>
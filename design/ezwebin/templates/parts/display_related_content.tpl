<div class="attribute-relatedcontent">
    <h1>{"Related content"|i18n("design/ezwebin/full/article")}</h1>
    <ul>
    {foreach $related_content as $related_object max 5}
        <li><a href="{$related_object.url_alias|ezurl( 'no' )}" title="{$related_object.name|wash()}">{$related_object.name|wash()}</a></li>
    {/foreach}
    </ul>
</div>
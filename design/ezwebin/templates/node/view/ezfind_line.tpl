<div class="content-view-line">
    <div class="class-article float-break">

        <div class="attribute-title">
            <h2 style="margin-top: 0.5em; margin-bottom: 0.25em"><a href="{$node.global_url_alias}">{$node.name|wash}</a></h2>
        </div>

        {if is_set( $node.data_map.image )}
            {if $node.data_map.image.has_content}
                <div class="attribute-image">
                    {attribute_view_gui image_class=small href=concat( '"', $node.global_url_alias, '"' ) attribute=$node.data_map.image}
                </div>
            {/if}
        {/if}

        <div class="attribute-short">
            {$node.highlight}
        </div>

        <div class="attribute-short">
            <i>{$node.score_percent|wash}% - <a href="{$node.global_url_alias}">{$node.global_url_alias|shorten(70, '...', 'middle')|wash}</a> - {$node.object.published|l10n(shortdatetime)}</i>
        </div>
    </div>
</div>

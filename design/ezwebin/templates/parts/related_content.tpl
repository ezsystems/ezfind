{def $related_content=fetch( 'ezfind', 'moreLikeThis', hash( 'query_type', 'nid',
                                                             'query', $node.node_id,
                                                             'limit', 5
                           ))}
{set $related_content=$related_content['SearchResult']}
{include uri="design:parts/display_related_content.tpl"}
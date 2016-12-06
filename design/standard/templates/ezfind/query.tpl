{*
    INPUT

        data : form data
        result : solr search result
*}
{def
    $solr_fields_meta = array(
        'meta_guid_ms',
        'meta_installation_id_ms',
        'meta_name_t',
        'meta_owner_name_ms',
        'meta_id_si',
        'meta_current_version_si',
        'meta_remote_id_ms',
        'meta_class_identifier_ms',
        'meta_main_node_id_si',
        'meta_modified_dt',
        'meta_published_dt',
        'meta_main_url_alias_ms',
        'meta_main_path_string_ms',
        'meta_object_states_si',
    )
    $solr_fields_attr = array( 'attr_publish_date_dt' )
    $solr_fields_other = array( 'timestamp', 'ezf_sp_words', 'ezf_df_text' )
    $formats = array( 'php', 'json', 'csv' )
}

<div>
    <form method="post" action={'ezfind/query'|ezurl()}>
    
        <label>Query:(<a target="_blank" href="http://wiki.apache.org/solr/QueryParametersIndex">?</a>)</label>
        
        <table>
            <tr>
                <td>q</td>
                <td>
                    <textarea name="data[query]" type="text" style="width: 600px; height: 100px;">{$data[ 'query' ]}</textarea>
                </td>
            </tr>
            <tr>
                <td>format</td>
                <td>
                    <select name="data[format]">
                    {foreach $formats as $format}
                        <option {if eq( $format, $data.format )}selected="selected"{/if}>{$format}</option>
                    {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td>offset</td>
                <td><input type="text" name="data[offset]" value="{$data.offset}" /></td>
            </tr>
            <tr>
                <td>limit</td>
                <td><input type="text" name="data[limit]" value="{$data.limit}" /></td>
            </tr>
            <tr>
                <td>sort</td>
                <td><input type="text" name="data[sort]" value="{$data.sort}" /></td>
            </tr>
            <tr>
                <td>Fields</td>
                <td>
                    <select size="12" name="data[fields][]" multiple="multiple">
                        <optgroup label="Meta">
                            {foreach $solr_fields_meta as $solr_field}
                                <option value="{$solr_field}" {if $data.fields|contains( $solr_field )}selected="selected"{/if}>
                                    {$solr_field}
                                </option>
                            {/foreach}
                        </optgroup>
                        <optgroup label="Attribute">
                            {foreach $solr_fields_attr as $solr_field}
                                <option value="{$solr_field}" {if $data.fields|contains( $solr_field )}selected="selected"{/if}>
                                    {$solr_field}
                                </option>
                            {/foreach}
                        </optgroup>
                        <optgroup label="Others">
                            {foreach $solr_fields_other as $solr_field}
                                <option value="{$solr_field}" {if $data.fields|contains( $solr_field )}selected="selected"{/if}>
                                    {$solr_field}
                                </option>
                            {/foreach}
                        </optgroup>
                    </select>
                </td>
            </tr>
        </table>
        
        <input type="submit" value="submit" name="submit" />
    </form>
</div>

{if $result}
    <div>
<pre style="max-width: 1200px; overflow-x: scroll">
{$result}
</pre>
    </div>
{/if}

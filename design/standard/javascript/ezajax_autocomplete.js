YUI.add('ezfindautocomplete', function (Y) {
    "use strict";

    Y.namespace('eZ');

    /**
     * Inits a YUI3 autocomplete doing ezfind lookups
     *
     * @param conf : array
     *  - url: url of the ajax server
     *  - inputSelector: where on the page the autocomplete must be enable, example: #myform
     *  - minQueryLength: number of char to be typed before the lookup
     *  - resultLimit: number of results fetched
     *
     */
    function initAutoComplete(conf) {
        // Create a DataSource instance.
        var ds = new Y.DataSource.IO({
            source: conf.url
        });

        Y.one(conf.inputSelector).plug(Y.Plugin.AutoComplete, {
            maxResults: conf.resultLimit,
            minQueryLength: conf.minQueryLength,
            resultHighlighter: 'phraseMatch',
            // What is displayed in the list
            resultTextLocator:  function (result) {
                return result[0];
            },
            source: ds,
            // This will be appended to the URL that was supplied to the DataSource's "source" config above.
            requestTemplate: "::{query}::" + conf.resultLimit + "?ContentType=json",
            // Custom result list locator to parse the results out of the response.
            resultListLocator: function (response) {
                if (response && response[0] && response[0].responseText){
                    return Y.JSON.parse(response[0].responseText).content;
                } else {
                    return [];
                }
            }
        });
    }

    Y.eZ.initAutoComplete = initAutoComplete;

}, '1.0.0', {
    requires: [
        'autocomplete', 'autocomplete-highlighters', 'datasource-io', 'json-parse'
    ]
});

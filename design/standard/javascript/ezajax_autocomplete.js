/**
 * Using YUI AutocomComplete to provide suggested terms from ezFind
 */
var eZAJAXAutoComplete = function() {

    var _cfg = {}, YAHOO;

    /**
     * Initializes the widget
     * 
     * @private
     */
    var initAutosuggest = function() {
        var dsJSON = new YAHOO.util.DataSource(_cfg.url);
        dsJSON.responseType = YAHOO.util.DataSource.TYPE_JSON;
        dsJSON.connXhrMode = "cancelStaleRequests";
        dsJSON.responseSchema = {
                resultsList: "content",
                fields: ["facet", "count"],
                metaFields: { errorMessage: "error_text" }
        };

        var autoComplete = new YAHOO.widget.AutoComplete(_cfg.inputid, _cfg.containerid, dsJSON);
        autoComplete.useShadow = true;
        autoComplete.minQueryLength = _cfg.minquerylength;
        autoComplete.allowBrowserAutocomplete = false;
        autoComplete.generateRequest = function(q) {
            return "::" + q + "::" + _cfg.resultlimit + "?ContentType=json";
        };
    }

    return {
        /**
         * The initialization of the module
         * Using YUI3 loader to avoid race conditions
         * 
         * @param {Array}
         *            url, 
         *            inputid, 
         *            containerid, 
         *            minQueryLength,
         *            resultlimit
         */
        init : function(configuration) {
            _cfg = configuration;
            YUI(YUI3_config).use('yui2-connection', 'yui2-autocomplete', function (Y) {
                YAHOO = Y.YUI2;
                initAutosuggest();
            });
        }
    }
};
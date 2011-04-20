/**
 * Using YUI AutocomComplete to provide suggested terms from ezFind
 */
var eZAJAXAutoComplete = function() {

    var _cfg = {};

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
            var loader = new YAHOO.util.YUILoader(YUI2_config);
            loader.require(['connection', 'autocomplete']);
            loader.onSuccess = function() {
                initAutosuggest();
            };
            loader.insert({}, 'js' );
        }
    }
};

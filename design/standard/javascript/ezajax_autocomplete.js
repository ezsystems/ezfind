/**
 * Using YUI AutocomComplete to provide suggested terms from ezFind
 */
var eZAJAXAutoComplete = function() {

    var ret = {};
    ret.cfg = {};

    /**
     * Initializes the widget
     * 
     * @private
     */
    var initAutosuggest = function() {
        var dsJSON = new YAHOO.util.DataSource(ret.cfg.url);
        dsJSON.responseType = YAHOO.util.DataSource.TYPE_JSON;
        dsJSON.connXhrMode = "cancelStaleRequests";
        dsJSON.responseSchema = {
                resultsList: "content",
                fields: ["facet", "count"],
                metaFields: { errorMessage: "error_text" }
        };

        var autoComplete = new YAHOO.widget.AutoComplete(ret.cfg.inputid, ret.cfg.containerid, dsJSON);
        autoComplete.useShadow = true;
        autoComplete.minQueryLength = ret.cfg.minquerylength;
        autoComplete.generateRequest = function(q) {
            return "::" + q + "::" + ret.cfg.resultlimit + "?ContentType=json";
        };
       
    }

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
    ret.init = function() {
        YUILoader.require([ 'autocomplete' ]);
        YUILoader.onSuccess = function() {
            initAutosuggest();
        };
        var options = [];
        YUILoader.insert(options, 'js');
    }

    return ret;
}();
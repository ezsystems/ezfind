/**
 * Using YUI AutocomComplete to provide suggested terms from ezFind
 */
var ezajaxautosuggest = function() {
    
    /**
     * Default configuration
     * @private
     */
    var _cfg = {
            url: 'http://localhost/siteaccess/ezjscore/call/ezfind::autocomplete',  // The rest URL
            inputid: 'searchtext',                                                  // ID of the input field for queries
            containerid: 'ezautocompletecontainer',                                 // ID of the container for query results
            minquerylength: 2,                                                      // Characters before the rest call
            resultlimit: 5
    };

    /**
     * Replaces default configuration
     * @private
     */
    var initConfig = function(config) {
        if(config.url)              _cfg.url = config.url;
        if(config.inputid)          _cfg.inputid = config.inputid;
        if(config.containerid)      _cfg.containerid = config.containerid;
        if(config.minquerylength)   _cfg.minquerylength = config.minquerylength;
        if(config.resultlimit)      _cfg.resultlimit = config.resultlimit;
    }; 

    /**
     * Initializes the widget
     * @private
     */
    var initAutosuggest = function() {
        var dsJSON = new YAHOO.util.DataSource(_cfg.url);
        dsJSON.responseType = YAHOO.util.DataSource.TYPE_JSON;
        dsJSON.connXhrMode = "cancelStaleRequests";
        dsJSON.responseSchema = {
                resultsList: "content",
                fields: ["t", "i"],
                metaFields: { errorMessage: "error_text" }
        };

        var autoComplete = new YAHOO.widget.AutoComplete(_cfg.inputid, _cfg.containerid, dsJSON);
        autoComplete.useShadow = true;
        autoComplete.minQueryLength = _cfg.minquerylength;
        autoComplete.generateRequest = function(q) {
            return "::" + q + "::" + _cfg.resultlimit + "?ContentType=json";
        };
       
    }

    return {

        /**
         * The initialization of the module
         * @param {Array} url, inputid, containerid, minQueryLength,  
         */
        init: function(configuration) {
            YUILoader.require([ 'autocomplete' ]);
            YUILoader.onSuccess = function() {
                initConfig(configuration);
                initAutosuggest();
            };
            var options = [];
            YUILoader.insert(options, 'js');
        }

    }

}();
/**
 * Using YUI AutoComplete to provide suggested terms from eZ Find
 */

/**
 * Constructor for the AJAX based autocomplete component
 *
 * @param conf Component configuration object
 * @param Y YUI global object
 */
function eZAJAXAutoComplete( conf, Y ) {
    this.conf = conf;
    this.Y = Y;

    this.init();
}

/**
 * Initializes YUI2 DataSource and AutoComplete components
 */
eZAJAXAutoComplete.prototype.init = function() {
        var YAHOO = this.Y.YUI2;
        var that = this;

        var datasource = new YAHOO.util.DataSource( this.conf.url, { responseType: YAHOO.util.DataSource.TYPE_JSON,
                                                                     connXhrMode: "cancelStaleRequests",
                                                                     responseSchema: { resultsList: "content",
                                                                                       fields: ["facet", "count"],
                                                                                       metaFields: { errorMessage: "error_text" } } } );

        var autocomplete = new YAHOO.widget.AutoComplete( this.conf.inputid, this.conf.containerid, datasource );
        autocomplete.useShadow = true;
        autocomplete.minQueryLength = this.conf.minquerylength;
        autocomplete.allowBrowserAutocomplete = false;
        autocomplete.generateRequest = function(q) {
            return "::" + q + "::" + that.conf.resultlimit + "?ContentType=json";
        };
}

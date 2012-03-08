/**
 * Using YUI AutoComplete to provide suggested terms from eZ Find
 */

/**
 * Constructor for the AJAX based autocomplete component
 *
 * @param conf Component configuration object
 */
function eZAJAXAutoComplete( conf ) {
    this.conf = conf;

    var that = this,
        loader = new YAHOO.util.YUILoader( YUI2_config );

    loader.require( ['connection', 'autocomplete'] );
    loader.onSuccess = function() {
        that.init();
    };
    loader.insert( {}, 'js' );
}

/**
 * Initializes YUI2 DataSource and AutoComplete components
 */
eZAJAXAutoComplete.prototype.init = function() {
    var that = this;

    var datasource = new YAHOO.util.DataSource( this.conf.url, { responseType: YAHOO.util.DataSource.TYPE_JSON,
                                                                 connXhrMode: "cancelStaleRequests",
                                                                 responseSchema: { resultsList: "content",
                                                                                   fields: ["facet", "count"],
                                                                                   metaFields: { errorMessage: "error_text" } } } );

    YAHOO.util.Event.onAvailable( this.conf.containerid, function( e ) {
        var autocomplete = new YAHOO.widget.AutoComplete( that.conf.inputid, that.conf.containerid, datasource );
        autocomplete.useShadow = true;
        autocomplete.minQueryLength = that.conf.minquerylength;
        autocomplete.allowBrowserAutocomplete = false;
        autocomplete.generateRequest = function(q) {
            return "::" + q + "::" + that.conf.resultlimit + "?ContentType=json";
        };
    }, this );
}

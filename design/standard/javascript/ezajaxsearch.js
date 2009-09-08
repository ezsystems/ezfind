var eZAJAXSearch = function() {
    var ret = {};
    
    var yCallback = function(Y, result) {
        var successCallBack = function(id, o) {
            if (o.responseJSON !== undefined) {
                var response = o.responseJSON;

                if (response.content.SearchResult !== undefined) {
                    var itemCount = response.content.SearchResult.length;

                    var resultsTarget = Y.get(ret.cfg.searchresults);
                    resultsTarget.set('innerHTML', '');
                    resultsTarget.addClass('loading');

                    var spellCheck = response.content.SearchExtras.spellcheck;
                    // A spellcheck proposal was made, display it : 
                    if ( spellCheck.collation )
                    {
                        var scTemplate = ret.cfg.spellchecktemplate;
                        scTemplate = scTemplate.replace( /\{+spellcheck+\}/, spellCheck.collation );
                        var scDiv = Y.Node.create( scTemplate );
                        scDiv.on( 'click', handleClickFromSpellcheck );
                        resultsTarget.appendChild( scDiv );
                    }
                    
                    for(var i = 0; i < itemCount; i++) {
                        var item = response.content.SearchResult[i];
                        
                        var template = ret.cfg.resulttemplate;
                        template = template.replace(/\{+title+\}/, item.name);
                        
                        var date = new Date( item.published * 1000 );
                        var dateString = date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds() + ' ' + date.getFullYear() + '/' + date.getMonth() + '/' + date.getDay();
                        template = template.replace(/\{+date+\}/, dateString);
                        template = template.replace(/\{+class_name+\}/, item.class_name);
                        template = template.replace(/\{+url_alias+\}/, item.url_alias);
                        template = template.replace(/\{+object_id+\}/, item.id);

                        var itemContainer = Y.Node.create(template);

                        resultsTarget.removeClass('loading');
                        resultsTarget.appendChild(itemContainer);
                    }
                }
            }
        }

        var performSearch = function() {
            var searchString = Y.get(ret.cfg.searchstring).get('value');

            var data = 'SearchStr=' + searchString;
            data += '&SearchLimit=10';
            data += '&SearchOffset=0';

            for ( var i = 0; i < ret.cfg.customSearchAttributes.length; i++ )
            {
                data += '&' + Y.get( ret.cfg.customSearchAttributes[i] ).get('name') + '=' + Y.get( ret.cfg.customSearchAttributes[i] ).get('value'); 
            }
            
            var backendUri = ret.cfg.backendUri ? ret.cfg.backendUri : 'ezflow::search' ;
            
            if(searchString !== '') {
                Y.io.ez(backendUri, {on: {success: successCallBack}, method: 'POST', data: data });
            }
        }

        var handleClick = function(e) {
            performSearch();

            e.preventDefault();
        }

        var handleClickFromSpellcheck = function(e) {
            Y.get(ret.cfg.searchstring).set( 'value', Y.get(ret.cfg.spellcheck).get('innerHTML') );
            handleClick( e );
        }
        var handleKeyPress = function(e) {
            var key = e.which || e.keyCode;
            if (key == 13) {
                performSearch();
                e.halt();
            }
        }

        Y.get(ret.cfg.searchbutton).on('click', handleClick);
        Y.get(ret.cfg.searchstring).on('keypress', handleKeyPress);
    }
    ret.cfg = {};

    ret.init = function() {
        var ins = YUI(YUI3_config).use('node', 'event', 'io-ez', yCallback);
    }
    
    return ret;
}();
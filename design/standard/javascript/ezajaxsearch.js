var eZAJAXSearch = function()
{
    var ret = {};

    var yCallback = function(Y, result)
    {
        var successCallBack = function(id, o)
        {
            if (o.responseJSON !== undefined)
            {
                var response = o.responseJSON;

                if (response.content.SearchResult !== undefined)
                {
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

                    var facets = response.content.SearchExtras.facets;
                    // Facets were returned, display them : 
                    if ( facets && facets.length != 0 )
                    {
                        var facetMainList = ret.cfg.facetsmainlisttemplate;                        
                        for( var i = 0; i < facets.length; i++ )
                        {
                            var facet = facets[i];
                            // Name of the facet :
                            var facetName = facet[0];
                            var facetInnerList = ret.cfg.facetsinnerlisttemplate;                            
                            facetInnerList = facetInnerList.replace( /\{+facet_name+\}/, facetName );                                                        
                            
                            if ( facet.length > 1 )
                            {
                                for( var j = 1; j < facet.length; j++ )
                                {
                                    var link = facet[j][0];
                                    var value = facet[j][1];
                                    var count = facet[j][2];
                                    var facetElement = ret.cfg.facetselementtemplate;
                                    facetElement = facetElement.replace( /\{+link+\}/, link );
                                    facetElement = facetElement.replace( /\{+value+\}/, value );
                                    facetElement = facetElement.replace( /\{+count+\}/, count );
                                    facetInnerList = facetInnerList.replace( /\{+facet_element+\}/, facetElement + "{facet_element}" );                                
                                }
                                facetInnerList = facetInnerList.replace( /\{+facet_element+\}/, '' );
                                facetMainList = facetMainList.replace( /\{+inner_facet_list+\}/, facetInnerList + "{inner_facet_list}" );
                            }
                        }

                        // Only display the "Refine with facets" block if actual facets were returned.
                        if ( facetMainList != ret.cfg.facetsmainlisttemplate )
                        {
                            facetMainList = facetMainList.replace( /\{+inner_facet_list+\}/, "" );                            
                            var facetsDiv = Y.Node.create( facetMainList );
                            
                            resultsTarget.appendChild( facetsDiv );                            
                            var id = facetsDiv.get( 'id' );
                            //var width = facetsDiv.get( 'clientWidth' ) - 10; // removing horizontal padding in order to have the actual element's width
                            //var height = facetsDiv.get( 'clientHeight' ) - 10; // idem.
                            
                            var myAnim = function ( Y )
                            {
                                var anim = new Y.Anim({
                                    node: '#' + id,
                                    easing: Y.Easing.backIn,
                                    duration: 1.0,
                                    
                                    from: 
                                    {
                                        /*height: 0,
                                        width: 0*/
                                        opacity: 0
                                    },
    
                                    to: 
                                    {
                                        /*width: width,
                                        height: height*/
                                        opacity: 1
                                    }
                                });
                                anim.run();
                            }
                            YUI().use( 'animation', 'anim', myAnim );
                            
                        }
                    }
                    
                    
                    for(var i = 0; i < itemCount; i++) 
                    {
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

        var performSearch = function()
        {
            var searchInput = Y.get(ret.cfg.searchstring);
            var searchString = searchInput.get('value');

            var data = 'SearchStr=' + searchString;
            data += '&SearchLimit=10';
            data += '&SearchOffset=0';

            for ( var i = 0; i < ret.cfg.customSearchAttributes.length; i++ )
            {
                data += '&' + Y.get( ret.cfg.customSearchAttributes[i] ).get('name') + '=' + Y.get( ret.cfg.customSearchAttributes[i] ).get('value'); 
            }
            
            var backendUri = ret.cfg.backendUri ? ret.cfg.backendUri : 'ezflow::search' ;
            
            if(searchString !== '')
            {
                Y.io.ez(backendUri, {on: {success: successCallBack}, method: 'POST', data: data });
            }
        }

        var handleClick = function(e)
        {
            performSearch();
            e.preventDefault();
        }

        var handleClickFromSpellcheck = function(e)
        {
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
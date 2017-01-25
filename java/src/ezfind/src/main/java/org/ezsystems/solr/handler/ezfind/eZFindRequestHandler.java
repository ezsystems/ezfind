package org.ezsystems.solr.handler.ezfind;

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
import org.apache.solr.util.plugin.*;
import org.apache.solr.handler.*;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrRequestHandler;
import org.apache.solr.response.SolrQueryResponse;
import org.apache.solr.core.SolrCore;

import org.apache.solr.common.SolrException;
import org.apache.solr.common.SolrException.ErrorCode;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.core.Config;
import org.apache.solr.core.SolrCore;
import org.apache.solr.core.SolrDeletionPolicy;
import org.apache.solr.handler.component.SearchComponent;
import org.apache.solr.handler.component.QueryElevationComponent;

import org.apache.solr.common.util.SimpleOrderedMap;

import java.io.File;
import java.io.FileWriter;
import java.io.BufferedWriter;
import java.util.Iterator;
import java.util.ArrayList;
import java.util.Collection;
import java.util.HashSet;
import java.util.LinkedList;
import java.util.Map;
import java.util.HashMap;
import java.util.logging.Logger;
import java.util.logging.Level;
import java.net.URL;

import java.util.List;

/**
 * Multi purpose handler, extending Solr's features for <a
 * href="http://ez.no/ezfind/">eZ Find</a>, technological bridge between the
 * Enterprise Open Source CMS <a href="http://ez.no/ezpublish/">eZ Publish</a>
 * and <a href="http://lucene.apache.org/solr/">Solr</a>
 * <p/>
 */
public class eZFindRequestHandler extends RequestHandlerBase implements SolrCoreAware {

    volatile long numRequests;
    volatile long totalTime;
    volatile long numErrors;

    /**
     * Storing the current core.
     */
    private SolrCore core = null;

    /**
     * Storing the core's first elevation Component encountered. Will be used to
     * update the configuration dynamically.
     */
    private QueryElevationComponent elevationComponent = null;

    /**
     * Used to dynamically update the configuration file, usually named
     * "elevate.xml". Is populated once at initialization.
     *
     * @see init
     */
    private String elevateConfigurationFileName = null;

    private transient static Logger log = Logger.getLogger(eZFindRequestHandler.class + "");

    /**
     * Constant storing the name of the POST/GET variable ( request parameter )
     * containing the update configuration XML for the QueryElevation component.
     */
    public static final String CONF_PARAM_NAME = "elevate-configuration";

    /**
     * <code>init</code> will be called just once, immediately after creation.
     * <p>
     * The args are user-level initialization parameters that may be specified
     * when declaring a request handler in solrconfig.xml
     */
    @Override
    public void init(NamedList params) {

        super.init(params);
    }

    /**
     * Returns the name of the QueryElevation component's configuration file.
     *
     * Is assumed here that the QueryElevation component's configuration is
     * correct ( hence the absence of sanity checks ). It would have triggered
     * exceptions at startup otherwise.
     */
    private String getElevateConfigurationFileName() {
        if (this.elevateConfigurationFileName == null) {
		  // Issue accessing the initArgs property of a QueryElevationComponent object, it is private. Need to directly access the config.
            //  String f = this.elevationComponent.initArgs.get( QueryElevationComponent.CONFIG_FILE );

		  // FIXME: the XML attribute name ( "config-file" ) is only visible from the package in QueryElevationComponent,
            //         hence the impossibility to use QueryElevationComponent.CONFIG_FILE ( which would be way cleaner ). This issue appears again a few lines below.
            this.elevateConfigurationFileName = this.core.getSolrConfig().get("searchComponent[@class=\"solr.QueryElevationComponent\"]/str[@name=\"" + "config-file" + "\"]", "elevate.xml");
        }
        return this.elevateConfigurationFileName;
    }

    /**
     * Handles a query request, this method must be thread safe.
     * <p>
     * Information about the request may be obtained from <code>req</code> and
     * response information may be set using <code>rsp</code>.
     * <p>
     * There are no mandatory actions that handleRequest must perform. An empty
     * handleRequest implementation would fulfill all interface obligations.
     */
    @Override
    public void handleRequestBody(SolrQueryRequest req, SolrQueryResponse rsp) {
        numRequests++;
        long startTime = System.currentTimeMillis();

        String newElevateConfiguration = req.getParams().get(eZFindRequestHandler.CONF_PARAM_NAME);

        if (newElevateConfiguration != null) {
            String f = this.getElevateConfigurationFileName();

            File fC = new File(this.core.getResourceLoader().getConfigDir(), f);

            if (fC.exists()) {
                try {
                    FileWriter fw = new FileWriter(fC);
                    BufferedWriter out = new BufferedWriter(fw);
                    out.write(newElevateConfiguration);
                    out.close();
                    // reinitialize the QueryElevation component. Is there another way to take the new configuration into account ?
                    this.elevationComponent.inform(this.core);
                } catch (Exception e) {
                    numErrors++;
                    this.log.log(Level.SEVERE, e.getMessage());
                } finally {
                    totalTime += System.currentTimeMillis() - startTime;
                }
            }

            /**
             * Although the QueryElevationComponent supports having elevate.xml
             * both in the dataDir and in the conf dir, this requestHandler will
             * not support having elevate.xml in the dataDir. In fact, the
             * replication feature, being on his way at the moment is not able
             * to replicate configuration files placed in the dataDir.
             */

            /*
             else if( fD.exists() )
             {
             // Update fD.
             try
             {
             this.log.info( "Updating " + fD );
             FileWriter fw = new FileWriter( fD );
             BufferedWriter out = new BufferedWriter( fw );
             out.write( newElevateConfiguration );
             out.close();
             // reinitialize the QueryElevation component. Is there another way to take the new configuration into account ?
             this.elevationComponent.inform( this.core );
             }
             catch (Exception e) {
             this.log.info( "Exception when updating " + fD.getAbsolutePath() + " : " + e.getMessage());
             rsp.add( "error", "Error when updating " + fD.getAbsolutePath() + " : " + e.getMessage() );
             }
             }
             */
        }
    }

    //  SolrCoreAware interface implementation - Start
    public void inform(SolrCore core) {
        this.core = core;

        Map<String, SearchComponent> availableSearchComponents = core.getSearchComponents();

        for (Iterator i = availableSearchComponents.entrySet().iterator(); i.hasNext();) {
            Map.Entry e = (Map.Entry) i.next();
            // Ugly hard-coded fully-qualified class name. Any workaround ?
            if (e.getValue().getClass().getName() == "org.apache.solr.handler.component.QueryElevationComponent") {
                // Found the Query Elevation Component, store it as local property.
                this.elevationComponent = (QueryElevationComponent) e.getValue();
                break;
            }
        }
    }
  //  SolrCoreAware interface implementation - End

    // ////////////////////// SolrInfoMBeans methods //////////////////////
    @Override
    public String getDescription() {
        return "eZFind's elevate helper request Handler.";
    }

    @Override
    public String getVersion() {
        return "5.3";
    }

    /**
     * CVS Source, SVN Source, etc
     */
    @Override
    public String getSource() {
        return "http://ez.no";
    }

    /**
     * Simple common usage name, e.g. BasicQueryHandler, or fully qualified clas
     * name.
     */
    @Override
    public String getName() {
        return "eZFindQueryHandler";
    }

    /**
     * Documentation URL list.
     *
     * <p>
     * Suggested documentation URLs: Homepage for sponsoring project, FAQ on
     * class usage, Design doc for class, Wiki, bug reporting URL, etc...
     * </p>
     */
    @Override
    public URL[] getDocs() {
        return null;
    }

    /**
     * Any statistics this instance would like to be publicly available via the
     * Solr Administration interface.
     *
     * <p>
     * Any Object type may be stored in the list, but only the
     * <code>toString()</code> representation will be used.
     * </p>
     */
    @Override
    public NamedList<Object> getStatistics() {
        NamedList all = new SimpleOrderedMap<Object>();
        all.add("requests", "" + numRequests);
        all.add("errors", "" + numErrors);
        if (numRequests > 0) {
            all.add("averageTimePerReq(ms)", "" + (totalTime / numRequests));
        }
        all.add("totalTime(ms)", "" + totalTime);
        return all;
    }
}

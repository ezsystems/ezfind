<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//


/**
 * eZSolrBase handles communication with the solr server.
 * 
 * It is recommended to have the php_curl extension enabled, as it will perform
 * much better, but it is also able to work without it
 */
class eZSolrMultiCoreBase extends eZSolrBase
{
    /**
     * URL information of the solr server
     * Associative array with 2 indexes: uri & protocol
     * @var array
     * @since eZ Find 2.2
     */
    var $searchServer;
    
    /**
     * Languages / cores mapping
     * Based on ezfind.ini
     * @var array
     * @since eZ Find 2.2
     */
    protected $languagesCoresMapping = array();
    
    /**
     * Default solr core
     * @var string
     * @since eZ Find 2.2
     */
    protected $defaultCore = false;
    
    /**
     * @var bool
     * @since eZ Find 2.2
     */
    protected $enableMultiCore = null;
     
    /**
     * solr.ini eZINI instance
     * @var eZINI
     */
    var $SolrINI;
    
    /**
     * ezfind.ini eZINI instance
     * @var eZINI
     * @since eZ Find 2.2
     */
    var $eZFindINI;

    /**
     * Constructor. Initializes $SearchServerURI
     * @param string $baseURI An optional solr URI that overrides the INI one.
     */
    function __construct( $baseURI = false )
    {
        $this->SolrINI = eZINI::instance( 'solr.ini' );
        $this->eZFindINI = eZINI::instance( 'ezfind.ini' );

        $this->enableMultiCore = ( $this->eZFindINI->variable( 'LanguageSearch', 'MultiCore' ) == 'enabled' );
        
        if ( $this->enableMultiCore )
        {
            $this->defaultCore = $this->eZFindINI->variable( 'LanguageSearch', 'DefaultCore' );
            $this->languagesCoresMapping = $this->eZFindINI->variable( 'LanguageSearch', 'LanguagesCoresMap' );
        }
        
        if ( $baseURI !== false )
        {
            $parts = explode( '://', $baseURI );
            $this->solrURI = array(
                'protocol' => $parts[0],
                'uri' => $parts[1]
            );
        }
        else
        {
            $this->solrURI = array(
                'uri' => $this->SolrINI->variable( 'SolrBase', 'SearchServerURI' ),
                'protocol' => $this->SolrINI->variable( 'SolrBase', 'SearchServerProtocol' )
            );
        }
        
        // fall back to hardcoded Solr default
        if ( !$this->solrURI )
        {
            $this->solrURI = array(
                'uri' => 'localhost:8983/solr',
                'protocol'  => 'http'
            );
        }

    }

    /**
     * Build a HTTP GET query
     *
     * @param string $request Solr request type name.
     * @param array $queryParams Query parameters, as an associative array
     *
     * @return string The HTTP GET URL
     */
    function buildHTTPGetQuery( $request, $queryParams )
    {
        foreach ( $queryParams as $name => $value )
        {
            if ( is_array( $value ) )
            {
                foreach ( $value as $valueKey => $valuePart )
                {
                    $encodedQueryParams[] = urlencode( $name ) . '=' . urlencode( $valuePart );
                }
            }
            else
            {
                $encodedQueryParams[] = urlencode( $name ) . '=' . urlencode( $value );
            }
        }

        // @todo Check where languages are coming from
        return $this->solrURL( $request ) . '?' . implode( '&', $encodedQueryParams );
    }

    /**
     * Send HTTP Post query to the Solr engine
     *
     * @param string $request request name (examples: /select, /update, ...)
     * @param string $postData post data
     * @param string $languageCodes A language code string
     * @param string $contentType POST content type
     *
     * @return string Result of HTTP Request ( without HTTP headers )
     */
    protected function postQuery( $request, $postData, $languageCodes = array(), $contentType = 'application/x-www-form-urlencoded' )
    {
        try
        {
            $url = $this->solrURL( $request, $languageCodes );
        }
        catch ( Exception $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ . ': An error occured getting the solr request URL' );
            return false;
        }
        
        eZDebug::writeDebug( compact( 'url', 'postData', 'contentType' ), __METHOD__ );
        
        return $this->sendHTTPRequest( $url, $postData, $contentType );
    }

    /**
     * Sends a ping request to solr
     * 
     * @param string $wt
     *        Query response writer. Only PHP is supported for now.
     *        Note that this parameter isn't used at all for now.
     * 
     * @return array The ping operation result
     * 
     * @note OBS ! Experimental.
     */
    function ping ( $wt = 'php' )
    {
        return $this->rawSolrRequest ( '/admin/ping' );
    }

    /**
     * Performs a commit in Solr. This operation commits all pending changes.
     * 
     * @param string|array $languageCode
     *        Either a language code string, or an array of language codes to
     *        commit for
     * @return string The raw query result
     */
    function commit( $languageCodes = false )
    {
        if ( $languageCodes === false )
        {
            $languageCodes = array();
            foreach( eZContentLanguage::fetchList() as $language )
            {
                $languageCodes[] = $language->attribute( 'locale' );
            }
        }
        elseif ( is_string( $languageCodes ) )
        {
            $languageCodes = array( $languageCodes );
        }
        
        // from this point, $languageCodes is always an array
        $result = true;
        foreach( $languageCodes as $languageCode )
        {
            $updateResult = $this->postQuery ( '/update', '<commit/>', $languageCode, 'text/xml' );
            $result = $result and $this->validateUpdateResult( $updateResult );
        }
        return $result;
    }

    /**
     * Performs an optimize in Solr, which means the index is compacted
     * for maximum performance.
     * @param bool $withCommit Wether or not to COMMIT before OPTIMIZE
     * 
     * @note OPTIMIZE is a heavy operation, very similar to a MySQL OPTIMIZE.
     *       It should in no case be performed on a regular basis, as it will
     *       require a full copy of the index to a temporary location and may
     *       even lock down the solr server on heavy indexes
     * 
     * @todo Fix optimize. It should support the languages parameter
     */
    function optimize( $withCommit = false )
    {
        if ( $withCommit == true )
        {
            $this->commit();
        }
        //return the response for inspection if optimize was successful
        return $this->postQuery( '/update', '<optimize/>', 'text/xml' );
    }

    /**
     * Adds an array of documents (of type eZSolrDoc) to the Solr index
     * 
     * Adding multiple documents at the same time is much more efficient than
     * sending one document per HTTP request
     * 
     * @param array $docs
     *        List of documents to add, as an associative array of eZSolrDoc
     *               
     * @param boolean $commit wether or not to perform a solr commit at the end
     * 
     * @return bool True if the operation was successful, false otherwise
     *              The result is parsed using eZSolrBase::validateUpdateResult
     */
    function addDocs ( $docs = array(), $commit = true, $optimize = false  )
    {
        if (! is_array( $docs ) )
        {
            return false;
        }
        if ( count ( $docs ) == 0)
        {
            return false;
        }
        else
        {
            $postStrings = array();
            
            // @todo Think about a refactoring using an eZSolrDocList class
            // we first index all documents as an array of XML POST strings,
            // indexed by language
            // @todo Make this work for single core as well. Children class ?
            foreach ( $docs as $doc )
            {
                if ( !isset( $postStrings[$doc->LanguageCode] ) )
                {
                    $postStrings[$doc->LanguageCode] = $doc->docToXML();
                }
                else
                {
                    $postStrings[$doc->LanguageCode] .= $doc->docToXML();
                }
            }
            eZDebug::writeDebug( $postStrings, __METHOD__ );
            $result = true;
            foreach ( $postStrings as $languageCode => $languagePostString )
            {
                $postString = '<add>' . $languagePostString . '</add>';
                $updateResult = $this->postQuery( '/update', $postString, $languageCode, 'text/xml' );
                $result = $result and $this->validateUpdateResult ( $updateResult );
            }

            if ( $commit )
            {
                $this->commit( array_keys( $postStrings ) );
            }
            return $result;
        }

    }

    /**
     * Removes document(s) from the solr index
     *
     * @param array $docs
     *        List of documents to delete. Each key contains one sub-array, with
     *        the following keys: ID, InstallationID and LanguageCode
     * @param string $query
     *        Solr Query. If specified, this parameter will be used as a search
     *        query of documents to delete instead of $docs.
     *        Note: this parameter DOES have precedence over $docIDs
     * @param bool $optimize set to true to perform a solr optimize after delete
     * @return bool
     **/
    function deleteDocs ( $docs = array(), $query = false, $commit = true,  $optimize = false )
    {
        if ( empty( $query ) )
        {
            $postStrings = array();
            $result = true;
            foreach ( $docs as $languageCode => $guid )
            {
                $postString = "<delete><id>$guid</id></delete>";
                $updateXML = $this->postQuery( '/update', $postString, $languageCode, 'text/xml' );
                $result = $result and $this->validateUpdateResult( $updateXML );
            }
            $languageCodes = array_keys( $docs );
        }
        else
        {
            // @todo We don't know what core these get sent to. We can send this
            // query to all of them. This is what the $languageCodes = false
            // parameter means

            // send to all cores
            $languageCodes = false;
            
            $postString .= "<delete><query>$query</query></delete>";
            $updateXML = $this->postQuery ( '/update', $postString, $languageCodes, 'text/xml' );
            $result = $this->validateUpdateResult( $updateXML );
        }
        
        if ( $optimize )
        {
            $this->optimize( $commit );
        }
        elseif ( $commit )
        {
            $this->commit( $languageCodes );
        }

        return $result;
    }

    /**
     * Returns the appropriate URI depending on the request type and parameters
     * 
     * @param string $request Solr request (/select, /update...)
     * @param string|array $languageCode
     *        The request's language code(s). Can be a language code as a string,
     *        or a list of language codes as an array of strings
     * 
     * @return string|array The solr URI, or an array of URIs, indexed by
     *         language, if the request type can't be sharded (/update)
     * @since eZ Find 2.2
     * 
     * @throws ezcBaseValueException
     *         - the request isn't known
     *         - multiple languages are provided for a /update request (single only)
     */
    public function solrURL( $request, $languageCodes = false )
    {
        // no multicore, simple situation
        if ( !$this->enableMultiCore )
        {
            $url = "{$this->solrURI['protocol']}://{$this->solrURI['uri']}{$request}";
            eZDebugSetting::writeDebug(
                'ezfind-multicore',
                "Single-core request to: $url",
                __METHOD__ );
            return $url;
        }
        // multicore is enabled. Things actually get fun.
        else
        {
            switch( $request )
            {
                // single-core request
                case '/update':
                {
                    if ( is_array( $languageCodes ) )
                    {
                        if ( count( $languageCodes ) > 1 )
                        {
                            throw new ezcBaseValueException( 'languageCodes', $languageCodes, 'a single language code' );
                        }
                        else
                        {
                            $languageCodes = $languageCodes[1];
                        }
                    }

                    $core = $this->getLanguageCore( $languageCodes );
                    $url = "{$this->solrURI['protocol']}://{$this->solrURI['uri']}/{$core}/update";
                } break;
            
                // multi-core (sharded) request
                case '/select': 
                {
                    if ( is_array( $languageCodes ) )
                    {
                        if ( count( $languageCodes ) == 1 )
                        {
                            $languageCodes = $languageCodes[1];
                        }
                        // real multi-core request
                        else
                        {
                            // the base request goes to the default core
                            $baseURL = "{$this->solrURI['protocol']}://{$this->solrURI['uri']}/{$this->defaultCore}/select";
                            
                            // the shards parameter depends on the given languages list
                            foreach( $languageCodes as $languageCode )
                            {
                                $core = $this->getLanguageCore( $languageCode );
                                $shardParts[] = "{$this->solrURI['uri']}/$core";
                            }
                            $shards = implode( ',', $shardParts );
                            
                            $url = "{$baseURL}?shards=$shards";
                            break;
                        }
                    }
                    // simple core request, no sharding required
                    $core = $this->getLanguageCore( $languageCodes );
                    $url = "{$this->solrURI['protocol']}://{$this->solrURI['uri']}/{$core}{$request}";
                } break;
            
                default:
                {
                    throw new ezcBaseValueException( 'request', $request, '/update or /delete', 'argument' );
                }
            }
            eZDebugSetting::writeDebug(
                'ezfind-multicore',
                "Multi-core update request to: $url",
                __METHOD__ );
            return $url;
        }
    }
    
    /**
     * Returns the core that matches a language code
     * 
     * @param string $languageCode
     * 
     * @return string The core name configured for this language
     * @since eZ Find 2.2
     */
    public function getLanguageCore( $languageCode )
    {
        if ( !$this->enableMultiCore )
        {
            return false;
        }
        
        if ( isset( $this->languagesCoresMapping[$languageCode] ) )
        {
            return $this->languagesCoresMapping[$languageCode];
        }
        else
        {
            return $this->defaultCore;
        }
    }
    
    /**
     * Sends the updated elevate configuration to Solr
     * 
     * @note This method has no effect in a multicore configuration, as elevate
     *       isn't yet supported in that case
     * @return true
     */
    function pushElevateConfiguration( $params )
    {
        return true;
    }

}
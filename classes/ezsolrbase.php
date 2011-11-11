<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
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



/*!
 eZSolrBase is a PHP library for connecting and performing operations
 on the Solr server.

 It's recommended to have the PHP-CURL extension enabled with this class,
 but not required.
 */
class eZSolrBase
{
    const DEFAULT_REQUEST_CONTENTTYPE = 'application/x-www-form-urlencoded;charset=utf-8';

    const DEFAULT_REQUEST_USERAGENT = 'eZ Publish';

    /**
     * The solr search server URI
     * @var string
     */
    var $SearchServerURI;

    var $SolrINI;

    /**
     * Constructor.
     * Initializes the solr URI and various INI files
     *
     * @param string $baseURI An optional solr URI that overrides the INI one.
     */
    function __construct( $baseURI = false )
    {
        // @todo Modify this code to adapt to the new URI parameters
        // Also keep BC with the previous settings

        //$this->SearchServerURI = $baseURI;
        $this->SolrINI = eZINI::instance( 'solr.ini' );
        $iniSearchServerURI = $this->SolrINI->variable( 'SolrBase', 'SearchServerURI' );
        if ( $baseURI !== false )
        {
            $this->SearchServerURI = $baseURI;
        }
        elseif ( isset( $iniSearchServerURI ) )
        {
            $this->SearchServerURI = $this->SolrINI->variable( 'SolrBase', 'SearchServerURI' );
        }
        // fall back to hardcoded Solr default
        else
        {
            $this->SearchServerURI = 'http://localhost:8983/solr';
        }

    }

    /*!
     Build a HTTP GET query

     \param Solr request type name.
     \param Query parameters ( associative array )

     \return Complete HTTP get URL.
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

        return $this->SearchServerURI . $request . '?' . implode( '&', $encodedQueryParams );
    }


    /*!
     \protected
     Build HTTP Post string.

     \param Query parameters, associative array

     \return POST part of HTML request
     */
	function buildPostString( $queryParams )
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
        return implode( '&', $encodedQueryParams );
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
    protected function postQuery( $request, $postData, $contentType = self::DEFAULT_REQUEST_CONTENTTYPE )
    {
        $url = $this->SearchServerURI . $request;
        return $this->sendHTTPRequestRetry( $url, $postData, $contentType );
    }

    /**
     * Send an HTTP Get query to Solr engine
     *
     * @param string $request request name
     * @param string $getParams HTTP GET parameters, as an associative array
     *
     * @return Result of HTTP Request ( without HTTP headers )
     */
    function getQuery( $request, $getParams )
    {
        return $this->sendHTTPRequestRetry( eZSolrBase::buildHTTPGetQuery( $request, $getParams ) );
    }

    /*!
      OBS ! Experimental.

      Can be used for anything, uses the post method
      ResponseWriter wt=php is default, alternative: json or phps

      \param $request refers to the request handler called
      \param $params is an array of post variables to include. The actual values
             are urlencoded in the buildPostString() call
     */
    function rawSolrRequest ( $request = '', $params = array(), $wt = 'php' )
    {
        if ( count( $params ) == 0 && $request == '' )
        {
            return false;
        }
		$params['wt'] = $wt;
        $paramsAsString = $this->buildPostString( $params );
        $data = $this->postQuery( $request, $paramsAsString );
        $resultArray = array();
        if ( $data === false )
        {
            return false;
        }
        if ( $wt == 'php' )
        {
            if ( strpos( $data, 'array' ) === 0 )
            {
                eval( "\$resultArray = $data;");
            }
            else
            {
                eZDebug::writeError( 'Got invalid result from search engine.' . $data );
                return false;
            }
        }
        elseif ( $wt == 'json' )
        {
            $resultArray = json_decode ( $data, true );
        }
        //phps unserialize
        elseif ( $wt == 'phps' )
        {
            $resultArray = unserialize ( $data );
        }
        else
        {
            $resultArray[] = $data;
        }
        return $resultArray;
    }

    /**
     * Sends a ping request to solr
     *
     * @note OBS ! Experimental.
     *
     * @param string $wt
     *        Query response writer. Defaults to PHP array response format
     *
     * @return array The ping operation result
     */
    function ping ( $wt = 'php' )
    {
        return $this->rawSolrRequest ( '/admin/ping' );
    }

    /*!
      Performs a commit in Solr, which means the index is made live after performing
      all pending additions and deletes
     */
    function commit()
    {
        return $this->postQuery (  '/update', '<commit/>', 'text/xml' );
    }

    /*!
      Performs an optimize in Solr, which means the index is compacted
      for maximum performance.
      \param $withCommit means a commit is performed first
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
     * Function to validate the update result returned by solr
     *
     * A valid solr update reply contains the following document:
     * <?xml version="1.0" encoding="UTF-8"?>
     * <response>
     *   <lst name="responseHeader">
     *     <int name="status">0</int>
     *     <int name="QTime">3</int>
     *   </lst>
     *   <strname="WARNING">This response format is experimental.  It is likely to change in the future.</str>
     * </response>
     *
     * Function will check if solrResult document contains "<int name="status">0</int>"
     **/
    static function validateUpdateResult ( $updateResult )
    {
        if ( empty( $updateResult ) )
        {
            return false;
        }
        $dom = new DOMDocument( '1.0' );
        // Supresses error messages
        $status = $dom->loadXML( $updateResult, LIBXML_NOWARNING | LIBXML_NOERROR  );

        if ( !$status )
        {
            return false;
        }

        $intElements = $dom->getElementsByTagName( 'int' );

        if ( $intElements->length < 1 )
        {
            return false;
        }

        foreach ( $intElements as $intNode )
        {
            foreach ( $intNode->attributes as $attribute )
            {
                if ( ( $attribute->name === 'name' ) and ( $attribute->value === 'status' ) )
                {
                    //Then we have found the correct node
                    return ( $intNode->nodeValue === "0"  );
                }
            }
        }
        return false;
    }

    /**
     * Adds an array of docs (of type eZSolrDoc) to the Solr index for maximum
     * performance.
     * @param array $docs associative array of documents to add
     * @param boolean $commit wether or not to perform a solr commit at the end
     * @param integer $commitWithin specifies within how many milliseconds a commit should occur if no other commit
     *       is triggered in the meantime (Solr 1.4, eZ Find 2.2)
     */
    function addDocs ( $docs = array(), $commit = true, $optimize = false, $commitWithin = 0  )
    {
        if ( !is_array( $docs ) )
        {
            return false;
        }
        if ( count ( $docs ) == 0)
        {
            return false;
        }
        else
        {
            if ( is_integer( $commitWithin ) && $commitWithin > 0 )
            {
                $postString = '<add commitWithin="' . $commitWithin . '">';
            }
            else
            {
                $postString = '<add>';
            }

            foreach ( $docs as $doc )
            {
                $postString .= $doc->docToXML();
            }
            $postString .= '</add>';

            $updateResult = $this->postQuery ( '/update', $postString, 'text/xml' );

            if ( $optimize )
            {
                $this->optimize( $commit );
            }
            elseif ( $commit )
            {
                $this->commit();
            }
            return self::validateUpdateResult ( $updateResult );
        }

    }

    /**
     * Removes an array of docID's from the Solr index
     *
     * @param array $docsID List of document IDs to delete. If set to <empty>,
     *              $query will be used to delete documents instead.
     * @param string $query Solr Query. This will be ignored if $docIDs is set.
     * @param bool $optimize set to true to perform a solr optimize after delete
     * @return bool
     **/
    function deleteDocs ( $docIDs = array(), $query = false, $commit = true,  $optimize = false )
    {
        $postString = '<delete>';
        if ( empty( $query ) )
        {
            foreach ( $docIDs as $docID )
            {
                $postString .= '<id>' . $docID . '</id>';
            }
        }
        else
        {
            $postString .= '<query>' . $query . '</query>';
        }
        $postString .= '</delete>';
        $updateXML = $this->postQuery ( '/update', $postString, 'text/xml' );
        if ( $optimize )
        {
            $this->optimize( $commit );
        }
        elseif ( $commit )
        {
            $this->commit();
        }

        return self::validateUpdateResult( $updateXML );
    }

    /**
     * Sends the solr server a search request
     *
     * @param array|ezfSolrQueryBuilder $params
     *        Query parameters, either:
     *        - an array, as returned by ezfeZPSolrQueryBuilder::buildSearch
     *        or
     *        - an ezfeZPSolrQueryBuilder instance
     * @param string $wt Query response writer
     * @return array The search results
     */
    function rawSearch ( $params = array(), $wt = 'php' )
    {
        return $this->rawSolrRequest ( '/select' , $params, $wt );
    }

    /**
     * Sends the updated elevate configuration to Solr
     *
     * @params array $params Raw query parameters
     *
     *
     * @note This method is a simple wrapper around rawSearch in order to easily
     *       ignore elevate when using multicore
     * @return bool
     * @see rawSearch()
     */
    function pushElevateConfiguration( $params )
    {
        return $this->rawSearch( $params );
    }

    /**
     * Proxy method to {@link self::sendHTTPRequest()}.
     * Sometimes, an overloaded Solr server can result a timeout and drop the connection
     * In this case, we will retry just after, with a max number of retries defined in solr.ini ([SolrBase].ProcessMaxRetries)
     *
     * @param string $url
     * @param string $postData POST data as string (field=value&foo=bar). Default is false (HTTP Request will be GET)
     * @param string $contentType Default is {@link self::DEFAULT_REQUEST_CONTENTTYPE}
     * @param string $userAgent Default is {@link self::DEFAULT_REQUEST_USERAGENT}
     *
     * @return HTTP result ( without headers ), false if the request fails.
     */
    protected function sendHTTPRequestRetry( $url, $postData = false, $contentType = self::DEFAULT_REQUEST_CONTENTTYPE, $userAgent = self::DEFAULT_REQUEST_USERAGENT )
    {
        $maxRetries = (int)$this->SolrINI->variable( 'SolrBase', 'ProcessMaxRetries' );
        if ( $maxRetries < 1 )
        {
            eZDebug::writeWarning( 'solr.ini : [SolrBase].ProcessMaxRetries cannot be < 1' );
            $maxRetries = 1;
        }

        $tries = 0;
        while ( $tries < $maxRetries )
        {
            try
            {
                $tries++;
                return $this->sendHTTPRequest( $url, $postData, $contentType, $userAgent );
            }
            catch ( ezfSolrException $e )
            {
                $doRetry = false;
                $errorMessage = $e->getMessage();
                switch ( $e->getCode() )
                {
                    case ezfSolrException::REQUEST_TIMEDOUT : // Code error 28. Server is most likely overloaded
                    case ezfSolrException::CONNECTION_TIMEDOUT : // Code error 7, same thing
                        $errorMessage .= ' // Retry #'.$tries;
                        $doRetry = true;
                    break;
                }

                if ( !$doRetry )
                    break;
            }
        }

        return false;
    }

    /**
     * Send HTTP request. This code is based on eZHTTPTool::sendHTTPRequest, but contains
     * Some improvements. Will use Curl, if curl is present.
     *
     * @param string $url
     * @param string $postData POST data as string (field=value&foo=bar). Default is false (HTTP Request will be GET)
     * @param string $contentType Default is {@link self::DEFAULT_REQUEST_CONTENTTYPE}
     * @param string $userAgent Default is {@link self::DEFAULT_REQUEST_USERAGENT}
     *
     * @throws ezfSolrException Throws an ezfSolrException if the request results a timeout.
     *                          If curl is available, this exception will also be thrown, with its error number and message
     * @return HTTP result ( without headers ), false if the request fails.
     */
    function sendHTTPRequest( $url, $postData = false, $contentType = self::DEFAULT_REQUEST_CONTENTTYPE, $userAgent = self::DEFAULT_REQUEST_USERAGENT )
    {
        $connectionTimeout = $this->SolrINI->variable( 'SolrBase', 'ConnectionTimeout' );
        $processTimeout = $this->SolrINI->variable( 'SolrBase', 'ProcessTimeout' );


        if ( extension_loaded( 'curl' ) )
        {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $connectionTimeout );
            curl_setopt( $ch, CURLOPT_TIMEOUT, $processTimeout );
            if ( $this->SolrINI->variable( 'SolrBase', 'SearchServerAuthentication' ) === 'enabled' )
            {
                if ( $this->SolrINI->variable( 'SolrBase', 'SearchServerAuthenticationMethod' ) === 'basic' )
                {
                    curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
                    curl_setopt( $ch, CURLOPT_USERPWD, $this->SolrINI->variable( 'SolrBase', 'SearchServerUserPass' ) );
                }
            }
            //CURLOPT_TIMEOUT
            if ( $postData !== false )
            {
                curl_setopt( $ch, CURLOPT_POST, 1 );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
                if ( $contentType != '' )
                {
                    curl_setopt( $ch, CURLOPT_HTTPHEADER, array ( 'Content-Type: ' . $contentType ) );
                }
            }

            $data = curl_exec( $ch );
            $errNo = curl_errno( $ch );
            $err = curl_error( $ch );
            curl_close( $ch );
            if  ( $errNo )
            {
                throw new ezfSolrException( __METHOD__ . ' - curl error: ' . $err, $errNo );
            }
            else
            {
                return $data;
            }
        }
        else
        {
            preg_match( "/^((http[s]?:\/\/)([a-zA-Z0-9_.]+))?:?([0-9]+)?([\/]?[~]?(\.?[^.]+[~]?)*)/i", $url, $matches );
            $protocol = $matches[2];
            $host = $matches[3];
            $port = $matches[4];
            $path = $matches[5];
            if ( !$path )
            {
                $path = '/';
            }

            if ( $protocol == 'https://' )
            {
                $filename = 'ssl://' . $host;
            }
            else
            {
                $filename = 'tcp://' . $host;
            }

            // make sure we have a valid hostname or call to fsockopen() will fail
            $parsedUrl = parse_url( $filename );
            $ip = isset( $parsedUrl[ 'host' ] ) ? gethostbyname( $parsedUrl[ 'host' ] ) : '';
            $checkIP = ip2long( $ip );
            if ( $checkIP == -1 or $checkIP === false )
            {
                eZDebug::writeDebug( 'Could not find hostname: ' . $parsedUrl['host'], __METHOD__ );
                return false;
            }

            $errorNo = 0;
            $errorStr = '';
            $fp = @fsockopen( $filename, $port, $errorNo, $errorStr, $connectionTimeout );
            if ( !$fp )
            {
                eZDebug::writeDebug( 'Could not open connection to: ' . $filename . ':' . $port . '. Error: ' . $errorStr,
                                     __METHOD__ );
                return false;
            }

            $method = 'GET';
            if ( $postData !== false )
            {
                $method = 'POST';
            }

            $request = $method . ' ' . $path . ' ' . 'HTTP/1.1' . "\r\n" .
                "Host: $host\r\n" .
                "Accept: */*\r\n" .
                "Content-type: $contentType\r\n" .
                "Content-length: " . strlen( $postData ) . "\r\n" .
                "User-Agent: $userAgent\r\n" .
                "Pragma: no-cache\r\n" .
                "Connection: close\r\n\r\n";

            stream_set_timeout( $fp, $processTimeout );
            fputs( $fp, $request );
            if ( $method == 'POST' )
            {
                fputs( $fp, $postData );
            }

            $header = true;
            while( $header )
            {
                while ( !feof( $fp ) )
                {
                    $character = fgetc( $fp );
                    if ( $character == "\r" )
                    {
                        fgetc( $fp );
                        $character = fgetc( $fp );
                        if ( $character == "\r" )
                        {
                            fgetc( $fp );
                            $header = false;
                        }
                        break;
                    }
                }
            }

            $buf = '';
            while ( !feof( $fp ) )
            {
                $buf .= fgets( $fp, 128 );
            }
            $info = stream_get_meta_data( $fp );

            fclose( $fp );
            if ( $info['timed_out'] )
            {
                throw new ezfSolrException( __METHOD__ . ' - connection error: processing timed out', ezfSolrException::REQUEST_TIMEDOUT );
            }
            else
            {
                return $buf;
            }


        }
    }

}

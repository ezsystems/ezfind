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


/*!
 eZSolrBase is a PHP library for connecting and performing operations
 on the Solr server.

 It's recommended to have the PHP-CURL extension enabled with this class,
 but not required.
 */
class eZSolrBase
{
    var $SearchServerURI;
    var $SolrINI;

    /*!
     \constructor
     \brief Constructor

     \param string Solr server URL
    */
    function eZSolrBase( $baseURI = 'http://localhost:8983/solr' )
    {
        $this->SearchServerURI = $baseURI;
        $this->SolrINI = eZINI::instance( 'solr.ini' );
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

    /*!
     Send HTTP Post query to Solr engine

     \param string request name
     \param string post data
     \param string contentType

     \return Result of HTTP Request ( without HTTP headers ).
    */
    function postQuery( $request, $postData, $contentType = 'application/x-www-form-urlencoded' )
    {
        $url = $this->SearchServerURI . $request;
        return $this->sendHTTPRequest( $url, $postData, $contentType );
    }

    /*!
     Send HTTP Get query to Solr engine

     \param string request name
     \param string HTTP GET parameters

     \return Result of HTTP Request ( without HTTP headers ).
    */
    function getQuery( $request, $getParams )
    {
        return $this->sendHTTPRequest( eZSolrBase::buildHTTPGetQuery( $request, $getParams ) );
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
        if ( count( $params ) == 0 || $request == '' )
        {
            return false;
        }
		$params['wt'] = $wt;
        $paramsAsString = $this->buildPostString( $params );
        $data=$this->postQuery( $request, $paramsAsString );
        //print_r ($data);
        //echo ('data is ' . strlen($data) . " chars long\n");
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
                eZDebug::writeError( 'Got invalid result from search engine.' );
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

    /*!
      OBS ! Experimental.
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
        $this->postQuery( '/update', '<optimize/>', 'text/xml' );
    }


    /*!
      Adds an array of docs (of type eZSolrDoc) to the Solr index
      for maximum performance.

      \param array associative array of documents to add.
      \param boolean $commit means a commit is performed afterwards
     */
    function addDocs ( $docs = array(), $commit = false  )
    {
        //
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
            $postString = '<add>';
            foreach ( $docs as $doc )
            {
                $postString .= $doc->docToXML();
            }
            $postString .= '</add>';

            $this->postQuery ( '/update', $postString, 'text/xml' );
            if ( $commit )
            {
                $this->commit();
            }
            return true;
        }

    }

    /*!
      Adds an array of docID's from the Solr index

      \param array List of document IDs to delete. If set to <empty>,
                   $query will be used to delete documents instead.
      \param string Solr Query. This will be ignored if \a$docIDs is set.
      \param boolean $optimize means an optimize is performed afterwards ( optional, default value: false )
     */
    function deleteDocs ( $docIDs = array(), $query = false, $optimize = false )
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
        $this->postQuery ( '/update', $postString, 'text/xml' );
        if ( $optimize )
        {
            $this->optimize();
        }
        return true;
    }

    function rawSearch ( $params = array(), $wt = 'php' )
    {
        return $this->rawSolrRequest ( '/select' , $params, $wt );
    }

    /*!
     Send HTTP request. This code is based on eZHTTPTool::sendHTTPRequest, but contains
     Some improvements. Will use Curl, if curl is present.

     \param \a $url
     \param \a $postData ( optional, default false )
     \param \a $contentType ( optional, default '' )
     \param \a $userAgent ( optional, default 'eZ Publish' )

     \return HTTP result ( without headers ), false if the request fails.
    */
    function sendHTTPRequest( $url, $postData = false, $contentType = '', $userAgent = 'eZ Publish' )
    {
        $connectionTimeout = $this->SolrINI->variable( 'SolrBase', 'ConnectionTimeout' );

        if ( extension_loaded( 'curl' ) )
        {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $connectionTimeout );
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
            curl_close( $ch );
            if  ( $errNo )
            {
                eZDebug::writeError( 'curl error: ' . $errNo, 'eZSolr::sendHTTPRequest()' );
                return false;
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
                eZDebug::writeDebug( 'Could not find hostname: ' . $parsedUrl['host'], 'eZSolr::sendHTTPRequest()' );
                return false;
            }

            $errorNo = 0;
            $errorStr = '';
            $fp = @fsockopen( $filename, $port, $errorNo, $errorStr, $connectionTimeout );
            if ( !$fp )
            {
                eZDebug::writeDebug( 'Could not open connection to: ' . $filename . ':' . $port . '. Error: ' . $errorStr,
                                     'eZSolr::sendHTTPRequest()' );
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
            fclose($fp);

            return $buf;
        }
    }
}
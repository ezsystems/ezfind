<?php
/**
 * File containing ezfSolrException class
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @author jv
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

/**
 * Exception for Solr issues
 */
class ezfSolrException extends ezfBaseException
{
    /**
     * Error code for a solr request timeout
     * @var int
     */
    const REQUEST_TIMEDOUT = 28, // Copy of CURLE_OPERATION_TIMEOUTED constant
          CONNECTION_TIMEDOUT = 7; // Copy of CURLE_COULDNT_CONNECT constant that can be resulted by a connection timeout

    public function __construct( $message = '', $code = 0 )
    {
        $this->code = $code;
        parent::__construct( $message );
    }
}
?>

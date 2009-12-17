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
 Solr Document class. Converts an associative array to Solr acceptacle XML.
*/
class eZSolrDoc
{
    /**
     * @constructor
     *
     * @param int Document boost, optional
     */
    function eZSolrDoc( $boost = false )
    {
        $this->Doc = new DOMDocument( '1.0', 'utf-8' );
        $this->RootElement = $this->Doc->createElement( 'doc' );
        $this->Doc->appendChild( $this->RootElement );

        if ( $boost && is_numeric( $boost ) )
        {
            $this->RootElement->setAttribute( 'boost', $boost );
        }
    }

    /**
     * Set document boost
     *
     * @param float Document boost
     */
    public function setBoost ( $boost = false )
    {
        if ( $boost && is_numeric( $boost ) )
        {
            $this->RootElement->setAttribute( 'boost', $boost );
        }
    }

    /**
     * Add document field
     *
     * @param string Field name
     * @param mixed Field content. $content may be a value or an array containing values.
     * @param float Field boost ( optional ).
     */
    public function addField ( $name, $content, $boost = false )
    {
        if ( !is_array( $content ) )
        {
            $content = array( $content );
        }
        foreach( $content as $value )
        {
            // $value should never be array. Log the value and the stack trace.
            if ( is_array( $value ) )
            {
                $backtrace = debug_backtrace();
                $dump = array( $backtrace[0], $backtrace[1] );
                eZDebug::writeError( 'Tried to index array value: ' . $name . "\nValue: " . var_export( $value, 1 ) .
                                     "\nStack trace: " . var_export( $dump, 1 ) );
                continue;
            }
            $fieldElement = $this->Doc->createElement( 'field' );
            $fieldElement->appendChild( $this->Doc->createTextNode( $value ) );
            $fieldElement->setAttribute( 'name', $name );

            if ( $boost && is_numeric( $boost ) )
            {
                $fieldElement->setAttribute( 'boost', $boost );
            }

            $this->RootElement->appendChild( $fieldElement );
        }
    }

    /**
     * Convert current object to XML string
     *
     * @return string XML string.
     */
    function docToXML()
    {
        $rawXML = $this->Doc->saveXML( $this->RootElement );
        //make sure there are no control characters left
        return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $rawXML);
    }


    /// Vars

    var $Doc;
    var $RootElement;
    
    /**
     * The document's language code
     * @var string
     * @since eZ Find 2.2
     */
    var $LanguageCode;
}


?>
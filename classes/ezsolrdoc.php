<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 *
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 * @license GPL
 */


/**
 *
 * Solr Document class. Converts an associative array to Solr XML.
 */
class eZSolrDoc
{
    /**
     *
     * @var array
     */
    public  $Doc   = array();

    /**
     *
     * @var numeric
     */
    private $DocBoost = null;

    /*
     * @var \Dom
     */
    private $DomDoc;

    /*
     * @var
     */
    private $DomRootElement;

    /**
     * The document's language code
     * @var string
     * @since eZ Find 2.2
     */
    public $LanguageCode;

    /**
     * @constructor
     *
     * @param int Document boost, optional
     */
    function __construct( $boost = null )
    {
        if ( $boost !== null && is_numeric( $boost ))
        {
            $this->DocBoost = $boost;
        }
    }

    /**
     * Set document boost
     *
     * @param float Document boost
     */
    public function setBoost ( $boost = null )
    {
        if ( $boost !== null && is_numeric( $boost ))
        {
            $this->DocBoost = $boost;
        }
    }

    /**
     * Add document field
     *
     * @param string Field name
     * @param mixed Field content. $content may be a value or an array containing values.
     *     if the the array has more than one element, the schema declaration must be multi-valued too
     * @param float Field boost ( optional ).
     */
    public function addField ( $name, $content, $boost = null )
    {
        if ( !is_array( $content ) )
        {
            $content = array( $content );
        }

        if (array_key_exists($name, $this->Doc))
        {
            $this->Doc[$name]['content'] = array_merge($this->Doc[$name]['content'], $content);
        }
        else
        {
            $this->Doc[$name]['content'] =  $content;
        }
        $this->Doc[$name]['boost'] = $boost;
    }

    public function setFieldBoost ($name, $boost)
    {
        if ( $boost !== null && is_numeric($boost) && array_key_exists($name, $this->Doc))
        {
            $this->Doc[$name]['boost'] = $boost;
        }
    }

    /**
     * Convert current object to XML string
     *
     * @return string XML string.
     */
    function docToXML()
    {
        $this->DomDoc = new DOMDocument( '1.0', 'utf-8' );
        $this->DomRootElement = $this->DomDoc->createElement( 'doc' );
        $this->DomDoc->appendChild( $this->DomRootElement );

        if ($this->DocBoost !== null && is_numeric( $this->DocBoost ) )
        {
            $this->DomRootElement->setAttribute( 'boost', $this->DocBoost );
        }

        foreach ($this->Doc as $name => $field) {
            foreach( $field['content'] as $value )
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
                $fieldElement = $this->DomDoc->createElement( 'field' );
                $fieldElement->appendChild( $this->DomDoc->createTextNode( $value ) );
                $fieldElement->setAttribute( 'name', $name );

                if ( $field['boost'] !== null && is_numeric( $field['boost'] ) )
                {
                    $fieldElement->setAttribute( 'boost', $field['boost'] );
                }

                $this->DomRootElement->appendChild( $fieldElement );
            }
        }


        $rawXML = $this->DomDoc->saveXML( $this->DomRootElement );
        //make sure there are no control characters left that could currupt the XML string
        return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $rawXML);
    }

}


?>
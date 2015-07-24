<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 *
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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
     * @var array of child documents as eZSolrDoc objects!
     */
    public  $Children = array();

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
     *
     * @param DOMDocument $domDoc The (usually) empty DOM master document
     * @param mixed $inputArray containing the fields, field values and field boosts
     * @param Booleam $boost the Lucene overall document boost to apply if any
     * @return DOMElement The doc and field structure
     */
    public static function createDocElementFromArray( DOMDocument $domDoc, $inputArray = array(), $boost = NULL )
    {
        $docRootElement = $domDoc->createElement( 'doc' );

        if ( $boost !== null && is_numeric( $boost ) )
        {
            $docRootElement->setAttribute( 'boost', $boost );
        }

        foreach ( $inputArray as $name => $field )
        {
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
                $fieldElement = $domDoc->createElement( 'field' );
                $fieldElement->appendChild( $domDoc->createTextNode( $value ) );
                $fieldElement->setAttribute( 'name', $name );

                if ( isset( $field['boost'] ) && is_numeric( $field['boost'] ) )
                {
                    $fieldElement->setAttribute( 'boost', $field['boost'] );
                }

                $docRootElement->appendChild( $fieldElement );
            }
        }
        return $docRootElement;
    }

    /**
     * Convert current object to XML string
     *
     * @return string XML string.
     */
    function docToXML()
    {
        $this->DomDoc = new DOMDocument( '1.0', 'utf-8' );
        $this->DomRootElement = self::createDocElementFromArray( $this->DomDoc, $this->Doc, $this->DocBoost );

        foreach ( $this->Children as $child )
        {
            if ( $child instanceof eZSolrDoc )
            {
                $this->DomRootElement->appendChild( self::createDocElementFromArray( $this->DomDoc, $child->Doc ) );
            }
        }

        $this->DomDoc->appendChild( $this->DomRootElement );

        $rawXML = $this->DomDoc->saveXML( $this->DomRootElement );
        //make sure there are no control characters left that could currupt the XML string
        return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $rawXML);
    }

}


?>
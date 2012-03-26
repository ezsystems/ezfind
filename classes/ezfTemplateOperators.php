<?php
/**
 * File containing the ezfTemplateOperators class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Operators for eZFind
 */
class ezfTemplateOperators
{
    const QUOTES_TO_ESCAPE = '"';

    public function operatorList()
    {
        return array( 'solr_escape', 'solr_quotes_escape' );
    }

    public function namedParameterPerOperator()
    {
        return true;
    }

    public function namedParameterList()
    {
        return array(
            'solr_escape'        => array(),
            'solr_quotes_escape' => array(
                'leave_edge_quotes' => array(
                    'type'        => 'boolean',
                    'required'    => false,
                    'default'     => false
                )
            ),
        );
    }

    public function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'solr_escape':
                $operatorValue = $this->escapeQuery( $operatorValue );
                break;

            case 'solr_quotes_escape':
                $operatorValue = $this->escapeQuotes( $operatorValue, (bool)$namedParameters['leave_edge_quotes'] );
                break;
        }
    }

    /**
     * Encloses $query inside double quotes so that special characters can be handled litteraly by Solr.
     * If there are double quotes inside $query, they will be escaped.
     * Edge quotes are ignored.
     *
     * @param string $query
     * @return string
     * @see http://lucene.apache.org/core/old_versioned_docs/versions/3_4_0/queryparsersyntax.html#Escaping%20Special%20Characters
     * @see http://issues.ez.no/18701
     */
    public function escapeQuery( $query )
    {
        if ( $query[0] === '"' && $query[strlen( $query ) -1] === '"' )
        {
            $query = substr( $query, 1, -1 );
        }

        return '"' . $this->escapeQuotes( $query ) . '"';
    }

    /**
     * Escapes quotes in $query.
     *
     * @param string $query
     * @param bool $leaveEdgeQuotes If true, edge quotes surrounding $query will be ignored.
     * @return string
     */
    public function escapeQuotes( $query, $leaveEdgeQuotes = false )
    {
        if ( $leaveEdgeQuotes && $query[0] === '"' && $query[strlen( $query ) -1] === '"' )
        {
            return '"' . addcslashes(
                substr( $query, 1, -1 ),
                self::QUOTES_TO_ESCAPE
            ) . '"';
        }
        else
        {
            return addcslashes( $query, self::QUOTES_TO_ESCAPE );
        }
    }
}

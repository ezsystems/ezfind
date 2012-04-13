<?php

/**
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

/**
 * class ezfSolrUtils contains various methods to manipulate one or more Solr indexes
 * with higher level functions
 */
class ezfSolrUtils
{

    /**
     *
     */


    /*function  __construct( )
    {

    }*/


    /**
     *
     * @param eZSolrBase $fromCore
     * @param eZSolrBase $toCore
     * @param string $keyField
     * @param string $docID
     * @param array $params hash array with fields to modify or add, fields to suppress
     *      hash keys: 'modify_fields' (hash array key->value),
     *                 'suppress_fields' (array of strings),
     *                 'add_fields' (hash array key->value)
     * @param boolean $commit
     * @param boolean $optimize
     * @param int $commitWithin
     * @return boolean success (true |  false)
     */
    public static function copyDocument ( eZSolrBase $fromCore, eZSolrBase $toCore, $keyField, $docID, $params = array(), $commit = false, $optimize = false, $commitWithin = 0 )
    {

        $resultArray = $fromCore->rawSearch( array( 'q'=> $keyField . ':' . $docID, 'fl' => '*' ), 'json' );
        $result = $resultArray['response'];
        $searchCount = $result['numFound'];
        $docs = $result['docs'];
        if ( $searchCount == 1 )
        {
            $doc = $docs[0];
            $cleanDoc = array();
            //loop over all fields, remove, replace, add and prepare a new doc
            //first replace values if needed
            if ( count( $params['modify_fields'] ) > 0 )
            {
                $doc = array_merge( $doc, $params['modify_fields'] );
            }
            //add new fields
            if ( count( $params['add_fields'] ) > 0 )
            {
                $doc = array_merge( $doc, $params['add_fields'] );
            }
            foreach ( $params['suppress_fields'] as $key )
            {
               if ( array_key_exists( $key, $doc ) )
               {
                   unset( $doc[$key] );
               }
            }
            //recreate the new toc do add/update in the destination index
            $destDoc = new eZSolrDoc();
            foreach ( $doc as $fieldName => $fieldValue )
            {
                $destDoc->addField( $fieldName, $fieldValue );
            }
            return $toCore->addDocs( array( $destDoc ), $commit, $optimize, $commitWithin );

        }
        else
        {
            // todo: some logging, but through DI
            return false;
        }
    }

    /**
     *
     * @param eZSolrBase $fromCore
     * @param eZSolrBase $toCore
     * @param string $keyField
     * @param string $docID
     * @param mixed $params hash array with fields to modify or add, fields to suppress
     *      hash keys: 'modify_fields' (hash array key->value),
     *                 'suppress_fields' (array of strings),
     *                 'add_fields' (hash array key->value)
     * @param boolean $commit
     * @param boolean $optimize
     * @param int $commitWithin
     * @return boolean success
     */
    public static function moveDocument ( eZSolrBase $fromCore, eZSolrBase $toCore, $keyField, $docID, $params = array(), $commit = false, $optimize = false, $commitWithin = 0 )
    {
        if ( self::copyDocument( $fromCore, $toCore, $keyField, $docID, $params, $commit, $optimize, $commitWithin ) )
        {
            $fromCore->deleteDocs( array(), $keyField . ':' . $docID );
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     *
     * @param eZSolrBase $fromCore
     * @param eZSolrBase $toCore
     * @param <type> $keyField
     * @param <type> $filterQuery
     * @param <type> $params hash array with fields to modify or add, fields to suppress
     *      hash keys: 'modify_fields' (hash array key->value),
     *                 'suppress_fields' (array of strings),
     *                 'add_fields' (hash array key->value)
     * @param <type> $commit
     * @param <type> $optimize
     * @param <type> $commitWithin
     */
    public static function copyDocumentsByQuery ( eZSolrBase $fromCore, eZSolrBase $toCore, $keyField, $filterQuery, $params = array(), $commit = false, $optimize = false, $commitWithin = 0 )
    {
        $resultArray = $fromCore->rawSearch( array( 'q'=> '*:*', 'fl' => $keyField, 'rows' => 1000000 , 'fq' => $filterQuery ), 'json' );
        foreach ( $resultArray['response']['docs'] as $doc )
        {
            self::copyDocument( $fromCore, $toCore, $keyField, $doc[$keyField], $params, false, false, $commitWithin );
        }
        if ( $commitWithin == 0 )
        {
            $fromCore->commit();
            $toCore->commit();
        }
    }


    /**
     *
     * @param eZSolrBase $fromCore
     * @param eZSolrBase $toCore
     * @param <type> $keyField
     * @param <type> $filterQuery
     * @param <type> $params hash array with fields to modify or add, fields to suppress
     *      hash keys: 'modify_fields' (hash array key->value),
     *                 'suppress_fields' (array of strings),
     *                 'add_fields' (hash array key->value)
     * @param <type> $commit
     * @param <type> $optimize
     * @param <type> $commitWithin
     */
    public static function moveDocumentsByQuery ( eZSolrBase $fromCore, eZSolrBase $toCore, $keyField, $filterQuery, $params = array(), $commit = false, $optimize = false, $commitWithin = 0 )
    {
        $resultArray = $fromCore->rawSearch( array( 'q'=> '*:*', 'fl' => $keyField, 'rows' => 1000000 , 'fq' => $filterQuery ), 'json' );
        foreach ( $resultArray['response']['docs'] as $doc )
        {
            // don't issue commits, let alone optimize commands for this batch
            $result = self::moveDocument( $fromCore, $toCore, $keyField, $doc[$keyField], $params, false, false, $commitWithin );
        }
        if ( $commitWithin == 0 )
        {
            $fromCore->commit();
            $toCore->commit();
        }
    }

    /**
     *
     * @param eZSolrBase $solrCore the Solr instance to use
     * @param array $fields a hash array in teh form fieldname => fieldvalue (single scalar or array for multivalued fields)
     * @param boolean $commit do a commit or not
     * @param boolean $optimize do an optimize or not, usually false as this can be very CPU intensive
     * @param int $commitWithin optional commitWithin commit window expressed in milliseconds
     * @return boolean success or failure
     */
    public static function addDocument (eZSolrBase $solrCore, $fields = array(), $commit = false, $optimize = false, $commitWithin = 0 )
    {
        if ( count( $fields ) == 0 )
        {
            return false;
        }
        $destDoc = new eZSolrDoc();
        foreach ( $fields as $fieldName => $fieldValue )
        {
            $destDoc->addField( $fieldName, $fieldValue );
        }
        return $solrCore->addDocs( array( $destDoc ), $commit, $optimize, $commitWithin );
    }



}


?>

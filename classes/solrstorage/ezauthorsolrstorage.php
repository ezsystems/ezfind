<?php

/**
 * File containing the ezauthorSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezauthorSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $authorList = array();
        foreach ( $contentObjectAttribute->attribute( 'content' )->attribute( 'author_list' ) as $author )
        {
            $authorList[] = array(
                'id' => $author['id'],
                'name' => $author['name'],
                'email' => $author['email'],
            );
        }

        return array(
            'content' => $authorList,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

<?php

/**
 * File containing the ezdatetimeSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */

class ezdatetimeSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $dateTime = new DateTime( '@' . $contentObjectAttribute->attribute( 'data_int' ) );
        return array(
            'content' => $dateTime->format( 'c' ),
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

<?php

/**
 * File containing the ezdatetimeSolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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

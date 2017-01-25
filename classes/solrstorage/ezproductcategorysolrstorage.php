<?php

/**
 * File containing the ezproductcategorySolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

class ezproductcategorySolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $category =  $contentObjectAttribute->attribute( 'content' );

        return array(
            'content' => array(
                'name' => $category ? $category->attribute( 'name' ) : null,
                'id' => $category ? $category->attribute( 'id' ) : null,
            ),
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

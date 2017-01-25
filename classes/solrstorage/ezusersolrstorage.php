<?php

/**
 * File containing the ezuserSolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

class ezuserSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $user = eZUser::fetch( $contentObjectAttribute->attribute( "contentobject_id" ) );
        return array(
            'content' => array(
                'id' => $user->attribute( 'id' ),
                'login' => $user->attribute( 'login' ),
                'email' => $user->attribute( 'email' ),
            ),
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

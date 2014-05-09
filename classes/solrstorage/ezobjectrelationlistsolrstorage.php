<?php

/**
 * File containing the ezobjectrelationlistSolrStorage class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

class ezobjectrelationlistSolrStorage extends ezdatatypeSolrStorage
{
    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return array
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {
        $objectAttributeContent = $contentObjectAttribute->attribute( 'content' );
        $objectIDList = array();
        foreach ( $objectAttributeContent['relation_list'] as $objectInfo )
        {
            $objectIDList[] = $objectInfo['contentobject_id'];
        }

        return array(
            'content' => $objectIDList,
            'has_rendered_content' => false,
            'rendered' => null,
        );
    }
}

?>

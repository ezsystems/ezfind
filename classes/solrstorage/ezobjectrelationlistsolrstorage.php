<?php

/**
 * File containing the ezobjectrelationlistSolrStorage class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
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

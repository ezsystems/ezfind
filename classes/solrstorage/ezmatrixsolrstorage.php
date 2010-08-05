<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezmatrixSolrStorage extends ezdatatypeSolrStorage
{

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return json encoded string for further processing
     * required first level elements 'method', 'version_format', 'data_type_identifier', 'content'
     * optional first level element is 'rendered' which should store (template) rendered xhtml snippets
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute)
    {

        

        $attributeContents = $contentObjectAttribute->content();
        $cellList          = $attributeContents->attribute( 'cells' );

        $availableCells = array();

        for( $i = 0; $i < count( $cellList ); $i++ )
        {

            $availableCells[] = array( $cellList[$i] => $cellList[++$i] );
        }

        $target = array(
                'content' => $availableCells,
                'has_rendered_content' => false,
                'rendered' => null
                );

        return $target;
    }

    /**
     *
     * @param string $jsonString
     * @return mixed
     */

}


?>

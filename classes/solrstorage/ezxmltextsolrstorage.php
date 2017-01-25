<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezxmltextSolrStorage extends ezdatatypeSolrStorage
{

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute the attribute to serialize
     * @param eZContentClassAttribute $contentClassAttribute the content class of the attribute to serialize
     * @return json encoded string for further processing
     * required first level elements 'method', 'version_format', 'data_type_identifier', 'content'
     * optional first level element is 'rendered' which should store (template) rendered xhtml snippets
     */
    public static function getAttributeContent( eZContentObjectAttribute $contentObjectAttribute, eZContentClassAttribute $contentClassAttribute )
    {

        $dataTypeIdentifier = $contentObjectAttribute->attribute( 'data_type_string' );
        $attributeContents = $contentObjectAttribute->content();
        $doc = new DOMDocument( '1.0' );
        $doc->loadXML( $attributeContents->attribute( 'xml_data' ) );
        $xpath = new DOMXPath( $doc );
        $content = $doc->saveXML( $xpath->query( '/*' )->item( 0 ) );

        $target = array(
                'content' => $content,
                'has_rendered_content' => $contentObjectAttribute->hasContent(),
                'rendered' => $attributeContents->attribute( 'output' )->attribute( 'output_text' )
                );

        return  $target;
    }


}


?>

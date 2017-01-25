<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

class ezfSolrDocumentFieldXML extends ezfSolrDocumentFieldBase
{
    /**
     *
     * @param text $text
     * @return text
     *
     * instead of walking thorugh the dom tree, strip all xml/html like
     * this is more brute force, but helps in the case of html literal blocks
     * which are returned verbatim by ezxml attribute meta data function
     */
    public function strip_html_tags( $text )
    {
        $text = preg_replace(
            array(
            // Replace ezmatrix specific cell and column tags by a space
            '@<c[^>]*?>(.*?)</c>@siu',
            '@<column[^>]*?>(.*?)</column>@siu',
            // Remove invisible content
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
            // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
            '@</?(br)@iu'
            ),
            array(
            ' $0 ', ' $0 ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0", "\n"
            ),
            $text );
        $text = strip_tags( $text );

        return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
    }


    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData()
    {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldName = self::getFieldName( $contentClassAttribute );

        switch ( $contentClassAttribute->attribute( 'data_type_string' ) )
        {
            case 'ezxmltext' :
            {
            // $xmlData = $this->ContentObjectAttribute->attribute( 'content' )->attribute( 'xml_data' );
            $xmlData = $this->ContentObjectAttribute->attribute( 'content' )->attribute( 'output' )->attribute( 'output_text' );
            } break;

            case 'ezmatrix' :
            {
                $xmlData = $this->ContentObjectAttribute->attribute( 'content' )->xmlString();
            } break;

            case 'eztext' :
            {
                $xmlData = $this->ContentObjectAttribute->attribute( 'data_text' );
            } break;

            default:
            {
                    return array( $fieldName => '' );
            } break;
        }
        $cleanedXML = $this->strip_html_tags( $xmlData );
        return array( $fieldName => $cleanedXML );
    }
}

?>

<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

class eZFindResultNode extends eZContentObjectTreeNode
{
    /*!
     \reimp
    */
    function eZFindResultNode( $rows = array() )
    {
        $this->eZContentObjectTreeNode( $rows );
        if ( isset( $rows['id'] ) )
        {
            $this->ContentObjectID = $rows['id'];
        }
        $this->LocalAttributeValueList = array();
        $this->LocalAttributeNameList = array( 'is_local_installation',
                                               'name',
                                               'global_url_alias',
                                               'published',
                                               'language_code',
                                               'highlight',
                                               'score_percent',
                                               'elevated'
                );
    }

    /*!
     \reimp
    */
    function attribute( $attr, $noFunction = false )
    {
        $retVal = null;

        switch ( $attr )
        {
            case 'object':
            {
                if ( $this->attribute( 'is_local_installation' ) )
                {
                    $retVal = eZContentObjectTreeNode::attribute( $attr, $noFunction );
                }
                else
                {
                    if ( empty( $this->ResultObject ) )
                    {
                        $this->ResultObject = new eZFindResultObject( array( 'published' => $this->attribute( 'published' ) ) );
                    }
                    $retVal = $this->ResultObject;
                }
            } break;

            case 'language_code':
            {
                $retVal = $this->CurrentLanguage;
            } break;

            default:
            {
                if ( in_array( $attr, $this->LocalAttributeNameList ) )
                {
                    $retVal = isset( $this->LocalAttributeValueList[$attr] ) ? $this->LocalAttributeValueList[$attr] : null;
                    // Timestamps are stored as strings for remote objects, so it must be converted.
                    if ( $attr == 'published' )
                    {
                        $retVal = strtotime( $retVal );
                    }
                }
                else if ( $this->attribute( 'is_local_installation' ) )
                {
                    $retVal = eZContentObjectTreeNode::attribute( $attr, $noFunction );
                }
            } break;
        }

        return $retVal;
    }

    /*!
     \reimp
    */
    function attributes()
    {
        return array_merge( $this->LocalAttributeNameList,
                            eZContentObjectTreeNode::attributes() );
    }

    /*!
     \reimp
    */
    function hasAttribute( $attr )
    {
        return ( in_array( $attr, $this->LocalAttributeNameList ) ||
                 eZContentObjectTreeNode::hasAttribute( $attr ) );
    }

    /*!
     \reimp
    */
    function setAttribute( $attr, $value )
    {
        switch( $attr )
        {
            case 'language_code':
            {
                $this->CurrentLanguage = $value;
            } break;

            default:
            {
                if ( in_array( $attr, $this->LocalAttributeNameList ) )
                {
                    $this->LocalAttributeValueList[$attr] = $value;
                }
                else
                {
                    eZContentObjectTreeNode::setAttribute( $attr, $value );
                }
            }
        }
    }

    /// Member vars
    var $CurrentLanguage;
    var $LocalAttributeValueList;
    var $LocalAttributeNameList;
    var $ResultObject;
}

?>

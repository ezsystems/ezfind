<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2013 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezfindresultnode.php
*/

/*!
  \class eZFindResultNode ezfindresultnode.php
  \brief The class eZFindResultNode does

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
                                               'score_percent' );
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

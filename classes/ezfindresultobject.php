<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

class eZFindResultObject extends eZContentObject
{
    /*!
     \reimp
    */
    function eZFindResultObject( $rows = array() )
    {
        $this->LocalAttributeValueList = array();
        $this->LocalAttributeNameList = array( 'published' );

        foreach ( $rows as $name => $value )
        {
            $this->setAttribute( $name, $value );
        }
    }

    /*!
     \reimp
    */
    function attribute( $attr, $noFunction = false )
    {
        $retVal = null;
        switch ( $attr )
        {
            default:
            {
                if ( in_array( $attr, $this->LocalAttributeNameList ) )
                {
                    $retVal = isset( $this->LocalAttributeValueList[$attr] ) ?
                        $this->LocalAttributeValueList[$attr] : null;
                }
            } break;
        }
        return $retVal;
    }

    /*!
     \reimp
    */
    function setAttribute( $attr, $value )
    {
        if ( in_array( $attr, $this->LocalAttributeNameList ) )
        {
            $this->LocalAttributeValueList[$attr] = $value;
        }
    }

    /*!
     \reimp
    */
    function attributes()
    {
        return array_merge( $this->LocalAttributeNameList,
                            eZContentObject::attributes() );
    }

    /*!
     \reimp
    */
    function hasAttribute( $attr )
    {
        return ( in_array( $attr, $this->LocalAttributeNameList ) ||
                 eZContentObject::hasAttribute( $attr ) );
    }


    /// Object variables
    var $LocalAttributeValueList;
    var $LocalAttributeNameList;
}

?>

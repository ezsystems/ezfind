<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
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


class eZSolrDoc
{

    function eZSolrDoc( $boost = false )
    {
        $this->Doc = array();
        $this->Doc['element'] = 'doc';
        if ( $boost && is_numeric( $boost ) )
        {
            $this->Doc['attr'] = array ( 'boost' => $boost );
        }
        $this->Doc['value'] = array();
    }

    function setBoost ( $boost = false )
    {
        if ( $boost && is_numeric( $boost ) )
        {
            $this->Doc['attr'] = array ( 'boost' => $boost );
        }
    }

    function addField ( $name, $content, $boost = false )
    {

        if ( $boost && is_numeric( $boost ) )
        {
            $attrArray = array( 'name' => $name, 'boost' => $boost );
        }
        else
        {
            $attrArray = array( 'name' => $name );
        }

        //check for multiple values
        if ( is_array( $content ) )
        {
            foreach ($content as $value)
            {
                $this->Doc['value'][] = array( 'element' => 'field', 'value' => $value, 'attr' => $attrArray );
            }

        }
        else
        {
            $this->Doc['value'][] = array( 'element' => 'field', 'value' => $content, 'attr' => $attrArray );
        }

    }
    /*!
     \brief Utility: set atttributes of an xml element, expects an assoc array
    */
	function xmlAttributes( $attr = array() )
	{
		if (is_array($attr))
		{
			$str = '';
			foreach ($attr as $key => $value)
			{
				$str .= " $key=".'"'. htmlspecialchars($value, ENT_QUOTES, 'UTF-8') .'"';
            }
            return $str;
        }
    }


	/*!
     \brief Utility: simple array to xml functions, recursive. If first level key is numeric, then for each assoc array which is supposed to be there
    */
	function docArrayToXML ( $assocArray = array() )
	{
		$outputString = '';
	    foreach ($assocArray as $element => $value)
		{
			if (is_numeric($element))
			{
				if ($value['element'])
				{
					$outputString .= '<'. $value['element'];
					if (isset($value['attr']) && is_array($value['attr']))
					{
						$outputString .= $this->xmlAttributes($value['attr']);
					}

					if ($value['value'] != '')
					{
						$outputString .= '>'. (is_array($value['value']) ? $this->docArrayToXML($value['value']) : htmlspecialchars($value['value'], ENT_NOQUOTES, 'UTF-8')) .'</'. $value['element'] . ">\n";
					}
                    else
				    {
                        $outputString .= " />\n";
					}
                }
			}
			else
			{
				$outputString .= '<'. $element .'>'. (is_array($value) ?  $this->docArrayToXML($value) : htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8')) ."</$element>\n";
			}
		}
		return $outputString;
	}

	function docToXML()
	{
        return $this->docArrayToXML( array( $this->Doc ) );
	}

    var $Doc;

}


?>
<?php
//
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

/*! \file ezfsearchresultinfo.php
*/

/**
 * ezfSearchResultInfo contains additional search result information.
 * This information include facet information.
 */
class ezfSearchResultInfo
{
    /**
     * Constructor
     *
     * @param array Search result information list.
     *        This list contains facet information, errors and
     *        engine information.
     *        Example parameter.
     * <code>
     * $searchResultInfo = new ezfSearchResultInfo(
     *    array( 'facet' => array( 'facet_fields' => array( 'facet_fields' => array( .... ) ) ),
     *           'Engine' => 'engine name',
     *           ... )
     */
    function ezfSearchResultInfo( array $resultArray )
    {
        $this->ResultArray = $resultArray;
    }

    /**
     * Attribute name list.
     *
     * @return array List or attribute names.
     */
    public function attributes()
    {
        return array( 'facet',
                      'engine',
                      'hasError',
                      'error',
                      'responseHeader' );
    }

    /**
     * Get attribute value
     *
     * @param string Attribute name
     *
     * @return mixed Attribute value. null if attribute does not exist.
     */
    public function attribute( $attr )
    {
        switch( $attr )
        {
            case 'responseHeader':
            {
                return $this->ResultArray['responseHeader'];
            } break;

            case 'hasError':
            {
                return !empty( $this->ResultArray['error'] );
            } break;

            case 'error':
            {
                if ( !empty( $this->ResultArray['error'] ) )
                {
                    return $this->ResultArray['error'];
                }
            };

            case 'facet':
            {
                if ( !empty( $this->Facet ) )
                {
                    return $this->Facet;
                }

                // If the facets count is empty, an error has occured.
                if ( empty( $this->ResultArray['facet_counts'] ) )
                {
                    return null;
                }

                $facetArray = array();
                $mainFacet = null;
                foreach( $this->ResultArray['facet_counts']['facet_fields'] as $field => $facetField )
                {
                    switch( $field )
                    {
                        // class facet field
                        case eZSolr::getMetaFieldName( 'contentclass_id' ):
                        {
                            $fieldInfo = array( 'field' => 'class',
                                                'count' => count( $facetField ),
                                                'nameList' => array(),
                                                'queryLimit' => array(),
                                                'countList' => array() );
                            foreach( $facetField as $contentClassID => $count )
                            {
                                if ( $contentClass = eZContentClass::fetch( $contentClassID ) )
                                {
                                    $fieldInfo['nameList'][$contentClassID] = $contentClass->attribute( 'name' );
                                    $fieldInfo['queryLimit'][$contentClassID] = 'contentclass_id:' . $contentClassID;
                                    $fieldInfo['countList'][$contentClassID] = $count;
                                }
                                else
                                {
                                    eZDebug::writeWarning( 'Could not fetch eZContentClass: ' . $contentClassID,
                                                           'ezfSearchResultInfo::attribute()' );
                                }
                            }
                            $facetArray[] = $fieldInfo;
                        } break;

                        // author facet field
                        case eZSolr::getMetaFieldName( 'owner_id' ):
                        {
                            $fieldInfo = array( 'field' => 'author',
                                                'count' => count( $facetField ),
                                                'nameList' => array(),
                                                'queryLimit' => array(),
                                                'countList' => array() );
                            foreach( $facetField as $ownerID => $count )
                            {
                                if ( $owner = eZContentObject::fetch( $ownerID ) )
                                {
                                    $fieldInfo['nameList'][$ownerID] = $owner->attribute( 'name' );
                                    $fieldInfo['queryLimit'][$ownerID] = 'owner_id:' . $ownerID;
                                    $fieldInfo['countList'][$ownerID] = $count;
                                }
                                else
                                {
                                    eZDebug::writeWarning( 'Could not fetch owner ( eZContentObject ): ' . $ownerID,
                                                           'ezfSearchResultInfo::attribute()' );
                                }
                            }
                            $facetArray[] = $fieldInfo;
                        } break;

                        // translation facet field
                        case eZSolr::getMetaFieldName( 'language_code' ):
                        {
                            $fieldInfo = array( 'field' => 'translation',
                                                'count' => count( $facetField ),
                                                'nameList' => array(),
                                                'queryLimit' => array(),
                                                'countList' => array() );
                            foreach( $facetField as $languageCode => $count )
                            {
                                $fieldInfo['nameList'][$languageCode] = $languageCode;
                                $fieldInfo['queryLimit'][$languageCode] = 'language_code:' . $languageCode;
                                $fieldInfo['countList'][$languageCode] = $count;
                            }
                            $facetArray[] = $fieldInfo;
                        } break;

                        default:
                        {
                            $fieldInfo = array( 'field' => $attr,
                                                'count' => count( $facetField ),
                                                'queryLimit' => array(),
                                                'nameList' => array(),
                                                'countList' => array() );
                            foreach( $facetField as $value => $count )
                            {
                                $fieldInfo['nameList'][$value] = $value;
                                $fieldInfo['queryLimit'][$value] = $field . ':' . $value;
                                $fieldInfo['countList'][$value] = $count;
                            }
                            $facetArray[] = $fieldInfo;
                        } break;
                    }
                    if ( !$mainFacet && $facetArray )
                    {
                        $mainFacet = $facetArray[0];
                    }
                }

                $this->Facet = $facetArray;
                $this->Facet['main'] = $mainFacet;
                return $this->Facet;
            } break;

            case 'engine':
            {
                return eZSolr::engineText();
            } break;

            default:
            {
            } break;
        }
        return null;
    }

    /**
     * Check if attribute name exists.
     *
     * @param string Attribute name
     *
     * @return boolean True if attribute name exists. False if not.
     */
    public function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }


    /// Member vars
    protected $Facet;
    protected $ResultArray;
}

?>

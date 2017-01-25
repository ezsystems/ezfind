<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
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
        return array( 'facet_fields',
                      'facet_queries',
                      'facet_dates',
                      'facet_ranges',
                      'engine',
                      'hasError',
                      'error',
                      'responseHeader',
                      'spellcheck',
                      // spellcheck_collation is part of spellcheck suggestions,
                      // but a border case is present when "collation"
                      // is also a word searched for and not present in the
                      // spellcheck dictionary/index -- Solr php response writer "bug"
                      'spellcheck_collation',
                      'interestingTerms',
                      'clusters',
                      'highlighting'
            );
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
            } break;

            case 'facet_queries':
            {
                if ( !empty( $this->FacetQueries ) )
                {
                    return $this->FacetQueries;
                }

                // If the facets count is empty, an error has occured.
                if ( empty( $this->ResultArray['facet_counts'] ) )
                {
                    return null;
                }

                $facetArray = array();
                foreach ( $this->ResultArray['facet_counts']['facet_queries'] as $query => $count )
                {
                    list( $field, $fieldValue ) = explode( ':', $query );
                    $fieldInfo = array( 'field' => $field,
                                        'count' => $count,
                                        'queryLimit' => $query,
                                        'fieldValue' => $fieldValue );
                    $facetArray[] = $fieldInfo;
                }

                $this->FacetQueries = $facetArray;
                return $this->FacetQueries;
            } break;

            case 'facet_fields':
            {
                if ( !empty( $this->FacetFields ) )
                {
                    return $this->FacetFields;
                }

                // If the facets count is empty, an error has occured.
                if ( empty( $this->ResultArray['facet_counts'] ) )
                {
                    return null;
                }

                $facetArray = array();
                foreach ( $this->ResultArray['facet_counts']['facet_fields'] as $field => $facetField )
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
                                                'fieldList' => array(),
                                                'countList' => array() );
                            foreach ( $facetField as $contentClassID => $count )
                            {
                                if ( $contentClass = eZContentClass::fetch( $contentClassID ) )
                                {
                                    $fieldInfo['nameList'][$contentClassID] = $contentClass->attribute( 'name' );
                                    $fieldInfo['queryLimit'][$contentClassID] = 'contentclass_id:' . $contentClassID;
                                    $fieldInfo['countList'][$contentClassID] = $count;
                                    $fieldInfo['fieldList'][$contentClassID] = 'contentclass_id';
                                }
                                else
                                {
                                    eZDebug::writeWarning( 'Could not fetch eZContentClass: ' . $contentClassID, __METHOD__ );
                                }
                            }
                            $facetArray[] = $fieldInfo;
                        } break;

                        // instalaltion facet field
                        case eZSolr::getMetaFieldName( 'installation_id' ):
                        {
                            $findINI = eZINI::instance( 'ezfind.ini' );
                            $siteNameMapList = $findINI->variable( 'FacetSettings', 'SiteNameList' );
                            $fieldInfo = array( 'field' => 'installation',
                                                'count' => count( $facetField ),
                                                'nameList' => array(),
                                                'queryLimit' => array(),
                                                'fieldList' => array(),
                                                'countList' => array() );
                            foreach ( $facetField as $installationID => $count )
                            {
                                $fieldInfo['nameList'][$installationID] = isset( $siteNameMapList[$installationID] ) ?
                                    $siteNameMapList[$installationID] : $installationID;
                                $fieldInfo['queryLimit'][$installationID] = 'installation_id:' . $installationID;
                                $fieldInfo['countList'][$installationID] = $count;
                                $fieldInfo['fieldList'][$installationID] = 'installation_id';
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
                                                'fieldList' => array(),
                                                'countList' => array() );
                            foreach ( $facetField as $ownerID => $count )
                            {
                                if ( $owner = eZContentObject::fetch( $ownerID ) )
                                {
                                    $fieldInfo['nameList'][$ownerID] = $owner->attribute( 'name' );
                                    $fieldInfo['queryLimit'][$ownerID] = 'owner_id:' . $ownerID;
                                    $fieldInfo['countList'][$ownerID] = $count;
                                    $fieldInfo['fieldList'][$ownerID] = 'owner_id';
                                }
                                else
                                {
                                    eZDebug::writeWarning( 'Could not fetch owner ( eZContentObject ): ' . $ownerID, __METHOD__ );
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
                                                'fieldList' => array(),
                                                'countList' => array() );
                            foreach ( $facetField as $languageCode => $count )
                            {
                                $fieldInfo['nameList'][$languageCode] = $languageCode;
                                $fieldInfo['queryLimit'][$languageCode] = 'language_code:' . $languageCode;
                                $fieldInfo['fieldList'][$languageCode] = 'language_code';
                                $fieldInfo['countList'][$languageCode] = $count;
                            }
                            $facetArray[] = $fieldInfo;
                        } break;

                        default:
                        {
                            $fieldInfo = array( 'field' => $attr,
                                                'count' => count( $facetField ),
                                                'queryLimit' => array(),
                                                'fieldList' => array(),
                                                'nameList' => array(),
                                                'countList' => array() );
                            foreach ( $facetField as $value => $count )
                            {
                                $fieldInfo['nameList'][$value] = $value;
                                $fieldInfo['fieldList'][$value] = $field;
                                $fieldInfo['queryLimit'][$value] = $field . ':' . $value;
                                $fieldInfo['countList'][$value] = $count;
                            }
                            $facetArray[] = $fieldInfo;
                        } break;
                    }
                }

                $this->FacetFields = $facetArray;
                return $this->FacetFields;
            } break;

            case 'engine':
            {
                return eZSolr::engineText();
            } break;


            //may or may not be active, so returns false if not present
            case 'spellcheck':
            {
                if ( isset( $this->ResultArray['spellcheck'] ) && $this->ResultArray['spellcheck']['suggestions'] > 0 )
                {
                    return $this->ResultArray['spellcheck']['suggestions'];
                }
                else
                {
                    return false;
                }
            } break;

            case 'spellcheck_collation':
            {
                if ( isset( $this->ResultArray['spellcheck']['suggestions']['collation'] ) )
                {
                    // work around border case if 'collation' is searched for but does not exist in the spell check index
                    // the collation string is the last element of the suggestions array
                    return end( $this->ResultArray['spellcheck']['suggestions'] );

                }
                else
                {
                    return false;
                }
            } break;

            //only relevant for MoreLikeThis queries
            case 'interestingTerms':
            {
                if ( isset( $this->ResultArray['interestingTerms'] ) )
                {
                    return $this->ResultArray['interestingTerms'];
                }
                else
                {
                    return false;
                }
            } break;
            case 'facet_dates':
            {
                if ( isset( $this->ResultArray['facet_dates'] ) )
                {
                    return $this->ResultArray['facet_dates'];
                }
                else
                {
                    return false;
                }
            } break;

            case 'facet_ranges':
            {
                if ( isset( $this->ResultArray['facet_counts']['facet_ranges'] ) )
                {
                    return $this->ResultArray['facet_counts']['facet_ranges'];
                }
                else
                {
                    return false;
                }
            } break;
            case 'clusters':
            {
                if ( isset( $this->ResultArray['clusters'] ) )
                {
                    return $this->ResultArray['clusters'];
                }
                else
                {
                    return false;
                }
            } break;
            case 'highlighting':
            {
                if ( isset( $this->ResultArray['highlighting'] ) )
                {
                    return $this->ResultArray['highlighting'];
                }
                else
                {
                    return false;
                }
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
    protected $FacetFields;
    protected $FacetQueries;
    protected $ResultArray;
}

?>

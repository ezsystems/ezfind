<?php
/**
 * Helper class used to test ezfeZPSolrQueryBuilder
 **/
class ezfeZPSolrQueryBuilderTester extends ezfeZPSolrQueryBuilder
{
    public function __construct()
    {
        parent::__construct( new eZSolr() );
    }

    /**
     * Simple wrapper that makes getClassAttributes public
     * @see ezfeZPSolrQueryBuilder::getClassAttributes()
     **/
    public function getClassAttributes( $classIDArray = false,
        $classAttributeIDArray = false,
        $fieldTypeExcludeList = null )
    {
        return parent::getClassAttributes( $classIDArray, $classAttributeIDArray, $fieldTypeExcludeList );
    }

    /**
     * Simple wrapper that makes fieldTypeExcludeList public
     * @see ezfeZPSolrQueryBuilder::fieldTypeExcludeList
     **/
    public function fieldTypeExludeList( $searchText )
    {
        return parent::fieldTypeExludeList( $searchText );
    }

    /**
    * Simple wrapper that makes buildLanguageFilterQuery public
    **/
    public function buildLanguageFilterQuery()
    {
        return parent::buildLanguageFilterQuery();
    }

    /**
    * Simple wrapper that makes buildSortParameter public
    **/
    public function buildSortParameter( $parameterList )
    {
        return parent::buildSortParameter( $parameterList );
    }
}
?>

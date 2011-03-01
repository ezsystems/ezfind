<?php
/**
 * Helper class used to test ezfSolrDocumentFieldName
 **/
class ezfSolrDocumentFieldNameTester extends ezfSolrDocumentFieldName
{
    /**
     * Simple wrapper that makes loadLookupTable public
     * @see ezfSolrDocumentFieldName::loadLookupTable()
     **/
    public function loadLookupTable()
    {
        return parent::loadLookupTable();
    }

    public function getFieldTypeMap()
    {
        return parent::$FieldTypeMap;
    }
}
?>

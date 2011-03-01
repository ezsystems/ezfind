<?php
/**
 * Helper class used to test ezfSolrDocumentFieldObjectRelation
 **/
class ezfSolrDocumentFieldObjectRelationTester extends ezfSolrDocumentFieldObjectRelation
{
    public function getPlainTextRepresentation( eZContentObjectAttribute $contentObjectAttribute = null )
    {
        return parent::getPlainTextRepresentation( $contentObjectAttribute );
    }

    public function __call( $name, $args )
    {
        return call_user_func_array( array( $this, $name ), $args );
    }
}
?>

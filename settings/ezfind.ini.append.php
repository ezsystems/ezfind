<?php /*

[SiteSettings]
# Prepend site URL with http:// or https://
URLProtocol=http://
# Site index public available. For changes to take effect, the search
# index must be updated by running bin/php/updatesearchindex.php
IndexPubliclyAvailable=enabled
# Search other installations
SearchOtherInstallations=enabled

[SolrFieldMapSettings]
# List of custom datatype mapping. eZ Publish datatype string is used
# as key, and the value if the name of the class to use.
#
# Example:
# CustomMap[eztext]=ezfSolrDocumentFieldText
CustomMap[ezobjectrelation]=ezfSolrDocumentFieldObjectRelation
CustomMap[ezobjectrelationlist]=ezfSolrDocumentFieldObjectRelation

# Datatype to field type map.
#
# Example:
# DatatypeMap[eztext]=text
DatatypeMap[ezboolean]=boolean
DatatypeMap[ezdate]=date
DatatypeMap[ezdatetime]=date
DatatypeMap[ezfloat]=sfloat
DatatypeMap[ezinteger]=sint
DatatypeMap[ezprice]=sfloat
DatatypeMap[eztime]=date

# Default field type
Default=text


*/ ?>

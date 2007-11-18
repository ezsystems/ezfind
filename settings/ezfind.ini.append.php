<?php /*

[SiteSettings]
# Prepend site URL with http:// or https://
URLProtocol=http://
# Site index public available. For changes to take effect, the search
# index must be updated by running bin/php/updatesearchindex.php
IndexPubliclyAvailable=enabled
# Search other installations
SearchOtherInstallations=enabled

[FacetSettings]
# Installation name map. The key is the installation ID,
# and the value is the name displayed in the design templates.
#
# Use this SQL to get the value : SELECT value FROM ezsite_data WHERE name='ezfind_site_id';
# Example:
# SiteNameList[]
# SiteNameList[3e731797af0a6b79e943eefaf437f956]=eZ.no
SiteNameList[]

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

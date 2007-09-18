<?php /*

[SolrBase]
# Base URI of the Solr server
SearchServerURI=http://localhost:8983/solr
# Realm is used to differentiate between "multiple indexes"
# You can have several of them combined into the same index
# To set up physically separated indexes, you should run multiple instances of Solr
# See the Solr wiki on how to do this (Jetty, Tomcat)
Realm=nfpro

[HighLight]
HighLight=yes
SnippetLength
# If Fields is left empty, all seachable atributes are used
Fields[]=

[Facets]
FacetAttributes[]=keywords
FacetMeta[]=class_name
FacetMeta[]=published
FacetMeta[]=modified
FacetMeta[]=owner_name
FacetMeta[]=author_name

*/ ?>
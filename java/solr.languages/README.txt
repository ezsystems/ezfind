Changes in multi-language/multicore support in eZ Find 5.3
==========================================================

The monolithic schema.xml has been split up in several parts, which are
re-assembled upon startup of Solr or a core (stand alone or master/slave)
reload or collection (SolrCloud) reload.

The parts are now (file names are mandatory):

- schema.xml (contains the main, but language independent parts of the schema)
- language-specific-fieldtypes.xml (contains language specific field types
  for at least the 'text' type, but can have other custom types as well)
- custom-fields.xml
- custom-copyfields.xml

In this folder, several sub-folders are listed that contains for every language
a customised 'language-specific-fieldtypes.xml' that you can use for your core
definitions. There are also additional stopwords.txt and synonyms

In order to create a new core, just copy the ezp-default folder contents from
ezfind/java/solr/ezp-default and replace the file
'language-specific-fieldtypes.xml' with the corresponding language version




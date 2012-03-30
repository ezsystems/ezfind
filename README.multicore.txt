Important notice regarding the solr.multicore/lib directory
===========================================================


Since eZ Find 2.7, a larger amount plugin libraries are shipped.
In order to avoid duplication of the larger size solr/lib in the solr.multicore/lib
directory, the solr.multicore/solr.xml config file is adapted to point to
../solr/lib. When copying the solr.multicore directory to other locations,
make sure to adapt solr.xml and copy the contents of solr/lib to the lib
directory of the new multicore home.

solr.xml should then look like below (like it was for previous eZ Find releases)


<?xml version="1.0" encoding="UTF-8" ?>
<solr persistent="true" sharedLib="lib">
  <cores adminPath="/admin/cores">
    <core name="eng-GB" instanceDir="eng-GB" />
    <!-- Add more cores here -->

  </cores>
</solr>
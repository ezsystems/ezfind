Installing eZFind
=================

Requirements:
-------------
- eZ Publish 4.x
- Java Runtime Environment 5.0 or later. (http://java.sun.com/javase/downloads/index.jsp)
- PHP Curl extension (http://php.net/manual/en/ref.curl.php)

Installing:
-----------
1. Extract the ezfind extension and place it in the extensions folder.
2. Run it:
   1. For small sites: use a regular java 1.5 JRE from Sun
      Start the Java based search engine (based on Solr).
      Enter `extension/ezfind/java` and start the Solr engine with the command:

      ```bash
      $ java -Dezfind -jar start.jar
      ```
      (See configuration for more details)
      Make sure that the user running the java application has write access to
      `extension/ezfind/java/solr/data` and `extension/ezfind/java/logs` .
   2. For high performance, larger sites, use a 64 bit OS and 64 bit Java VM from Sun (1.5 recommended)
      you need to use some extra parameters for java too, the following is recommended for typical sites:

      ```bash
      $ java -server -d64 -Xmx1500m -Xms1500m -XX:+UseParallelGC -XX:+AggressiveOpts -XX:NewRatio=5 -jar start.jar
      ```
      This will: make sure the Java VM is started with 64 bit mode,
      allocate a heap space of 1500MB which is used for Solr caches and objects,
      the rest of the options are related to garbage collection
3. Enable the extension in eZ Publish. Do this by opening settings/override/site.ini.append.php ,
   and add in the `[ExtensionSettings]` block:

   ```ini
   ActiveExtensions[]=ezfind
   ```
   To get the correct templates for ezwebin, and for all ezfind features to be available,
   ezfind must be enabled before the ezwebin and ezflow extensions. The final result must follow the following principle:

   ```ini
   [ExtensionSettings]
   ActiveExtensions[]=ezfind
   ActiveExtensions[]=ezwebin
   ActiveExtensions[]=ezflow
   ```
4. Update the class autoloads by running the script:

   ```bash
   $ php bin/php/ezpgenerateautoloads.php
   ```
5. Add a table in the database used by your eZ Publish instance. You can do so as follows
   (from eZ Publish's root directory), in the case you are using MySQL:

   ```bash
   $ mysql -u <user> -p <database_name> < extension/ezfind/sql/mysql/schema.sql
   ```
   The procedure is very similar in case you are using another RDMS. You may want to have a look at the
   sql/oracle and sql/postgresql for Oracle and PostgreSQL databases respectively.
   Please refer to the documentation reference for your DBMS if you are experiencing issues.
6. Clear template override cache with the command:

   ```bash
   $ php bin/php/ezcache.php --clear-id=template-override
   ```
7. Re-index the site content by running:

   ```bash
   $ php extension/ezfind/bin/php/updatesearchindexsolr.php -s <admin siteaccess>
   ```

Configuration:
--------------

* Configuring eZ Publish:
  See `extension/ezfind/settings/ezfind.ini.append.php` for options.
* Configuring Solr (Java based search engine):
  One instance of Solr is able to serve multiple eZ Publish installations.
  For shared servers, it's recommended to only run one instance Solr.

  Index options:
  Solr will store the search index according to the directive in the configuration file
  `extension/ezfind/java/solr/conf/solrconfig.xml`. Change the `config->dataDir`
  setting to alter the index path for your intallation. Make sure the permissions are
  correctly set

  Java options:
  The `-Dezfind` option is to make it easier to identify the eZ Find java process. The `-Dezfind` option
  will be visible in the process listing on unix systems.

Running as service:
-------------------
It is possible to run eZ Find as a service in Linux. The scripts to do this are located in
`extension/ezfind/bin/scripts/<dist>`. The supported distributions are Debian and RHEL (including CentOS, Fedora,
Mandriva and other RedHat based distributions)
Open the script for your distribution to see installations instructions.

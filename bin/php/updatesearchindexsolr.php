#!/usr/bin/env php
<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//
//

require 'autoload.php';

if ( !function_exists( 'readline' ) )
{
    function readline( $prompt = '' )
    {
        echo $prompt . ' ';
        return trim( fgets( STDIN ) );
    }
}

function microtime_float()
{
    return microtime( true );
}

set_time_limit( 0 );

$cli = eZCLI::instance();

$script = eZScript::instance(
    array(
        'description' =>
            "eZFind search index updater.\n\n" .
            "Goes trough all objects and reindexes the meta data to the search engine" .
            "\n" .
            "updatesearchindexsolr.php",
        'use-session' => true,
        'use-modules' => true,
        'use-extensions' => true
    )
);


$solrUpdate = new ezfUpdateSearchIndexSolr( $script, $cli );
$solrUpdate->run();

$script->shutdown( 0 );

/**
 * Class containing controlling functions for updating the search index.
 */
class ezfUpdateSearchIndexSolr
{
    /**
     * Constructor
     *
     * @param eZScript $script
     * @param eZCLI $cli
     */
    function ezfUpdateSearchIndexSolr( eZScript $script, eZCLI $cli )
    {
        $this->Script = $script;
        $this->CLI = $cli;
        $this->Options = null;
    }

    /**
     * Startup and run script.
     */
    public function run()
    {
        $this->Script->startup();

        $this->Options = $this->Script->getOptions(
            "[db-host:][db-user:][db-password:][db-database:][db-type:|db-driver:][sql][clean][clean-all][conc:][offset:][limit:][topNodeID:][php-exec:]",
            "",
            array(
                'db-host' => "Database host",
                'db-user' => "Database user",
                'db-password' => "Database password",
                'db-database' => "Database name",
                'db-driver' => "Database driver",
                'db-type' => "Database driver, alias for --db-driver",
                'sql' => "Display sql queries",
                'clean' =>  "Remove all search data of the current installation id before beginning indexing",
                'clean-all' => "Remove all search data for all installations",
                'conc' => 'Parallelization, number of concurent processes to use',
                'php-exec' => 'Full path to PHP executable',
                'offset' => '*For internal use only*',
                'limit' => '*For internal use only*',
                'topNodeID' => '*For internal use only*',
            )
        );
        $this->Script->initialize();

        // Fix siteaccess
        $siteAccess = $this->Options['siteaccess'] ? $this->Options['siteaccess'] : false;
        if ( $siteAccess )
        {
            $this->changeSiteAccessSetting( $siteAccess );
        }
        else
        {
            $this->CLI->warning( 'You did not specify a siteaccess. The admin siteaccess is a required option in most cases.' );
            $input = readline( 'Are you sure the default siteaccess has all available languages defined? ([y] or [q] to quit )' );
            if ( $input === 'q' )
            {
                $this->Script->shutdown( 0 );
            }
        }

        // Check that Solr server is up and running
        if ( !$this->checkSolrRunning() )
        {
            $this->Script->shutdown( 1 );
            exit();
        }

        $this->initializeDB();

        // call clean up routines which will deal with the CLI arguments themselves
        $this->cleanUp();
        $this->cleanUpAll();

        // Check if current instance is main or sub process.
        // Main process can not have offset or limit set.
        // sub process MUST have offset and limit set.
        $offset = $this->Options['offset'];
        $limit = $this->Options['limit'];
        $topNodeID = $this->Options['topNodeID'];
        if ( !is_numeric( $offset ) &&
             !is_numeric( $limit ) &&
             !is_numeric( $topNodeID ) )
        {
            $this->CLI->output( 'Starting object re-indexing' );

            // Get PHP executable from user.
            $this->getPHPExecutable();

            $this->runMain();
        }
        elseif ( is_numeric( $offset ) &&
                 is_numeric( $limit ) &&
                 is_numeric( $topNodeID ) )
        {
            $this->runSubProcess( $topNodeID, $offset, $limit );
        }
        else
        {
            //OBS !!, invalid.
            $this->CLI->error( 'Invalid parameters provided.' );
            $this->Script->shutdown( 2 );
        }
    }

    /**
     * Run sub process.
     *
     * @param int $nodeID
     * @param int $offset
     * @param int $limit
     */
    protected function runSubProcess( $nodeID, $offset, $limit )
    {
        $count = 0;
        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            $this->CLI->error( "An error occured while trying fetching node $nodeID" );
            $this->Script->shutdown( 3 );
        }
        $searchEngine = new eZSolr();

        if (
            $subTree = $node->subTree(
                array(
                    'Offset' => $offset,
                    'Limit' => $limit,
                    'SortBy' => array(),
                    'Limitation' => array(),
                    'MainNodeOnly' => true
                )
            )
        )
        {
            foreach ( $subTree as $innerNode )
            {
                $object = $innerNode->attribute( 'object' );
                if ( !$object )
                {
                    continue;
                }

                //eZSearch::removeObject( $object );
                //pass false as we are going to do a commit at the end
                $result = $searchEngine->addObject( $object, false );
                if ( !$result )
                {
                    $this->CLI->error( ' Failed indexing ' . $object->attribute('class_identifier') .  ' object with ID ' . $object->attribute( 'id' ) );
                }
                ++$count;
            }
        }

        $this->CLI->output( $count );
        $this->Script->shutdown( 0 );
    }

    /**
     * Get PHP executable from user input. Exit if php executable cannot be
     * found and if no executable is entered.
     */
    protected function getPHPExecutable()
    {
        $validExecutable = false;
        $output = array();
        $exec = 'php';
        if ( !empty( $this->Options['php-exec'] ) )
        {
            $exec = $this->Options['php-exec'];
        }
        exec( $exec . ' -v', $output );

        if ( count( $output ) && strpos( $output[0], 'PHP' ) !== false )
        {
            $validExecutable = true;
            $this->Executable = $exec;
        }

        while( !$validExecutable )
        {
            $input = readline( 'Enter path to PHP-CLI executable ( or [q] to quit )' );
            if ( $input === 'q' )
            {
                $this->Script->shutdown( 0 );
            }

            exec( $input . ' -v', $output );

            if ( count( $output ) && strpos( $output[0], 'PHP' ) !== false )
            {
                $validExecutable = true;
                $this->Executable = $input;
            }
        }
    }

    /**
     * Run main process
     */
    protected function runMain()
    {
        $startTS = microtime_float();

        $searchEngine = new eZSolr();

        $processLimit = min( $this->Options['conc'] ? $this->Options['conc'] : 2,
                             10 ); // Maximum 10 processes
        $useFork = ( function_exists( 'pcntl_fork' ) &&
                     function_exists( 'posix_kill' ) );
        if ( $useFork )
        {
            $this->CLI->output( 'Using fork.' );
        }
        else
        {
            $processLimit = 1;
        }

        $this->CLI->output( 'Using ' . $processLimit . ' concurent process(es)' );

        $processList = array();
        for ( $i = 0; $i < $processLimit; $i++ )
        {
            $processList[$i] = -1;
        }

        $this->ObjectCount = $this->objectCount();
        $this->CLI->output( 'Number of objects to index: ' . $this->ObjectCount );
        $this->Script->resetIteration( $this->ObjectCount, 0 );

        $topNodeArray = eZPersistentObject::fetchObjectList(
            eZContentObjectTreeNode::definition(),
            null,
            array( 'parent_node_id' => 1, 'depth' => 1 )
        );
        // Loop through top nodes.
        foreach ( $topNodeArray as $node )
        {
            $nodeID = $node->attribute( 'node_id' );
            $offset = 0;

            $subtreeCount = $node->subTreeCount( array( 'Limitation' => array(), 'MainNodeOnly' => true ) );
            // While $offset < subtree count, loop through the nodes.
            while( $offset < $subtreeCount )
            {
                // Loop trough the available processes, and see if any has finished.
                for ( $i = 0; $i < $processLimit; $i++ )
                {
                    $pid = $processList[$i];
                    if ( $useFork )
                    {
                        if ( $pid === -1 || !posix_kill( $pid, 0 ) )
                        {
                            $newPid = $this->forkAndExecute( $nodeID, $offset, $this->Limit );
                            $this->CLI->output( "\n" . 'Creating a new thread: ' . $newPid );
                            if ( $newPid > 0 )
                            {
                                $offset += $this->Limit;
                                $this->iterate();
                                $processList[$i] = $newPid;
                            }
                            else
                            {
                                $this->CLI->error( "\n" . 'Returned invalid PID: ' . $newPid );
                            }
                        }
                    }
                    else
                    {
                        // Execute in same process
                        $count = $this->execute( $nodeID, $offset, $this->Limit );
                        $this->iterate( $count );
                        $offset += $this->Limit;
                    }

                    if ( $offset >= $subtreeCount )
                    {
                        break;
                    }
                }

                // If using fork, the main process must sleep a bit to avoid
                // 100% cpu usage. Sleep for 100 millisec.
                if ( $useFork )
                {
                    $status = 0;
                    pcntl_wait( $status, WNOHANG );
                    usleep( 100000 );
                }
            }
        }

        // Wait for all processes to finish.
        $break = false;
        while ( $useFork && !$break )
        {
            $break = true;
            for ( $i = 0; $i < $processLimit; $i++ )
            {
                $pid = $processList[$i];
                if ( $pid !== -1 )
                {
                    // Check if process is still alive.
                    if ( posix_kill( $pid, 0 ) )
                    {
                        $break = false;
                    }
                    else
                    {
                        $this->CLI->output( 'Process finished: ' . $pid );
                        $processList[$i] = -1;
                    }
                }
            }
            // Sleep for 500 msec.
            $status = 0;
            pcntl_wait( $status, WNOHANG );

            usleep( 500000 );
        }

        $this->CLI->output( 'Optimizing. Please wait ...' );
        $searchEngine->optimize( true );
        $endTS = microtime_float();

        $this->CLI->output(
            'Indexing took ' . ( $endTS - $startTS ) . ' secs ' .
            '( average: ' . ( $this->ObjectCount / ( $endTS - $startTS ) ) . ' objects/sec )'
        );

        $this->CLI->output( 'Finished updating the search index.' );
    }

    /**
     * Iterate index counter
     *
     * @param int $count
     */
    protected function iterate( $count = false )
    {
        if ( !$count )
        {
            $count = $this->Limit;
        }

        for ( $iterateCount = 0; $iterateCount < $count; ++$iterateCount )
        {
            if ( ++$this->IterateCount > $this->ObjectCount )
            {
                break;
            }
            $this->Script->iterate( $this->CLI, true );

            if ( $this->IterateCount % 1000 === 0 )
            {
                $this->CLI->output( "\n" . 'Comitting and optimizing index ...' );
                $searchEngine = new eZSolr();
                $searchEngine->optimize();
                eZContentObject::clearCache();
            }
        }
    }

    /**
     * Fork and execute
     *
     * @param int $nodeid
     * @param int $offset
     * @param int $limit
     */
    protected function forkAndExecute( $nodeID, $offset, $limit )
    {
        $pid = pcntl_fork();
        $db = eZDB::instance();
        $db->IsConnected = false;
        $db = null;
        eZDB::setInstance( $db );
        $this->initializeDB();
        if ( $pid == -1 )
        {
            die( 'could not fork' );
        }
        else if ( $pid )
        {
            // Main process
            return $pid;
        }
        else
        {
            // We are the child process
            $this->execute( $nodeID, $offset, $limit, true );
            $this->Script->shutdown( 0 );
        }
    }

    /**
     * Execute indexing of subtree
     *
     * @param int $nodeID
     * @param int $offset
     * @param int $limit
     * @param bool $isSubProcess.
     *
     * @return int Number of objects indexed.
     */
    protected function execute( $nodeID, $offset, $limit, $isSubProcess = false )
    {
        global $argv;
        // Create options string.
        $paramString = '';
        $paramList = array( 'db-host', 'db-user', 'db-password', 'db-type', 'db-driver', 'db-database' );
        foreach ( $paramList as $param )
        {
            if ( !empty( $this->Options[$param] ) )
            {
                $paramString .= ' --' . $param . '=' . escapeshellarg( $this->Options[$param] );
            }
        }

        if ( $this->Options['siteaccess'] )
        {
            $paramString .= ' -s ' . escapeshellarg( $this->Options['siteaccess'] );
        }

        $paramString .=
            ' --limit=' . $limit .
            ' --offset=' . $offset .
            ' --topNodeID=' . $nodeID;

        $output = array();
        $command = $this->Executable . ' ' . $argv[0] . $paramString;
        exec( $command, $output );
//        wtf code follows, but leave it here commented for future enhancements
//        if ( $isSubProcess )
//        {
//            exec( $command, $output );
//        }
//        else
//        {
//            exec( $command, $output );
//        }

        if ( !empty( $output ) )
        {
            $num = array_pop( $output );
            if ( is_numeric( $num ) )
            {
                return $num;
            }
        }

        $this->CLI->error( "\n" . 'Did not index content correctly: ' . "\n" . var_export( $output, 1 ) );

        return 0;
    }

    /**
     * Get total number of objects
     *
     * @return int Total object count
     */
    protected function objectCount()
    {
        $topNodeArray = eZPersistentObject::fetchObjectList(
            eZContentObjectTreeNode::definition(),
            null,
            array( 'parent_node_id' => 1, 'depth' => 1 )
        );
        $subTreeCount = 0;
        foreach ( array_keys ( $topNodeArray ) as $key  )
        {
            $subTreeCount += $topNodeArray[$key]->subTreeCount( array( 'Limitation' => array(), 'MainNodeOnly' => true ) );
        }

        return $subTreeCount;
    }

    /**
     * Cleanup search index.
     * Only clean up if --clean option is set.
     */
    protected function cleanUp()
    {
        if ( $this->Options['clean'] )
        {
            $this->CLI->output( "eZSearchEngine: Cleaning up search data for current installation id" );
            $searchEngine = new eZSolr();
            $allInstallations = false;
            $optimize = false;
            $searchEngine->cleanup( $allInstallations, $optimize );
        }
    }

    /**
     * Clean all indices in current Solr core, regardless of installation id's
     * Only clean-up if --clean-all is set
     */
    protected function cleanUpAll()
    {
        if ( $this->Options['clean-all'] )
        {
            $this->CLI->output( "eZSearchEngine: Cleaning up search data for all installations" );
            $searchEngine = new eZSolr();
            // The essence of teh All suffix
            $allInstallations = true;
            // Optimize: sets all indexes to minimal file size too
            $optimize = true;
            $searchEngine->cleanup( $allInstallations, $optimize );
        }
    }

    /**
     * Create custom DB connection if DB options provided
     */
    protected function initializeDB()
    {
        $dbUser = $this->Options['db-user'] ? $this->Options['db-user'] : false;
        $dbPassword = $this->Options['db-password'] ? $this->Options['db-password'] : false;
        $dbHost = $this->Options['db-host'] ? $this->Options['db-host'] : false;
        $dbName = $this->Options['db-database'] ? $this->Options['db-database'] : false;
        $dbImpl = $this->Options['db-driver'] ? $this->Options['db-driver'] : false;
        $showSQL = $this->Options['sql'] ? true : false;

        $db = eZDB::instance();

        if ( $dbHost or $dbName or $dbUser or $dbImpl )
        {
            $params = array();
            if ( $dbHost !== false )
                $params['server'] = $dbHost;
            if ( $dbUser !== false )
            {
                $params['user'] = $dbUser;
                $params['password'] = '';
            }
            if ( $dbPassword !== false )
                $params['password'] = $dbPassword;
            if ( $dbName !== false )
                $params['database'] = $dbName;
            $db = eZDB::instance( $dbImpl, $params, true );
            eZDB::setInstance( $db );
        }

        $db->setIsSQLOutputEnabled( $showSQL );
    }


    /**
     * Change siteaccess
     *
     * @param string $siteaccess
     */
    protected function changeSiteAccessSetting( $siteaccess )
    {
        global $isQuiet;
        $cli = eZCLI::instance();
        if ( !in_array( $siteaccess, eZINI::instance()->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' ) ) )
        {
            if ( !$isQuiet )
                $cli->notice( "Siteaccess $siteaccess does not exist, using default siteaccess" );
        }
    }

    /**
     * Tells whether $coreUrl allows to reach a running Solr.
     * If $coreUrl is false, the default Solr Url from solr.ini is used
     *
     * @param mixed $coreUrl
     * @return bool
     */
    protected function isSolrRunning( $coreUrl = false )
    {
        $solrBase = new eZSolrBase( $coreUrl );
        $pingResult = $solrBase->ping();
        return isset( $pingResult['status'] ) && $pingResult['status'] === 'OK';
    }

    /**
     * Tells whether Solr is running by replying to ping request.
     * In a multicore setup, all cores used to index content are checked.
     *
     * @return bool
     */
    protected function checkSolrRunning()
    {
        $eZFindINI = eZINI::instance( 'ezfind.ini' );
        if ( $eZFindINI->variable( 'LanguageSearch', 'MultiCore' ) === 'enabled' )
        {
            $shards = eZINI::instance( 'solr.ini' )->variable( 'SolrBase', 'Shards' );
            foreach ( $eZFindINI->variable( 'LanguageSearch', 'LanguagesCoresMap' ) as $locale => $coreName )
            {
                if ( isset( $shards[$coreName] ) )
                {
                    if ( !$this->isSolrRunning( $shards[$coreName] ) )
                    {
                        $this->CLI->error( "The '$coreName' Solr core is not running." );
                        $this->CLI->error( 'Please, ensure the server is started and the configurations of eZ Find and Solr are correct.' );
                        return false;
                    }
                }
                else
                {
                    $this->CLI->error( "Locale '$locale' is mapped to a core that is not listed in solr.ini/[SolrBase]/Shards." );
                    return false;
                }
            }
            return true;
        }
        else
        {
            $ret = $this->isSolrRunning();
            if ( !$ret )
            {
                $this->CLI->error( "The Solr server couldn't be reached." );
                $this->CLI->error( 'Please, ensure the server is started and the configuration of eZ Find is correct.' );
            }
            return $ret;
        }
    }

    /// Vars

    var $CLI;
    var $Script;
    var $Options;
    var $OffsetList;
    var $Executable;
    var $IterateCount = 0;
    var $Limit = 200;
    var $ObjectCount;
}

?>

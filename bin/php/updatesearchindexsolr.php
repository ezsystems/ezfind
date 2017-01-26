#!/usr/bin/env php
<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

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

$solrUpdate = new ezfUpdateSearchIndexSolr( $script, $cli, $argv[0] );
$solrUpdate->run();

$script->shutdown( 0 );

/**
 * Class containing controlling functions for updating the search index.
 */
class ezfUpdateSearchIndexSolr
{
    /**
     * @var string
     */
    protected $executedScript;

    /**
     * Constructor
     *
     * @param eZScript $script
     * @param eZCLI $cli
     * @param string $executedScript
     */
    function ezfUpdateSearchIndexSolr( eZScript $script, eZCLI $cli, $executedScript )
    {
        $this->Script = $script;
        $this->CLI = $cli;
        $this->Options = null;
        $this->executedScript = $executedScript;
    }

    /**
     * Startup and run script.
     */
    public function run()
    {
        $this->Script->startup();

        $this->Options = $this->Script->getOptions(
            "[db-host:][db-user:][db-password:][db-database:][db-type:|db-driver:][sql][clean][clean-all][conc:][php-exec:][commit-within:]",
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
                'commit-within' => 'Commit to Solr within this time in seconds (default '
                    . self::DEFAULT_COMMIT_WITHIN . ' seconds)',
            )
        );
        $this->Script->initialize();

        // check if ezfind is enabled and exit if not
        if ( ! in_array( 'ezfind', eZExtension::activeExtensions() ) )
        {
            $this->CLI->error( 'eZ Find extension is not enabled and because of that index process will fail. Please enable it and run this script again.' );
            $this->Script->shutdown( 0 );
        }

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

        if ( isset( $this->Options['commit-within'] )
                && is_numeric( $this->Options['commit-within'] ) )
        {
            $this->commitWithin = (int)$this->Options['commit-within'];
        }


        $this->output( 'Starting object re-indexing' );

        // Get PHP executable from user.
        $this->getPHPExecutable();

        $this->runMain();
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
        $useFork = ( $processLimit > 1 &&
                     function_exists( 'pcntl_fork' ) &&
                     function_exists( 'posix_kill' ) );
        if ( $useFork )
        {
            $this->output( 'Using fork.' );
        }
        else
        {
            $processLimit = 1;
        }

        $this->output( 'Using ' . $processLimit . ' concurent process(es)' );

        $processList = array();
        for ( $i = 0; $i < $processLimit; $i++ )
        {
            $processList[$i] = -1;
        }

        $this->ObjectCount = $this->objectCount();
        $this->output( 'Number of objects to index: ' . $this->ObjectCount );
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
                            if ($pid !== -1 )
                            {
                                $this->output( 'Process finished: ' . $pid );
                            }

                            $newPid = $this->forkAndExecute( $nodeID, $offset, $this->Limit );
                            $this->output( "\n" . 'Created a new process: ' . $newPid  . ' to handle '.$this->Limit.' nodes out of '.$subtreeCount.' children of node '.$nodeID.' starting at: '.$offset);

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
                        $this->output( "\n" . 'Starting a new batch' );
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
                        $this->output( 'Process finished: ' . $pid );
                        $processList[$i] = -1;
                    }
                }
            }
            // Sleep for 500 msec.
            $status = 0;
            pcntl_wait( $status, WNOHANG );

            usleep( 500000 );
        }

        $this->output( 'Optimizing. Please wait ...' );
        $searchEngine->optimize( true );
        $endTS = microtime_float();

        $this->output(
            'Indexing took ' . sprintf( '%.3f', $endTS - $startTS ) . ' secs ' .
            '( average: ' . sprintf( '%.3f', $this->ObjectCount / ( $endTS - $startTS ) ) . ' objects/sec )'
        );

        $this->output( 'Finished updating the search index.' );
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
        }
    }

    /**
     * Fork and execute
     *
     * @param int $nodeid
     * @param int $offset
     * @param int $limit
     * @return int
     */
    protected function forkAndExecute( $nodeID, $offset, $limit )
    {
        eZDB::setInstance( null );

        // Prepare DB-based cluster handler for fork (it will re-connect DB automatically).
        eZClusterFileHandler::preFork();

        $pid = pcntl_fork();

        // reinitialize DB after fork
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
            if ( $this->execute( $nodeID, $offset, $limit ) > 0 )
            {
                $this->Script->shutdown( 0 );
            }
            else
            {
                $this->Script->shutdown( 3 );
            }
        }
    }

    /**
     * Execute indexing of subtree
     *
     * @param int $nodeID
     * @param int $offset
     * @param int $limit
     *
     * @return int Number of objects indexed.
     */
    protected function execute( $nodeID, $offset, $limit )
    {
        $count = 0;
        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !( $node instanceof eZContentObjectTreeNode ) )
        {
            $this->CLI->error( "An error occured while trying fetching node $nodeID" );
            return 0;
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

                //pass false as we are going to do a commit at the end
                $result = $searchEngine->addObject( $object, false, $this->commitWithin * 1000 );
                if ( !$result )
                {
                    $this->CLI->error( ' Failed indexing ' . $object->attribute('class_identifier') .  ' object with ID ' . $object->attribute( 'id' ) );
                }
                ++$count;
            }
        }

        return $count;
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
            $this->output( "eZSearchEngine: Cleaning up search data for current installation id" );
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
            $this->output( "eZSearchEngine: Cleaning up search data for all installations" );
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

        // Forcing creation of new instance to avoid mysql wait_timeout to kill
        // the connection before it's done
        $db = eZDB::instance( false, false, true );

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
        $cli = eZCLI::instance();
        if ( !in_array( $siteaccess, eZINI::instance()->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' ) ) )
        {
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

    protected function output( $string = false, $addEOL = true )
    {
        if ( $this->CLI->isQuiet() )
            return;
        fwrite(STDOUT, $string);
        if ( $addEOL )
            fwrite(STDOUT, $this->CLI->endlineString());
    }

    const DEFAULT_COMMIT_WITHIN = 30;

    private $commitWithin = self::DEFAULT_COMMIT_WITHIN;

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

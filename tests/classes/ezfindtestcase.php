<?php 

class ezFindTestCase extends ezpDatabaseTestCase
{
    /*
     * SharedFixture == db connection (strange name?)
     * 
     * Lazy init
     */
    protected function getSharedFixture()
    {
        if( !$this->sharedFixture )
        {
            $dsn = ezpTestRunner::dsn();
            $this->sharedFixture = ezpDatabaseHelper::useDatabase( $dsn );
        }
          
        return $this->sharedFixture;
    }
}

?>
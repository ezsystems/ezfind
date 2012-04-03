<?php
/**
 * Extended attribute filters factory and base class
 * Extend this class and override filterQueryParams() method to get a working extended attribute filter
 * @author bchoquet
 */
class eZFindExtendedAttributeFilter
{

    /**
     * Modifies SolR query params according to filter parameters
     * Override this method in child class
     * @param array $queryParams
     * @param array $filterParams
     * @return array $queryParams
     */
    public function filterQueryParams( array $queryParams, array $filterParams )
    {
        return $queryParams;
    }


    /**
     * Singletons for child filters
     * key = filter id
     * val = instance
     * @var array
     */
    private static $instances = array();

    /**
     * Configuration set in ezfind.ini
     * @var array
     */
    private static $filtersList;

    /**
     * Get singleton instance for filter
     * @param string $filterID
     * @return eZFindExtendedAttributeFilter|false
     */
    public static function getInstance( $filterID )
    {
        if( !isset( self::$instances[$filterID] ) )
        {
            try
            {
                if( !self::$filtersList )
                {
                    $ini = eZINI::instance( 'ezfind.ini' );
                    self::$filtersList = $ini->variable( 'ExtendedAttributeFilters', 'FiltersList' );
                }

                if( !isset( self::$filtersList[$filterID] ) )
                {
                    throw new Exception( $filterID . ' extended attribute filter is not defined' );
                }

                $className = self::$filtersList[$filterID];
                if( !class_exists( $className ) )
                {
                    throw new Exception( 'Could not find class ' . $className );
                }

                $instance = new $className();
                if( !is_a($instance, __CLASS__ ) )
                {
                    throw new Exception( $className . ' is not a valid ' . __CLASS__ );
                }

                self::$instances[$filterID] = $instance;
            }
            catch( Exception $e)
            {
                eZDebug::writeWarning( $e->getMessage(), __METHOD__ );
                self::$instances[$filterID] = false;
            }
        }

        return self::$instances[$filterID];
    }
}
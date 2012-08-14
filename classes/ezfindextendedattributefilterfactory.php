<?php
/**
 * Extended attribute filters factory
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author bchoquet
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 */
class eZFindExtendedAttributeFilterFactory
{

    /**
     * Filters singletons
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
     * @return eZFindExtendedAttributeFilterInterface|false
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
                if( !$instance instanceof eZFindExtendedAttributeFilterInterface )
                {
                    throw new Exception( $className . ' is not a valid eZFindExtendedAttributeFilterInterface' );
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
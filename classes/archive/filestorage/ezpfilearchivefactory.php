<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezpFileArchiveFactory
{

    /**
     *
     * @param string $method
     * @param string $path
     */
    public static function getFileArchiveHandler( $method = 'filesystem' )
    {
        switch ( $method ) {
            case 'filesystem':
                return new ezpFileArchiveFileSystem();
                //break;
            default:
                return FALSE;
                //break;
        }
    }




}


?>

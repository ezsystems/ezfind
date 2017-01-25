<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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

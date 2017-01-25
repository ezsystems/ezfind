<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @author pb
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 *
 */
abstract class ezpFileArchive
{

    /**
     * archiveFile method is common for all archive classes
     * @todo maybe define a global interface instead with this method?
     * @param string $path the filepath
     * @param string $realm a realm or other classifier, possibly to be used for partitioning
     * @param array $keys an associative array of other values to avoid collisions if needed
     *        standard elements are 'prefix', 'seeds'
     *        the path should generally be used as a seed element as well
     */
    abstract protected function archiveFile( $path, $seeds, $prefix = null, $realm = null );

    /**
     *
     */
    abstract protected function getArchiveFileName( $path, $seeds, $prefix = null, $realm = null );

}



?>

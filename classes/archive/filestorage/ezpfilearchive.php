<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */
abstract class ezpFileArchive
{
    /**
     * archiveFile method is common for all archive classes
     * @todo maybe define a global interface instead with this method?
     *
     * @param string $path the filepath
     * @param array $seeds
     * @param string $prefix
     * @param string $realm a realm or other classifier, possibly to be used for partitioning
     *
     * @return array|bool
     */
    abstract protected function archiveFile( $path, $seeds, $prefix = null, $realm = null );

    /**
     * @param string $path
     * @param array $seeds
     * @param string $prefix
     * @param string $realm
     *
     * @return string
     */
    abstract protected function getArchiveFileName( $path, $seeds, $prefix = null, $realm = null );
}
?>

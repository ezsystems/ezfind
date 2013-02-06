<?php

/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @author pb
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package ezfind
 *
 */

class ezpFileArchiveFileSystem extends ezpFileArchive
{

    private $ArchiveDirName = 'archive';
    private $hashAlgorithm = 'md5';
    private $ArchiveDir;
    private $ArchiveDirLevels = 3;

    public function  __construct()
    {
        $sys = eZSys::instance();
        $storage_dir = $sys->storageDirectory();
        $this->ArchiveDir = $storage_dir . '/' . $this->ArchiveDirName;
        if (! file_exists( $this->ArchiveDir ) )
        {
            eZDir::mkdir( $this->ArchiveDir, false, true );
        }
    }


    public function archiveFile( $path, $seeds, $prefix = null, $realm = null )
    {


        $archiveFileName = $this->getArchiveFileName( $path, $seeds, $prefix, $realm );
        if ( eZFileHandler::copy( $path, $archiveFileName ) )
        {
            return array( 'archive_file_name' => $archiveFileName, 'seeds' => $seeds, 'prefix' => $prefix, 'realm' => $realm );
        }
        else
        {
            return false;
        }




    }

    public function getArchiveFileName( $path, $seeds, $prefix = null, $realm = null )
    {
        $dirElements = array();
        $dirElements[] = $this->ArchiveDir;
        if ( isset( $realm ) )
        {
            $dirElements[]= $realm;
        }
        $seed = implode ( '', $seeds );
        $hash = hash( $this->hashAlgorithm, $seed );
        $multiLevelDir = eZDir::createMultiLevelPath( substr( $hash, 0 , $this->ArchiveDirLevels ), $this->ArchiveDirLevels );
        $dirElements[] = $multiLevelDir;
        $fileDirectory = implode( '/', $dirElements );
        if ( !file_exists( $fileDirectory ) )
        {
            eZDir::mkdir( $fileDirectory, false, true );
        }
        $archiveFileName = $fileDirectory . '/';
        if ( isset( $prefix ) )
        {
            $archiveFileName .= $prefix . '-';
        }
        $archiveFileName .= $hash;

        return $archiveFileName;
    }

}

?>

<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: //autogentag//
// COPYRIGHT NOTICE: Copyright (C) 1999-2013 eZ Systems AS
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

/*! \file ezinfo.php
*/

/*!
  \class eZFindInfo ezinfo.php
  \brief The class eZFindInfo does

*/

class eZFindInfo
{
    static function info()
    {
        return array(
            'Name' => "eZ Find",
            'Version' => '//autogentag//',
            'Copyright' => "Copyright (C) 1999-2013 eZ Systems AS.",
            'Info_url' => "http://ez.no/ezfind",
            'License' => "GNU General Public License v2.0",
            '3rdparty_software' =>
                            array ( 'name' => 'Solr',
                                    'Version' => '3.6.1',
                                    'copyright' => 'The Apache Software Foundation.',
                                    'license' => 'Apache License, Version 2.0',
                                    'info_url' => 'http://lucene.apache.org/solr/' ) );
    }
}

?>

<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 1.0.x
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
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

/*! \file function_definition.php
*/

$FunctionList = array();

$FunctionList['search'] = array( 'name' => 'search',
                                 'operation_types' => 'read',
                                 'call_method' => array( 'class' => 'ezfModuleFunctionCollection',
                                                         'include_file' => 'extension/ezfind/classes/ezfmodulefunctioncollection.php',
                                                         'method' => 'search' ),
                                 'parameter_type' => 'standard',
                                 'parameters' => array( array( 'name' => 'query',
                                                               'type' => 'string',
                                                               'required' => true,
                                                               'default' => '' ),
                                                        array( 'name' => 'offset',
                                                               'type' => 'integer',
                                                               'required' => false,
                                                               'default' => 0 ),
                                                        array( 'name' => 'limit',
                                                               'type' => 'integer',
                                                               'required' => false,
                                                               'default' => 10 ),
                                                        array( 'name' => 'facet',
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'filter',
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'sort_by',
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'class_id',
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'subtree_array',
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ) ) );

$FunctionList['facetParameters'] = array( 'name' => 'facetParameters',
                                          'operation_types' => 'read',
                                          'call_method' => array( 'class' => 'ezfModuleFunctionCollection',
                                                                  'include_file' => 'extension/ezfind/classes/ezfmodulefunctioncollection.php',
                                                                  'method' => 'getFacetParameters' ),
                                          'parameter_type' => 'standard',
                                          'parameters' => array( ) );

$FunctionList['filterParameters'] = array( 'name' => 'filterParameters',
                                           'operation_types' => 'read',
                                           'call_method' => array( 'class' => 'ezfModuleFunctionCollection',
                                                                   'include_file' => 'extension/ezfind/classes/ezfmodulefunctioncollection.php',
                                                                   'method' => 'getFilterParameters' ),
                                           'parameter_type' => 'standard',
                                           'parameters' => array( ) );

?>

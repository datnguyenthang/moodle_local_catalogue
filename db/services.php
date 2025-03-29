<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * tool supporter external services.
 *
 * @package    tool_supporter
 * @copyright  2019 Benedikt Schneider, Klara Saary
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'local_catalogue_get_admin_category' => [
        'classname'   => 'local_catalogue_external',
        'methodname'  => 'get_admin_category',
        'classpath'   => 'local/catalogue/classes/externallib.php',
        'description' => 'Get the course in catalogue',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_catalogue_get_admin_list_course' => [
        'classname'   => 'local_catalogue_external',
        'methodname'  => 'get_admin_list_course',
        'classpath'   => 'local/catalogue/classes/externallib.php',
        'description' => 'Get the list of course by id',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_catalogue_admin_delete_category' => [
        'classname'   => 'local_catalogue_external',
        'methodname'  => 'admin_delete_category',
        'classpath'   => 'local/catalogue/classes/externallib.php',
        'description' => 'Delete the category',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_catalogue_admin_delete_course' => [
        'classname'   => 'local_catalogue_external',
        'methodname'  => 'admin_delete_course',
        'classpath'   => 'local/catalogue/classes/externallib.php',
        'description' => 'Delete the course',
        'type'        => 'write',
        'ajax'        => true,
    ],

];

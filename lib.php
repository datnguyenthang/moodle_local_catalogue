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
 * This plugin serves as a database and plan for all learning activities in the organization,
 * where such activities are organized for a more structured learning program.
 * @package    local_catalogue
 * @copyright  3i Logic<lms@3ilogic.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @author     Azmat Ullah <azmat@3ilogic.com>
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

/**
 * Serve files from the local catalogue plugin.
 *
 * @param string $filearea The file area (e.g., 'course_images').
 * @param int $courseid The course ID associated with the file.
 * @param int $itemid The ID of the item (course ID) that the file is related to.
 * @param string $filename The name of the file to be served.
 * @param string $filepath The file path.
 * @param array $options Additional options.
 * @return void
 */
function local_catalogue_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    if ($filearea !== 'categoryimage' && $filearea !== 'courseimage') {
        return false;
    }

    require_login();

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_catalogue', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Get the course image URL for a given course.
 *
 * @param int $categoryid The course ID.
 * @return string The full URL to the course image.
 */
function get_category_image_url($categoryid) {
    global $CFG, $USER;

    $context = context_system::instance(); // Use course context if applicable

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_catalogue', 'categoryimage', $categoryid, '0,0', false);

    if ($file && $file->is_visible()) {
        return $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/local_catalogue/categoryimage/' . $categoryid . '/' . $file->get_filename();
    }
}

/**
 * Get the course image URL for a given course.
 *
 * @param int $courseid The course ID.
 * @return string The full URL to the course image.
 */
function get_course_image_url($courseid) {
    global $CFG, $USER;

    $context = context_system::instance();

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_catalogue', 'courseimage', $courseid, '0,0', false);

    if ($file && $file->is_visible()) {
        return $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/local_catalogue/courseimage/' . $courseid . '/' . $file->get_filename();
    }
}

function type_of_course(){
    return [
        1 => get_string('type_online', 'local_catalogue'),
        2 => get_string('type_offline', 'local_catalogue'),
        3 => get_string('type_blended', 'local_catalogue')
    ];
}
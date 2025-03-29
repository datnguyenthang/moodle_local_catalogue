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
 * @package    local_learningpath
 * @copyright  3i Logic<lms@3ilogic.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @author     Azmat Ullah <azmat@3ilogic.com>
 */
namespace local_catalogue\form;
defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use core_form\dynamic_form;
use moodle_url;
use stdClass;
require_once($CFG->dirroot . '/local/catalogue/locallib.php');

class category_form extends dynamic_form {
    protected function definition() {
        $mform = $this->_form;

        $customdata = $this->_customdata;
        $ajaxformdata = $this->_ajaxformdata;
        $data = $ajaxformdata ?? $customdata;

        // Hidden field for category ID (used for editing)
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Category name field
        $mform->addElement('text', 'name', get_string('categoryname', 'local_catalogue'), ['size' => 70]);
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $categoryname);
        $mform->addRule('name', null, 'required', null, 'client');

        // Parent category dropdown
        // Fetch all categories for the parent category dropdown
        $categories = get_category_paths();
        $options = ['' => get_string('none', 'local_catalogue')];
        foreach ($categories as $category) {
            $options[$category->id] = $category->path;
        }

        // Description.
        $mform->addElement('editor', 'description_editor', get_string('description', 'local_catalogue'));
        $mform->setType('description_editor', PARAM_RAW); // Allow raw HTML to be saved.

        // Publish
        $mform->addElement('select', 'parent', get_string('parentcategory', 'local_catalogue'), $options);
        $mform->setType('parent', PARAM_INT);
        $mform->setDefault('parent', $parent);

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_catalogue'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->addRule('sortorder', null, 'numeric', null, 'client');
        $mform->addRule('sortorder', get_string('error_positive_number', 'local_catalogue'), 'regex', '/^\d+$/', 'client');

         // Catalogue Image.
        $mform->addElement('filemanager', 'categoryimage', get_string('categoryimage', 'local_catalogue'), null, [
            'subdirs' => 0,
            'maxbytes' => 10485760, // 10MB
            'areamaxbytes' => 10485760, // 10MB
            'maxfiles' => 1,
            'accepted_types' => array('image')
        ]);

        // Published
        $options = [
            0 => get_string('no'),
            1 => get_string('yes')
        ];
        
        $mform->addElement('select', 'visible', get_string('visible'), $options);
        $mform->setType('visible', PARAM_INT);
        $mform->setDefault('visible', 1);

        $this->add_action_buttons();
    }

    // Custom validation
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }

    public function process_dynamic_submission() {
        global $DB, $USER;
        $data = parent::get_data();

        if (isset($data->description_editor)) {
            $data->description = !empty($data->description_editor['text']) ? $data->description_editor['text'] : null;
            unset($data->description_editor);
        }
        $data->timemodified = time();

        if (!empty($data->id)) {
            $data->modifiedby = $USER->id;
            $DB->update_record('local_catalogue_categories', $data);
        } else {
            $data->timecreated = time();
            $data->id = $DB->insert_record('local_catalogue_categories', $data);
        }

        if (!empty($data->categoryimage)) {
            $draftitemid = $data->categoryimage;
            file_save_draft_area_files($draftitemid, \context_system::instance()->id, 'local_catalogue', 'categoryimage', $data->id, [
                'subdirs' => 0,
                'maxbytes' => 10485760, // 10MB
                'maxfiles' => 1,
                'accepted_types' => ['image']
            ]);
        }

        return $data;
    }

    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $data = (object)$this->_ajaxformdata;
        if (empty($data->id)) {
            return;
        }

        $data = $DB->get_record('local_catalogue_categories', ['id' => $data->id]);
        if (!empty($data->description)) 
            $data->description_editor = ['text' => $data->description, 'format' => FORMAT_HTML];
        
        // Load file into draft area.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $draftitemid = file_get_submitted_draft_itemid('categoryimage');

        file_prepare_draft_area($draftitemid, $context->id, 'local_catalogue', 'categoryimage', $data->id, [
            'subdirs' => 0,
            'maxbytes' => 10485760,
            'maxfiles' => 1
        ]);
        $data->categoryimage = $draftitemid;

        $this->set_data($data);
    }

    /**
     * Get page URL for dynamic submission.
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/catalogue/management.php');
    }

    /**
     * Get context for dynamic submission.
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Check access for dynamic submission.
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        // Perhaps we will need a specific campaigns capability.
        require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
    }
}

<?php
namespace local_catalogue\form;
defined('MOODLE_INTERNAL') || die();

global $CFG;
use context;
use context_system;
use core_form\dynamic_form;
use moodle_url;
use stdClass;
require_once($CFG->dirroot . '/local/catalogue/lib.php');
require_once($CFG->dirroot . '/local/catalogue/locallib.php');

class course_form extends dynamic_form {
    protected function definition() {
        $mform = $this->_form;

        $customdata = $this->_customdata;
        $ajaxformdata = $this->_ajaxformdata;
        $data = $ajaxformdata ?? $customdata;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Course name field
        $mform->addElement('text', 'name', get_string('coursename', 'local_catalogue'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Course description field
        $mform->addElement('textarea', 'description', get_string('description', 'local_catalogue'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('description', PARAM_TEXT);

        // Course duration field (in hours)
        $mform->addElement('text', 'duration', get_string('courseduration', 'local_catalogue') . ' (' . get_string('hours', 'local_catalogue') . ')');
        $mform->setType('duration', PARAM_INT);

        // Course code (must be unique)
        $mform->addElement('text', 'code', get_string('coursecode', 'local_catalogue'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        // Course visible (true/false value)
        $mform->addElement('selectyesno', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);

        // Course type (three types: Online, Offline, Pledge)
        $mform->addElement('select', 'type', get_string('coursetype', 'local_catalogue'), type_of_course());
        $mform->setType('type', PARAM_INT);

        // Course status
        $mform->addElement('selectyesno', 'status', get_string('coursestatus', 'local_catalogue'));
        $mform->setDefault('status', 1);

        // Category ID dropdown using category paths
        $categories = get_category_paths();
        $category_options = ['' => get_string('selectcategory', 'local_catalogue')];
        foreach ($categories as $category) {
            $category_options[$category->id] = $category->path;
        }
        $mform->addElement('select', 'category_id', get_string('coursecategory', 'local_catalogue'), $category_options);
        $mform->setType('category_id', PARAM_INT);
        $mform->addRule('category_id', null, 'required', null, 'client');

         // Catalogue Image.
         $mform->addElement('filemanager', 'courseimage', get_string('courseimage', 'local_catalogue'), null, [
            'subdirs' => 0,
            'maxbytes' => 10485760, // 10MB
            'areamaxbytes' => 10485760, // 10MB
            'maxfiles' => 1,
            'accepted_types' => array('image')
        ]);

        // Add action buttons
        $this->add_action_buttons(true, $buttonlabel);
    }

    // Custom validation
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $course = $DB->get_record('local_catalogue_courses', ['code' => $data['code']]);
        if($course && $course->id != $data['id']) {
            $errors['code'] = get_string('error_dupplicate_code', 'local_catalogue');
        }

        return $errors;
    }

    public function process_dynamic_submission() {
        global $DB, $USER;
        $data = parent::get_data();

        if (isset($data->description_editor)) {
            $data->description = !empty($data->description_editor['text']) ? $data->description_editor['text'] : null;
            unset($data->description_editor);
        }

        if (!empty($data->id)) {
            $data->timemodified = time();
            $DB->update_record('local_catalogue_courses', $data);
        } else {
            $data->timecreated = time();
            $data->id = $DB->insert_record('local_catalogue_courses', $data);
        }

        // Adding new learning path photo.
        if (!empty($data->courseimage)) {
            $draftitemid = $data->courseimage;
            file_save_draft_area_files($draftitemid, \context_system::instance()->id, 'local_catalogue', 'courseimage', $data->id, [
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
            $data->category_id = $data->category;
            $this->set_data($data);
            return;
        }

        $lcc = $DB->get_record('local_catalogue_courses', ['id' => $data->id]);
        if (empty($lcc)) {
            $newdata = new stdClass();
            $newdata->category_id = $data->category;
            $this->set_data($newdata);
            return;
        } else {
            if (!empty($lcc->description)) 
                $lcc->description_editor = ['text' => $lcc->description, 'format' => FORMAT_HTML];
            
            // Load file into draft area.
            $context = \context_system::instance();
            $fs = get_file_storage();
            $draftitemid = file_get_submitted_draft_itemid('courseimage');

            file_prepare_draft_area($draftitemid, $context->id, 'local_catalogue', 'courseimage', $lcc->id, [
                'subdirs' => 0,
                'maxbytes' => 10485760,
                'maxfiles' => 1
            ]);
            $lcc->courseimage = $draftitemid;

            $this->set_data($lcc);
        }
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

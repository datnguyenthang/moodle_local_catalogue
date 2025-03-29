<?php
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . "/local/catalogue/lib.php");
require_once($CFG->dirroot . "/local/catalogue/locallib.php");

class local_catalogue_external extends external_api {

    //get admin catergory
    public static function get_admin_category_parameters() {
        return new external_function_parameters(
            array(
                'category' => new external_value(PARAM_INT, 'Category ID', VALUE_DEFAULT, 0),
            )
        );
    }
    
    public static function get_admin_category($category) {
        global $DB;
        $params = self::validate_parameters(self::get_admin_category_parameters(), array(
            'category' => $category
        ));
    
        $html = display_category_catalogue($params['category'] ?? 0);
    
        return ['html' => $html];
    }

    public static function get_admin_category_returns() {
        return new external_single_structure(
            array(
                'html' => new external_value(PARAM_RAW, 'The generated HTML for the category catalogue')
            )
        );
    }

    //get admin list courses
    public static function get_admin_list_course_parameters() {
        return new external_function_parameters(
            array(
                'category' => new external_value(PARAM_INT, 'Category ID', VALUE_DEFAULT, 0),
            )
        );
    }

    public static function get_admin_list_course($category) {
        global $DB;
        $params = self::validate_parameters(self::get_admin_list_course_parameters(), array(
            'category' => $category
        ));

        $html = '';
        $category = $DB->get_record('local_catalogue_categories', array('id' => $params['category']));
        if (!$category) {
            $html .= '<div class="listing-pagination-totals text-muted">'.get_string('nocourseincategory', 'local_catalogue').'</div>';
            return [
                'name' => 'No category selected',
                'html' => $html
            ];
        }
    
        $html .= display_course_catalogue($params['category']);
    
        return [
            'name' => $category->name,
            'html' => $html
        ];
    }

    public static function get_admin_list_course_returns() {
        return new external_single_structure(
            array(
                'name' => new external_value(PARAM_TEXT, 'The name of the category'),
                'html' => new external_value(PARAM_RAW, 'The generated HTML for the course catalogue')
            )
        );
    }

    //Delete category
    public static function admin_delete_category_parameters() {
        return new external_function_parameters(
            array(
                'category' => new external_value(PARAM_INT, 'Category ID', VALUE_DEFAULT, 0),
            )
        );
    }

    public static function admin_delete_category($category) {
        global $DB;
        $params = self::validate_parameters(self::admin_delete_category_parameters(), array(
            'category' => $category
        ));

        $category = $DB->get_record('local_catalogue_categories', array('id' => $params['category']));
        if (!$category) {
            return [
                'success' => false,
                'message' => get_string('categorynotfound', 'local_catalogue')
            ];
        }

        $DB->delete_records('local_catalogue_categories', array('id' => $params['category']));
        $DB->delete_records('local_catalogue_courses', array('category_id' => $params['category']));

        return [
            'success' => true,
            'message' => get_string('categorydeleted', 'local_catalogue')
        ];
    }

    public static function admin_delete_category_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Whether the category was deleted successfully'),
                'message' => new external_value(PARAM_TEXT, 'The message to display to the user')
            )
        );
    }

    //Delete course
    public static function admin_delete_course_parameters() {
        return new external_function_parameters(
            array(
                'course' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            )
        );
    }

    public static function admin_delete_course($course) {
        global $DB;
        $params = self::validate_parameters(self::admin_delete_course_parameters(), array(
            'course' => $course
        ));

        $course = $DB->get_record('local_catalogue_courses', array('id' => $params['course']));
        if (!$course) {
            return [
                'success' => false,
                'message' => get_string('coursenotfound', 'local_catalogue')
            ];
        }

        $DB->delete_records('local_catalogue_courses', array('id' => $params['course']));

        return [
            'success' => true,
            'message' => get_string('coursedeleted', 'local_catalogue')
        ];
    }

    public static function admin_delete_course_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Whether the course was deleted successfully'),
                'message' => new external_value(PARAM_TEXT, 'The message to display to the user')
            )
        );
    }
}

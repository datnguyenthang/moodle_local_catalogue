<?php
require_once('../../config.php');
require_once('locallib.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/catalogue/management.php');
$PAGE->set_title(get_string('managecategories', 'local_catalogue'));
$PAGE->requires->js_call_amd('local_catalogue/index', 'init');
//$PAGE->set_pagelayout('standard');

$category_id = optional_param('category_id', 0, PARAM_INT);

$templatecontext = [
    'category_id' => $category_id
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalogue/management', $templatecontext);
echo $OUTPUT->footer();
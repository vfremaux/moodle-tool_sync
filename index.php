<?php
/**
 * sync.php - allows admin to create or delete courses,users,enrol from csv files
 * author - Funck Thibaut
 */

require('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/sync/tool_form.php');

// Security.

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('tool/sync:configure', $context);

tool_sync_capture_input_files(true);

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}
if (! $site = get_site()) {
    print_error('errornosite', 'tool_sync');
}
if (!$adminuser = get_admin()) {
    print_error('errornoadmin', 'tool_sync');
}

$syncconfig = get_config('tool_sync');

if (!isset($syncconfig->encoding)) {
    set_config('encoding', 'UTF-8', 'tool_sync');
    $syncconfig->encoding = 'UTF-8';
}

if (!isset($syncconfig->csvseparator)) {
    set_config('csvseparator', ';', 'tool_sync');
    $syncconfig->csvseparator = ';';
}

if (!isset($syncconfig->userpictures_userfield)) {
    set_config('userpictures_userfield', 1, 'tool_sync');
    $syncconfig->userpictures_userfield = 1;
}

if (!isset($syncconfig->userpictures_fileprefix)) {
    set_config('userpictures_fileprefix', 'userpictures_', 'tool_sync');
    $syncconfig->userpictures_fileprefix = 1;
}

if (!isset($syncconfig->userpictures_forcedeletion)) {
    set_config('userpictures_forcedeletion', 1, 'tool_sync');
    $syncconfig->userpictures_forcedeletion = 1;
}

if (!isset($syncconfig->userpictures_overwrite)) {
    set_config('userpictures_overwrite', 1, 'tool_sync');
    $syncconfig->userpictures_overwrite = 1;
}

/// If data submitted, then process and store.

$form = new ToolForm();

if ($data = $form->get_data()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    foreach ($data as $key => $value) {
        if (strpos($key, '/') > 0) {
            // Is a configuration key
            list($plugin, $datakey) = explode('/', $key);
            set_config($datakey, $value, 'tool_sync');
            // Refresh the currently loaded config for reflecting in form.
            $syncconfig->$datakey = $value;
        }
    }
    
    redirect(new moodle_url('/admin/tool/sync/index.php', array('resultmessage' => get_string('changessaved'))));
}

// Print current courses type description.

$url = $CFG->wwwroot."/admin/tool/sync/index.php";
// $PAGE->set_title(format_string($SITE->fullname));
$PAGE->set_url($url);
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('title', 'tool_sync'));

if ($message = optional_param('resultmessage', '', PARAM_TEXT)) {
    echo $OUTPUT->box_start('center', '100%', '', 5, 'informationbox');
    echo $message;
    echo $OUTPUT->box_end();
}

$formdata = tool_sync_config_add_sync_prefix($syncconfig);

$form->set_data($formdata);
$form->display();

echo $OUTPUT->footer();

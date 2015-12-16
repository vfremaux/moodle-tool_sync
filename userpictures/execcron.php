<?php
/**
 * @author Funck Thibaut
 *
 */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

require_login();
require_capability('tool/sync:configure', $systemcontext);

// Capture incoming files in <moodledata>/sync.
tool_sync_capture_input_files(false);

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$picturemanager = new \tool_sync\userpictures_sync_manager;

set_time_limit(1800);
raise_memory_limit('512M');

$url = new moodle_url('/admin/tool/sync/userpictures/execcron.php');
$PAGE->set_url($url);
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('userpicturesmgtmanual', 'tool_sync'));
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$action = optional_param('action', 'proces', PARAM_TEXT);

if ($action == 'registerallpictures') {
    $form = new ConfirmForm($url);
} elseif ($action == 'configregisterallpictures') {
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sync/index.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('userpicturesync', 'tool_sync'), 'userpicturesync', 'tool_sync');

echo $OUTPUT->heading(get_string('userpicturesmanualsync', 'tool_sync'), 3);

// Get processable files and print entries for information.
$fs = get_file_storage();

$filerec = new StdClass();
$component = 'tool_sync';
$filearea = 'syncfiles';
$itemid = 0;
$areafiles = $fs->get_area_files($systemcontext->id, $component, $filearea, $itemid);

// Searching in area what matches userpicture archives
if (!empty($areafiles)) {
    echo '<ul>';
    foreach ($areafiles as $f) {
        if (preg_match('/^'.$syncconfig->userpictures_fileprefix.'.*\.zip/', $f->get_filename())) {
            echo '<li>'.$f->get_filename().'</li>';
        }
    }
    echo '</ul>';
}

echo $OUTPUT->heading(get_string('processresult', 'tool_sync'), 3);

echo "<pre>";
$picturemanager->cron($syncconfig);
echo "</pre>";

echo $renderer->print_return_button();

echo $OUTPUT->footer();

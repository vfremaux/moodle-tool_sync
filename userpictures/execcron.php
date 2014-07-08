<?php
/**
 * @author Funck Thibaut
 *
 */

require_once("../../../../config.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');

$systemcontext = context_system_instance();
$PAGE->set_context($systemcontext);

require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}
if (! $site = get_site()) {
    print_error('errornosite', 'tool_sync');
}
if (!$adminuser = get_admin()) {
    print_error('errornoadmin', 'tool_sync');
}

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$picturemanager = new userpictures_plugin_manager;

set_time_limit(1800);
raise_memory_limit('512M');

$url = $CFG->wwwroot.'/admin/tool/sync/userpictures/execcron.php';
$PAGE->set_url($url);
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('userpicturesmgtmanual', 'tool_sync'));
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('userpicturesync', 'tool_sync'), 'userpicturesync', 'tool_sync');

echo $OUTPUT->heading(get_string('userpicturesmanualsync', 'tool_sync'), 3);

// Get processable files and print entries for information.

$filerec = new StdClass();
$contextid = context_system::intance()->id;
$component = 'tool_sync';
$filearea = 'syncfiles';
$itemid = 0;
$areafiles = $fs->get_area_files($contextid, $component, $filearea, $itmid);

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

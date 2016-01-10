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
 * @package tool_sync
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

$action = optional_param('action', 'process', PARAM_TEXT);

if ($action == 'registerallpictures') {
    $confirmurl = new moodle_url('/admin/tool/sync/userpictures/execcron.php', array('action' => 'confirmregisterallpictures'));
    $cancelurl = new moodle_url('/admin/tool/sync/index.php');
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('confirm'), $confirmurl, $cancelurl);
    echo $OUTPUT->footer();
    exit;
} elseif ($action == 'confirmregisterallpictures') {
    echo $OUTPUT->header();
    echo '<pre>';
    echo '<h3>Updating user pictures</h3>';
    update_all_user_picture_hashes(true);
    echo '</pre>';
    echo $OUTPUT->continue_button(new moodle_url('/admin/tool/sync/index.php'));
    echo $OUTPUT->footer();
    exit;
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

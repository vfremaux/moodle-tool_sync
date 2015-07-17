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
 * @author Funck Thibaut
 * @package tool-sync
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

// Security.
if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$url = $CFG->wwwroot.'/admin/tool/sync/courses/checkcourses.php';
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('coursecheck', 'tool_sync'), null);
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$form = new InputFileLoadForm($url, array('localfile' => $syncconfig->course_fileexistlocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if (!empty($data->uselocal)) {
        $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CHECK);
        $canprocess = true;
        $processedfile = $syncconfig->course_fileexistlocation;
    } else {
        $usercontext = context_user::instance($USER->id);

        $fs = get_file_storage();

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

            $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);
            $uploadedfile = array_pop($areafiles);

            $manualfilerec = new StdClass();
            $manualfilerec->contextid = $usercontext->id;
            $manualfilerec->component = 'user';
            $manualfilerec->filearea = 'draft';
            $manualfilerec->itemid = $data->inputfile;
            $manualfilerec->filepath = $uploadedfile->get_filepath();
            $manualfilerec->filename = $uploadedfile->get_filename();
            $processedfile = $manualfilerec->filename;

            $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CHECK, $manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('checkingcourse', 'tool_sync'));

$form->display();

if ($canprocess) {
    echo '<pre>';
    $coursesmanager->cron($syncconfig);
    echo '</pre>';

    $usermgtmanual = get_string('checkingcourse', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);
    
    echo "<br/><fieldset><legend><strong>$usermgtmanual</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';
}

// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

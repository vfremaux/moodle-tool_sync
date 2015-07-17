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
 * A moodle addon to quickly remove a number of courses by uploading an
 *       unformatted text file containing the shortnames of the courses
 *       each on its own line
 *
 * @author Funck Thibaut
 *
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/lib/uploadlib.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

// security
if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$strenrolname = get_string('enrolname', 'tool_sync');
$strdeletecourses = get_string('coursedeletion', 'tool_sync');
$strchoose = get_string('choose');

set_time_limit(300);

list($usec, $sec) = explode(' ', microtime());
$time_start = ((float)$usec + (float)$sec);
$url = $CFG->wwwroot.'/admin/tool/sync/courses/deletecourses.php';
$PAGE->navigation->add($strenrolname);
$PAGE->navigation->add($strdeletecourses);
$PAGE->set_url($url);
$PAGE->set_title("$site->shortname: $strdeletecourses");
$PAGE->set_heading($site->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('coursedeletion', 'tool_sync'), 'coursedeletion', 'tool_sync');

// Page controller

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_filedeletelocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if ($data->uselocal) {
        $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_DELETE);
        $canprocess = true;
        $processedfile = $syncconfig->course_filedeletelocation;
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

            $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_DELETE, $manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('deletingcourses', 'tool_sync'));

$form->display();

if ($canprocess) {
    echo '<pre>';
    $coursesmanager->cron($syncconfig);
    echo '</pre>';

    $usermgtmanual = get_string('deletingcourses', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);
    
    echo "<br/><fieldset><legend><strong>$usermgtmanual</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';
}

// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

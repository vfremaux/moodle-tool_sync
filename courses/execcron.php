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
 */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

$action = optional_param('action', SYNC_COURSE_CHECK | SYNC_COURSE_DELETE | SYNC_COURSE_CREATE_DELETE, PARAM_INT);

set_time_limit(1800);
raise_memory_limit('512M');

$systemcontext = context_system::instance();
$PAGE->set_context(null);

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');

require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$url = new moodle_url('/admin/tool/sync/courses/execcron.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('coursesync', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_fileuploadlocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if (!empty($data->uselocal)) {
        // Use the server side stored file.
        $enrolsmanager = new course_plugin_manager($action);
        $processedfile = $syncconfig->course_fileuploadlocation;
        $canprocess = true;
    } else {
        // Use the just uploaded file.

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

            $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);

            // Take last as former is the / directory
            $uploadedfile = array_pop($areafiles);

            $manualfilerec = new StdClass();
            $manualfilerec->contextid = $usercontext->id;
            $manualfilerec->component = 'user';
            $manualfilerec->filearea = 'draft';
            $manualfilerec->itemid = $data->inputfile;
            $manualfilerec->filepath = $uploadedfile->get_filepath();
            $manualfilerec->filename = $uploadedfile->get_filename();
            $processedfile = $manualfilerec->filename;
    
            $coursesmanager = new course_sync_manager($action, $manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('coursesync', 'tool_sync'), 'coursesync', 'tool_sync');

if ($action & SYNC_COURSE_CHECK) {
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_fileexistlocation);
    echo "<center>$cronrunmsg</center>";
}

if ($action & SYNC_COURSE_DELETE) {
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_filedeletelocation);
    echo "<center>$cronrunmsg</center>";
}

if ($action & SYNC_COURSE_CREATE_DELETE) {
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_fileuploadlocation);
    echo "<center>$cronrunmsg</center>";
}

$form->display();

if ($canprocess) {
    echo '<pre>';
    $enrolsmanager->cron($syncconfig);
    echo '</pre>';

    $enrolmgtmanual = get_string('enrolmgtmanual', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);

    echo "<br/><fieldset><legend><strong>$enrolmgtmanual</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';
}


// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();


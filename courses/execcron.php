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
 * @package   tool_sync
 * @category  tool
 * @author Funck Thibaut
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

$action = optional_param('action', SYNC_COURSE_CHECK | SYNC_COURSE_DELETE | SYNC_COURSE_CREATE_DELETE, PARAM_INT);

set_time_limit(1800);
raise_memory_limit('512M');

// Capture incoming files in <moodledata>/sync.
tool_sync_capture_input_files(false);

// Security.

$systemcontext = context_system::instance();
$PAGE->set_context(null);
require_login();
require_capability('tool/sync:configure', $systemcontext);

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');

$url = new moodle_url('/admin/tool/sync/courses/execcron.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('coursesync', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$singlecommand = false;
if ($action == SYNC_COURSE_DELETE) {
    $form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_filedeletelocation));
    $singlecommand = true;
} else if ($action == SYNC_COURSE_CHECK) {
    $form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_fileexistlocation));
    $singlecommand = true;
} else if ($action == SYNC_COURSE_CREATE) {
    $form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_fileuploadlocation));
    $singlecommand = true;
} else {
    $form = new InputfileLoadform($url, array('runlocalfiles' => true));
    $singlecommand = false;
}

$canprocess = false;

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sync/index.php'));
}

if ($data = $form->get_data()) {
    if (!empty($data->uselocal)) {
        // Use the server side stored file.
        $coursesmanager = new \tool_sync\course_sync_manager($action);
        $processedfile = $syncconfig->course_fileuploadlocation;
        $canprocess = true;
    } else if (!empty($data->runlocalfiles)) {
        $coursesmanager = new \tool_sync\course_sync_manager($action);
        $canprocess = true;
    } else {
        // Use the just uploaded file.

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

            $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);

            // Take last as former is the / directory.
            $uploadedfile = array_pop($areafiles);

            $manualfilerec = new StdClass();
            $manualfilerec->contextid = $usercontext->id;
            $manualfilerec->component = 'user';
            $manualfilerec->filearea = 'draft';
            $manualfilerec->itemid = $data->inputfile;
            $manualfilerec->filepath = $uploadedfile->get_filepath();
            $manualfilerec->filename = $uploadedfile->get_filename();
            $processedfile = $manualfilerec->filename;

            $coursesmanager = new \tool_sync\course_sync_manager($action, $manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('coursesync', 'tool_sync'), 'coursesync', 'tool_sync');

$formdata = new StdClass;
$formdata->action = $action;
$form->set_data($formdata);
$form->display();

if ($canprocess) {

    $coursemgtmanual = get_string('coursemgtmanual', 'tool_sync');
    echo "<br/><fieldset><legend><strong>$coursemgtmanual</strong></legend>";

    if ($action & SYNC_COURSE_CHECK) {
        if ($syncconfig->course_fileexistlocation) {
            $taskrunmsg = get_string('taskrunmsg', 'tool_sync', $syncconfig->course_fileexistlocation);
            echo "<center>$taskrunmsg</center>";
        } else {
            $taskrunmsg = get_string('taskrunmsgnofile', 'tool_sync');
            echo "<center>$taskrunmsg</center>";
        }
    }

    if ($action & SYNC_COURSE_DELETE) {
        if ($syncconfig->course_filedeletelocation) {
            $taskrunmsg = get_string('taskrunmsg', 'tool_sync', $syncconfig->course_filedeletelocation);
            echo "<center>$taskrunmsg</center>";
        } else {
            $taskrunmsg = get_string('taskrunmsgnofile', 'tool_sync');
            echo "<center>$taskrunmsg</center>";
        }
    }

    if ($action & SYNC_COURSE_CREATE) {
        if ($syncconfig->course_fileuploadlocation) {
            $taskrunmsg = get_string('taskrunmsg', 'tool_sync', $syncconfig->course_fileuploadlocation);
            echo "<center>$taskrunmsg</center>";
        } else {
            $taskrunmsg = get_string('taskrunmsgnofile', 'tool_sync');
            echo "<center>$taskrunmsg</center>";
        }
    }

    echo '</fieldset>';
    echo '<pre>';
    $coursesmanager->cron($syncconfig);
    echo '</pre>';
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

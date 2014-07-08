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
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');

$url = $CFG->wwwroot.'/admin/tool/sync/courses/resetcourses.php';
$PAGE->set_url($url);
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('coursereset', 'tool_sync'), null);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$form = new InputFileLoadForm($url, array('localfile' => $syncconfig->course_fileresetlocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if ($data->uselocal) {
        $coursesmanager = new course_sync_manager(SYNC_COURSE_RESET);
        $canprocess = true;
        $processedfile = $syncconfig->course_fileresetlocation;
    } else if (!empty($areafiles)) {
        $fs = get_file_storage();

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

            $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);
            $uploadedfile = array_pop($areafiles);

            $manualfilerec = new StdClass();
            $manualfilerec->contextid = $systemcontext->id;
            $manualfilerec->component = 'user';
            $manualfilerec->filearea = 'draft';
            $manualfilerec->itemid = $data->inputfile;
            $manualfilerec->filepath = $uploadedfile->get_filepath();
            $manualfilerec->filename = $uploadedfile->get_filename();
            $processedfile = $manualfilerec->filename;

            $coursesmanager = new course_sync_manager(SYNC_COURSE_RESET, $manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('resettingcourse', 'tool_sync'));

$form->display();

if ($canprocess) {
    echo '<pre>';
    $status = $coursesmanager->cron($syncconfig);
    echo '</pre>';

    $cmdname = get_string('resettingcourse', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);
    
    echo "<br/><fieldset><legend><strong>$cmdname</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';

    $data = array();
    if (!empty($status) && is_array($status)) {
        foreach ($status as $item) {
            $line = array();
            $line[] = $item['component'];
            $line[] = $item['item'];
            $line[] = ($item['error'] === false) ? get_string('ok') : '<div class="notifyproblem">'.$item['error'].'</div>';
            $data[] = $line;
        }
    }

    $table = new html_table();
    $table->head  = array(get_string('resetcomponent'), get_string('resettask'), get_string('resetstatus'));
    $table->size  = array('20%', '40%', '40%');
    $table->align = array('left', 'left', 'left');
    $table->width = '80%';
    $table->data  = $data;
    
    echo '<fieldset>';
    echo html_writer::table($table);
    echo '</fieldset>';
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();


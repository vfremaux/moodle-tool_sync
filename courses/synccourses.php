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
 * @package     tool_sync
 * @category    tool
 * @author      Funck Thibaut
 * @copyright   2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');
require_once($CFG->dirroot.'/lib/uploadlib.php');

// Security.

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();
require_capability('tool/sync:configure', $systemcontext);

$strenrolname = get_string('enrolname', 'tool_sync');
$strdeletecourses = get_string('coursedeletion', 'tool_sync');
$strchoose = get_string('choose');

set_time_limit(1200);

list($usec, $sec) = explode(' ', microtime());
$timestart = ((float)$usec + (float)$sec);
$url = new moodle_url('/admin/tool/sync/courses/deletecourses.php');
$PAGE->navigation->add($strenrolname);
$PAGE->navigation->add($strdeletecourses);
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname: $strdeletecourses");
$PAGE->set_heading($SITE->fullname);

// Page controller.

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$form = new InputfileLoadform($url, array('localfile' => $syncconfig->course_filecreatelocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if ($data->uselocal) {
        $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CREATE_DELETE);
        $canprocess = true;
        $processedfile = $syncconfig->course_filecreatelocation;
    } else {
        if (!$manualfilerec = tool_sync_receive_file()) {
            $errormes = "Failed loading a file";
        } else {
            $processedfile = $manualfilerec->filename;
            $coursesmanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CREATE_DELETE, $manualfilerec);
            $canprocess = true;
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('creatingcourses', 'tool_sync'));

$form->display();

if ($canprocess) {
    echo '<pre>';
    $coursesmanager->cron($syncconfig);
    echo '</pre>';
    $timeend = ((float)$usec + (float)$sec);
    $elapsed = $timeend - $timestart;

    $usermgtmanual = get_string('creatingcourses', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);

    echo "<br/><fieldset><legend><strong>$usermgtmanual</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

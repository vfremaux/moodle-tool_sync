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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/cohorts.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

// Security.

require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_capability('tool/sync:configure', $systemcontext);

// Capture incoming files in <moodledata>/sync.
tool_sync_capture_input_files(false);

set_time_limit(1800);
raise_memory_limit('512M');

$renderer = $PAGE->get_renderer('tool_sync');
$cohortssmanager = new \tool_sync\cohorts_sync_manager();
$syncconfig = get_config('tool_sync');

$url = new moodle_url('/admin/tool/sync/cohorts/execcron.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('cohortmgtmanual', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$form = new InputfileLoadform($url, array('localfile' => $syncconfig->cohorts_filelocation));

$canprocess = false;

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sync/index.php'));
}

if ($data = $form->get_data()) {

    $syncconfig->simulate = @$data->simulate;

    if (!empty($data->uselocal)) {
        // Use the server side stored file.
        $usersmanager = new \tool_sync\users_sync_manager();
        $processedfile = $syncconfig->cohorts_filelocation;
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

            $usersmanager = new \tool_sync\cohorts_sync_manager($manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}
echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('cohortmgtmanual', 'tool_sync'), 'cohortsync', 'tool_sync');

$form->display();

if ($canprocess) {

    $address = @$syncconfig->cohorts_filelocation;

    $cohortmgtmanual = get_string('cohortmgtmanual', 'tool_sync');
    $taskrunmsg = get_string('taskrunmsg', 'tool_sync', $address);

    echo "<br/><fieldset><legend><strong>$cohortmgtmanual</strong></legend>";
    echo "<center>$taskrunmsg</center>";

    echo '<pre>';
    $cohortssmanager->cron($syncconfig);
    echo '</pre>';

    echo '</fieldset>';
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

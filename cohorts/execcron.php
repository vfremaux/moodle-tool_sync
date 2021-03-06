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
$action = optional_param('action', SYNC_COHORT_CREATE_UPDATE, PARAM_INT);

$cohortsmanager = new \tool_sync\cohorts_sync_manager($action, null);
$syncconfig = get_config('tool_sync');

$url = new moodle_url('/admin/tool/sync/cohorts/execcron.php', array('action' => $action));
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('cohortmgtmanual', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

if ($action == SYNC_COHORT_CREATE_UPDATE) {
    $form = new InputfileLoadform($url, array('localfile' => @$syncconfig->cohorts_filelocation));
} else {
    $form = new InputfileLoadform($url, array('localfile' => @$syncconfig->cohorts_coursebindingfilelocation));
}

$canprocess = false;

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sync/index.php'));
}

if ($data = $form->get_data()) {

    $syncconfig->simulate = @$data->simulate;

    if (!empty($data->uselocal)) {
        // Use the server side stored file.
        if ($action == SYNC_COHORT_CREATE_UPDATE) {
            $processedfile = $syncconfig->cohorts_filelocation;
        } else {
            $processedfile = $syncconfig->cohorts_coursebindingfilelocation;
        }
        $canprocess = true;
    } else {
        // Use the just uploaded file.

        if (!$manualfilerec = tool_sync_receive_file($data)) {
            $errormes = "Failed loading a file";
        } else {
            $processedfile = $manualfilerec->filename;
            $cohortsmanager = new \tool_sync\cohorts_sync_manager($action, $manualfilerec);
            $canprocess = true;
        }
    }
}
echo $OUTPUT->header();

if ($action == SYNC_COHORT_CREATE_UPDATE) {
    $cohortmgtmanual = get_string('cohortmgtmanual', 'tool_sync');
} else {
    $cohortmgtmanual = get_string('cohortbindingmanual', 'tool_sync');
}

echo $OUTPUT->heading_with_help(get_string('cohortmgtmanual', 'tool_sync'), 'cohortsync', 'tool_sync');

If (!empty($errormes)) {
    echo $OUTPUT->notification($errormes, 'notifyfailure');
    echo $renderer->print_run_again_button('cohorts', $action);
}

if ($canprocess) {

    $taskrunmsg = get_string('taskrunmsg', 'tool_sync', $processedfile);

    echo "<br/><fieldset><legend><strong>$cohortmgtmanual</strong></legend>";
    echo "<center>$taskrunmsg</center>";

    echo '<pre>';
    try {
        $cohortsmanager->cron($syncconfig);
        echo '</pre>';
    } catch (Exception $ex) {
        echo '</pre>';
        echo $OUTPUT->notification(get_string('processerror', 'tool_sync', $ex->getMessage()), 'notifyproblem');
    }

    echo $renderer->print_run_again_button('cohorts', $action);

    echo '</fieldset>';
} else {
    $formdata['action'] = $action;
    $form->set_data($formdata);
    $form->display();
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

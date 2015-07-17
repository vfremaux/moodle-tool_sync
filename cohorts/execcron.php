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
* @package tool
* @subpackage @sync
*
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/cohorts.class.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

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

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('cohortmgtmanual', 'tool_sync'), 'cohortsync', 'tool_sync');

echo '<pre>';
$cohortssmanager->cron($syncconfig);
echo '</pre>';
$address = @$syncconfig->cohorts_filelocation;

$cohortmgtmanual = get_string('cohortmgtmanual', 'tool_sync');
$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $address);

echo "<br/><fieldset><legend><strong>$cohortmgtmanual</strong></legend>";
echo "<center>$cronrunmsg</center>";
echo '</fieldset>';

// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

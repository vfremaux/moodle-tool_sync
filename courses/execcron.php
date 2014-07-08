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

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');

set_time_limit(1800);
raise_memory_limit('512M');

$systemcontext = context_system::instance();
$PAGE->set_context(null);

require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$url = $CFG->wwwroot.'/admin/tool/sync/courses/execcron.php';
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('coursesync', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('coursesync', 'tool_sync'), 'coursesync', 'tool_sync');

// execron do everything a cron will do
$syncconfig = get_config('tool_sync');
$coursesmanager = new course_sync_manager(null, SYNC_COURSE_CHECK | SYNC_COURSE_DELETE | SYNC_COURSE_CREATE);
$renderer = $PAGE->get_renderer('tool_sync');

echo $OUTPUT->heading(get_string('coursemanualsync', 'tool_sync'), 3);

$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_fileexistlocation);
echo "<center>$cronrunmsg</center>";

$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_filedeletelocation);
echo "<center>$cronrunmsg</center>";

$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $syncconfig->course_fileuploadlocation);
echo "<center>$cronrunmsg</center>";

echo $OUTPUT->heading(get_string('processresult', 'tool_sync'), 3);

echo '<pre>';
$coursesmanager->cron($syncconfig);
echo '</pre>';

echo $renderer->print_return_button();

echo $OUTPUT->footer();

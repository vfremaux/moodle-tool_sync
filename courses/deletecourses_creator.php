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
 * @author      Funck Thibaut
 * @package     tool_sync
 * @category    tool
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$syncconfig = get_config('tool_sync');

require_login();
require_capability('tool/sync:configure', $systemcontext);

$renderer = $PAGE->get_renderer('tool_sync');

$PAGE->requires->js('/admin/tool/sync/courses/js.js');

$selection = optional_param_array('selection', '', PARAM_TEXT);
if ($selection) {
    tool_sync_create_course_deletion_file($selection);
}

$url = new moodle_url('/admin/tool/sync/courses/deletecourses_creator.php');
$indexurl = new moodle_url('/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $indexurl);
$PAGE->navigation->add(get_string('builddeletefile', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('deletefilebuilder', 'tool_sync'));

echo $renderer->print_delete_course_creator($syncconfig);

echo $renderer->print_return_button();

echo $OUTPUT->footer();
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
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

// Security.

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();
require_capability('tool/sync:configure', $systemcontext);

$PAGE->requires->js('/admin/tool/sync/courses/js.js');

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$coursesmanager = new \tool_sync\course_sync_manager('', null);

$selection = optional_param_array('selection', '', PARAM_TEXT);
if ($selection) {
    $coursesmanager->create_course_reinitialisation_file($selection, $syncconfig);
}

$url = new moodle_url('/admin/tool/sync/courses/resetcourses_creator.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('buildresetfile', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('resetfilebuilder', 'tool_sync'));

echo $OUTPUT->heading(get_string('deletefilebuilder', 'tool_sync'));

echo $renderer->print_reset_course_creator($syncconfig);

echo $renderer->print_return_button();

echo $OUTPUT->footer();

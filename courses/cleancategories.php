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
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/cleancategories_form.php');

// Security.

require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_capability('tool/sync:configure', $systemcontext);

$url = new moodle_url('/admin/tool/sync/courses/cleancategories.php');
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), new moodle_url('/admin/tool/sync/index.php'));
$PAGE->navigation->add(get_string('cleancategories', 'tool_sync'), null);
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');
$form = new clean_categories_form($url);

$canprocess = false;

$report = '';
if ($data = $form->get_data()) {
    if (!empty($data->confirm)) {
        $report = tool_sync_erase_empty_categories($data->startcategory, @$data->ignoresubcategories, $foo);
    }
}

$startcat = null;
if (!empty($data->startcategory)) {
    $startcat = $DB->get_record('course_categories', array('id' => $data->startcategory));
}

$emptycats = tool_sync_get_empty_categories(@$data->startcategory, @$data->ignoresubcategories, $foo);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('cleancats', 'tool_sync'));

$form->display();

$startcatname = get_string('rootcategory', 'tool_sync');
if ($startcat) {
    $startcatname = $startcat->name;
}

if (!empty($report)) {
    echo '<pre>';
    echo $report;
    echo '</pre>';
}

echo $OUTPUT->heading(get_string('emptycats', 'tool_sync', $startcatname));

if ($emptycats) {
    echo '<code>';
    foreach ($emptycats as $ecat) {
        echo $ecat->name.'<br/>';
    }
    echo '</code>';
}

// Always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

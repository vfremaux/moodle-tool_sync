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
 * A moodle addon to quickly remove all empty categories and cleanup category tree
 *
 * @author Valery Fremaux (valery.fremaux@gmail.com);
 * @package tool_sync
 *
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');

// Security.

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

$coursemanager = new course_sync_manager('', null); // Do not trigger any command
$renderer = $PAGE->get_renderer('tool_sync');

$cleancatnamestr = get_string('cleancategories', 'tool_sync');

set_time_limit(300);

list($usec, $sec) = explode(' ', microtime());
$time_start = ((float)$usec + (float)$sec);
$url = $CFG->wwwroot.'/admin/tool/sync/courses/clearemptycategories.php';
$PAGE->set_url($url);
$PAGE->navigation->add($cleancatnamestr);
$PAGE->set_title("$site->shortname: $cleancatnamestr");
$PAGE->set_heading($site->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('cleancategories', 'tool_sync'), 'cleancategories', 'tool_sync');

// Page controller

if (!isset($_POST['ids'])) {

    echo '<center>';
    echo '<table width="70%">';
    $path = '';
    tool_sync_scan_empty_categories(0, $catids, $path);
    echo '</table>';

    if (!empty($catids)) {
        $deleteids = implode(',', $catids);

        echo '<form method="post" action="clearemptycategories.php">';
        echo '<input type="hidden" name="ids" value="'.$deleteids.'">';
        echo '<input type="submit" value="'.get_string('confirm', 'tool_sync').'">';
        echo '</form>';
    } else if (!isset($_POST['cancel'])) {
        echo $OUTPUT->notification(get_string('nothingtodelete', 'tool_sync'), 'notifyproblem');
    }
    echo '</center>';
} else {
    // We got passed a list of id's to delete... they pressed the confirm button. Go ahead and delete the courses.

    $ids = optional_param('ids', '', PARAM_TEXT);
    if (!empty($ids)) {
        $coursemanager->clear_empty_categories($ids);
    }
}

// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

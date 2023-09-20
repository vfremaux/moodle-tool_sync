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

require('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/sync/tool_form.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

// Security.

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('tool/sync:configure', $context);

// Hack : confirm plugin version. Why in the hell it disapears from DB ?
// tool_sync_check_repair_plugin_version();
// OBSOLETE

tool_sync_capture_input_files(true);

if (! $site = get_site()) {
    print_error('errornosite', 'tool_sync');
}
if (!$adminuser = get_admin()) {
    print_error('errornoadmin', 'tool_sync');
}

$syncconfig = get_config('tool_sync');

if (!isset($syncconfig->encoding)) {
    set_config('encoding', 'UTF-8', 'tool_sync');
    $syncconfig->encoding = 'UTF-8';
}

if (!isset($syncconfig->csvseparator)) {
    set_config('csvseparator', ';', 'tool_sync');
    $syncconfig->csvseparator = ';';
}

if (!isset($syncconfig->userpictures_userfield)) {
    set_config('userpictures_userfield', 1, 'tool_sync');
    $syncconfig->userpictures_userfield = 1;
}

if (!isset($syncconfig->userpictures_fileprefix)) {
    set_config('userpictures_fileprefix', 'userpictures_', 'tool_sync');
    $syncconfig->userpictures_fileprefix = 1;
}

if (!isset($syncconfig->userpictures_forcedeletion)) {
    set_config('userpictures_forcedeletion', 1, 'tool_sync');
    $syncconfig->userpictures_forcedeletion = 1;
}

if (!isset($syncconfig->userpictures_overwrite)) {
    set_config('userpictures_overwrite', 1, 'tool_sync');
    $syncconfig->userpictures_overwrite = 1;
}

// If data submitted, then process and store.

$form = new ToolForm();

if ($data = $form->get_data()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    // Erase all configs but not version !
    $DB->delete_records_select('config_plugins', " plugin = 'tool_sync' AND name != 'version' ");

    foreach ($data as $key => $value) {
        if (strpos($key, '/') > 0) {
            if ($key != 'version') {
                // Be sure to protect version.
                // Is a configuration key.
                list($plugin, $datakey) = explode('/', $key);
                set_config($datakey, $value, 'tool_sync');
                // Refresh the currently loaded config for reflecting in form.
                $syncconfig->$datakey = $value;
            }
        }
    }

    redirect(new moodle_url('/admin/tool/sync/index.php', array('resultmessage' => get_string('changessaved'))));
}

// Print current courses type description.

$url = new moodle_url('/admin/tool/sync/index.php');
$PAGE->set_url($url);
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('toolindex', 'tool_sync'));
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('admin-index');

echo $OUTPUT->header();

$config = get_config('tool_sync');

echo $OUTPUT->heading(get_string('title', 'tool_sync'));

if ($message = optional_param('resultmessage', '', PARAM_TEXT)) {
    echo $OUTPUT->box_start('informationbox');
    echo $message;
    echo $OUTPUT->box_end();
}

$formdata = tool_sync_config_add_sync_prefix($syncconfig);

$form->set_data($formdata);
$form->display();

echo $OUTPUT->footer();

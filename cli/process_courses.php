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
 * This script launches the course operations from command line CLI script calls.
 *
 * @package tool_sync
 * @author - Funck Thibaut
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose'           => false,
        'help'              => false,
        'simulate'          => false,
        'host'              => false,
        'action'            => false,
        'file'              => false,
    ),
    array(
        'h' => 'help',
        'f' => 'file',
        'v' => 'verbose',
        's' => 'simulate',
        'a' => 'action',
        'C' => 'catid',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unkown option $unrecognized\n");
}

if ($options['help']) {
    $help =
        "Course bulk operations.

        Options:
        -v, --verbose               Provides lot of output
        -h, --help          Print out this help
        -s, --simulate      Get all data for simulation but will NOT process any writing in database.
        -f, --file          the operation command file as an absolute path in the system. If not given, will run the active file in sync configuration.
        -a, --action        The course operation (check, reset, delete, create).
        -C, --catid         The categoryidentifier mode (idnumber, idname).
        -H, --host          Set the host (physical or virtual) to operate on.

         \n"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    include(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

// Here can real processing start.

if (empty($options['file'])) {
    echo("No file given. Using configuration file....\n");
} else {
    if (!file_exists($options['file'])) {
        die("File not found. Aborting....\n");
    }
}

switch ($options['action']) {
    case 'check':
        $action = SYNC_COURSE_CHECK;
        break;

    case 'create':
        $action = SYNC_COURSE_CREATE;
        break;

    case 'delete':
        $action = SYNC_COURSE_DELETE;
        break;

    case 'reset':
        die("Future implementation. Needs some reshape in tool organization\n");
        $action = SYNC_COURSE_RESET;
        break;
}

$syncconfig = get_config('tool_sync');

// Integrates file in file system within tool_sync file area.
if (!empty($options['file'])) {
    $fs = get_file_storage();
    $filerec = new Stdclass();
    $contextid = context_system::instance()->id;
    $filerec->contextid = $contextid;
    $filerec->component = 'tool_sync';
    $filerec->filearea = 'syncfiles';
    $filerec->itemid = 0;
    $filerec->filepath = '/';
    $filerec->filename = basename($options['file']);

    // Purge eventual previous file.
    if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                 $filerec->filepath, $filerec->filename)) {
        $oldfile->delete();
    }

    $fs->create_file_from_pathname($filerec, $options['file']);
    $manager = new \tool_sync\course_sync_manager($action, $filerec);
} else {
    if ($options['action'] == 'check') {
        $processedfile = $syncconfig->courses_fileexsitlocation;
    } else if ($options['action'] == 'delete') {
        $processedfile = $syncconfig->courses_filedeletelocation;
    } else if ($options['action'] == 'create') {
        $processedfile = $syncconfig->courses_fileuploadlocation;
    }
    $manager = new \tool_sync\course_sync_manager($action);
}

global $USER;
$USER = get_admin();

if (!empty($options['catid'])) {
    $syncconfig->courses_coursecategoryidentifier = $options['catid'];
}
$manager->cron($syncconfig);

if ($options['verbose']) {
    echo $CFG->tool_sync_courselog;
}
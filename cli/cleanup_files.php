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
        'reports'           => false,
        'host'              => false,
        'archives'          => false,
        'trybacks'          => false,
        'before'            => false,
    ),
    array(
        'h' => 'help',
        'b' => 'before',
        'v' => 'verbose',
        'a' => 'archives',
        'r' => 'reports',
        't' => 'trybacks',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unkown option $unrecognized\n");
}

if ($options['help']) {
    $help =
"
Massive cleanup in admin sync tool filearea.

        Options:
        -v, --verbose       Provides lot of output
        -h, --help          Print out this help
        -b, --before        If given (unix timestamp) will delete file before this date.
        -a, --archives      Delete all archive files
        -r, --reports       Delete all reports
        -t, --trybacks      Delete all trybacks in tool sync file area root.
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

// Here can real processing start.
$beforeclause = '';
if (empty($options['before'])) {
    $beforeclause = " AND timecreated < '{$before}' ";
}

if (!empty($options['archives'])) {
    $fs = get_file_storage();

    $select = ' component = ? AND contextid = ? AND filearea = ? AND itemid = 0 AND filepath LIKE ? '.$beforeclause;
    $params = [
        'tool_sync',
        context_system::instance()->id,
        'filearea' => 'syncfiles',
        '/archives/%'
    ];

    if ($todelete = $DB->get_records_select('files', $select, $params, 'id, filepath, filename')) {
        foreach ($todelete as $td) {
            $todelete = $fs->get_file_by_id($td->id);
            $todelete->delete();
            if (!empty($options['verbose'])) {
                echo "Removing file {$d->filepath}{$td->filename}}";
            }
        }
    }
}

if (!empty($options['reports'])) {
    $fs = get_file_storage();

    $select = ' component = ? AND contextid = ? AND filearea = ? AND itemid = 0 AND filepath LIKE ? '.$beforeclause;
    $params = [
        'tool_sync',
        context_system::instance()->id,
        'filearea' => 'syncfiles',
        '/reports/%'
    ];

    if ($todelete = $DB->get_records_select('files', $select, $params, 'id, filepath, filename')) {
        foreach ($todelete as $td) {
            $todelete = $fs->get_file_by_id($td->id);
            $todelete->delete();
            if (!empty($options['verbose'])) {
                echo "Removing file {$d->filepath}{$td->filename}}";
            }
        }
    }
}

if (!empty($options['trybacks'])) {
    $fs = get_file_storage();

    $select = ' component = ? AND contextid = ? AND filearea = ? AND itemid = 0 AND filepath  = ? AND filename LIKE ? '.$beforeclause;
    $params = [
        'tool_sync',
        context_system::instance()->id,
        'filearea' => 'syncfiles',
        '/',
        '%_tryback_%'
    ];

    if ($todelete = $DB->get_records_select('files', $select, $params, 'id, filepath, filename')) {
        foreach ($todelete as $td) {
            $todelete = $fs->get_file_by_id($td->id);
            $todelete->delete();
            if (!empty($options['verbose'])) {
                echo "Removing file {$d->filepath}{$td->filename}}";
            }
        }
    }
}

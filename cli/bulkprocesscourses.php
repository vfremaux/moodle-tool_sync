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

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'action'           => false,
        'file'             => false,
        'logroot'          => false,
        'verbose'          => false,
        'simulate'         => false,
        'fullstop'         => false,
        'withmaster'         => false,
    ),
    array(
        'h' => 'help',
        'a' => 'action',
        'f' => 'file',
        'l' => 'logroot',
        's' => 'simulate',
        'v' => 'verbose',
        'x' => 'fullstop',
        'm' => 'withmaster',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
File driven course operations

    Options:
    -h, --help              Print out this help
    -a, --action            Action to perform (create, delete, reset, check)
    -f, --file              File to process
    -l, --logroot           Root for the log output
    -x, --fullstop          Stops the processing on first error
    -v, --verbose           More output
    -s, --simulate          Simulates the operation
    -m, --withmaster        If present will also run the script on master moodle.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$logroot = '';
if (!empty($options['logroot'])) {
    $logroot = '--logroot='.$options['logroot'];
}

$verbose = '';
if (!empty($options['verbose'])) {
    $verbose = '--verbose='.$options['verbose'];
}

$simulate = '';
if (!empty($options['simulate'])) {
    $simulate = '--simulate='.$options['simulate'];
}

if (empty($options['action'])) {
    die ("Action cannot be empty\n");
}
$action = '--action='.$options['action'];

if (empty($options['file'])) {
    die ("A file must be provides \n");
}
$file = '--file='.$options['file'];

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting processing course operation files....\n";

if (!empty($options['withmaster'])) {
    $workercmd = "php {$CFG->dirroot}/admin/tool/sync/cli/process_courses.php ";
    $workercmd .= " {$action} {$file} {$verbose} {$simulate} {$logroot}";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);

    if (!empty($output)) {
        echo implode("\n", $output);
    }
    echo "\n";

    if ($return) {
        if (!empty($option['fullstop'])) {
            die("Master Worker ended with error\n");
        } else {
            mtrace("Master Worker ended with error\n");
        }
    }
}


$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/admin/tool/sync/cli/process_courses.php --host=\"{$h->vhostname}\" ";
    $workercmd .= " {$action} {$file} {$verbose} {$simulate} {$logroot}";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);

    if (!empty($output)) {
        echo implode("\n", $output);
    }
    echo "\n";

    if ($return) {
        if (!empty($option['fullstop'])) {
            die("Worker ended with error\n");
        } else {
            mtrace("Worker ended with error\n");
        }
    }
}

echo "All done.";

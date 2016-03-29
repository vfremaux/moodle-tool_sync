<?php

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose'           => false,
        'help'              => false,
        'host'              => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "User Picture sum generation.

        Options:
        -h, --help          Print out this help
        -v, --verbose       Print processing output
        -H, --host          Set the host (physical or virtual) to operate on.

         \n"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

$CLI_VMOODLE_PRECHECK = false;

// Replay full config whenever. If vmoodle switch is armed, will switch now config.
unset($CFG);
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot."\n");

print_r($CFG);

$CFG->libdir = $CFG->dirroot.'/lib';
$CFG->tempdir = $CFG->dataroot.'/tmp';

require_once($CFG->dirroot.'/admin/tool/sync/userpictures/lib.php');
// Here can real processing start
update_all_user_picture_hashes($options['verbose']);
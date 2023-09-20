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
 * This script transbases cohorts from another moodle using database to database
 * processing. Databases need to be on the same DB server and accessible through the
 * same Moodle DB connexion.
 *
 * @package tool_sync
 * @author - Valery Fremaux
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
$CFG->debugdeveloper = $USER->id;
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose'           => false,
        'help'              => false,
        'simulate'          => false,
        'host'              => false,
        'remotedb'          => false,
        'makemap'               => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        's' => 'simulate',
        'r' => 'remotedb',
        'm' => 'makemap',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unkown option $unrecognized\n");
}

if ($options['help']) {
    $help =
        "Transbases cohorts from another moodle and accssorily builds a cohort mapping for the remote site,
        that will help importing cohort enrolment based courses.

        Options:
        -v, --verbose               Provides lot of output
        -h, --help          Print out this help
        -s, --simulate      Get all data for simulation but will NOT process any writing in database.
        -r, --remotedb      The remote moodle dbname where to get cohorts from.
        -m, --makemap       If set, registers mapping in DB.
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

// Here can real processing start.

global $USER;
$USER = get_admin();

if (empty($options['remotedb'])) {
    die("No remote db to import");
}

$remotedb = $options['remotedb'];

// Get all remote cohorts and memberships, using 'username' key
$sql = "
    SELECT
        CONCAT(c.id, '-', u.id) as pkey,
        c.*,
        cm.timeadded,
        u.username
    FROM
        {$remotedb}.{$CFG->prefix}cohort c
    LEFT JOIN
        {$remotedb}.{$CFG->prefix}cohort_members cm
    ON
        cm.cohortid = c.id
    LEFT JOIN
        {$remotedb}.{$CFG->prefix}user u
    ON
        cm.userid = u.id
";

$remotes = $DB->get_records_sql($sql);

$dbman = $DB->get_manager();
if (!empty($options['makemap'])) {
    $table = new xmldb_table('cohort_remote_mapping');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('remotewwwroot', XMLDB_TYPE_CHAR, '255', null, null, null, null);
    $table->add_field('remoteid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    $table->add_field('localid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table local_shop.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('ix_unique_remoteid', XMLDB_KEY_UNIQUE, array('remotewwwroot', 'remoteid'));

    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Get the most probable suitable record that gives us the remote wwwroot.
    $wwwrootsql = "
        SELECT
            wwwroot
        FROM
            {$remotedb}.{$CFG->prefix}mnet_host
        WHERE
            name = ''
    ";
    $remotewwwroot = $DB->get_field_sql($wwwrootsql);
    $remotewwwroot = preg_replace('#https?://#', '', $remotewwwroot);

    // Purge mapping table for this host.
    echo "Deleting all remote mappings for $remotewwwroot \n";
    $DB->delete_records('cohort_remote_mapping', ['remotewwwroot' => $remotewwwroot]);
}

if ($remotes) {
    foreach ($remotes as $cu) {

        // save username and timeadded to cohort for binding. Both may be null on empty cohort.
        $remoteid = $cu->id;
        $username = $cu->username;
        $timeadded = $cu->timeadded;

        // Make cohort if not exists
        unset($cu->username);
        unset($cu->id);

        if ($cu->contextid > 1) {
            // At the moment, cannot process non site level cohorts.
            continue;
        }

        // Match by idnumber id possible.
        if (!empty($cu->idnumber)) {
            $oldrec = $DB->get_record('cohort', ['idnumber' => $cu->idnumber]);
        } else {
            // else match by bame.
            $oldrec = $DB->get_record('cohort', ['name' => $cu->name]);
        }

        if ($oldrec) {
            // We have some old record registered, update it.
            $cu->id = $oldrec->id;
            if (!empty($options['verbose'])) {
                echo "Updating cohort $cu->name ($cu->id) [$cu->idnumber]\n";
            }
            $DB->update_record('cohort', $cu);
        } else {
            // Make new one.
            $cu->id = $DB->insert_record('cohort', $cu);
            if (!empty($options['verbose'])) {
                echo "Adding cohort $cu->name ($cu->id) [$cu->idnumber]\n";
            }
        }

        if (!empty($options['makemap'])) {
            // Register in remote cohort mapping.
            $mapping = new Stdclass;
            $mapping->remotewwwroot = $remotewwwroot;
            $mapping->localid = $cu->id;
            $mapping->remoteid = $remoteid;
            if (!$DB->record_exists('cohort_remote_mapping', ['remotewwwroot' => $remotewwwroot, 'remoteid' => $remoteid])) {
                $DB->insert_record('cohort_remote_mapping', $mapping);
            }
        }

        if (!empty($username)) {
            // Finally process the membership
            $userid = $DB->get_field('user', 'id', ['username' => $username]);
            if (!$userid) {
                // If completely unkown this side, forget it.
                continue;
            }

            if (!$oldrec = $DB->get_record('cohort_members', ['cohortid' => $cu->id, 'userid' => $userid])) {
                $chm = new StdClass;
                $chm->cohortid = $cu->id;
                $chm->userid = $userid;
                $chm->timeadded = $timeadded;
                if (!empty($options['verbose'])) {
                    echo "Adding cohort membership in $cu->name ($cu->id) for $username ($userid)\n";
                }
                $DB->insert_record('cohort_members', $chm);
            }
        }
    }
}

echo "Done.\n";

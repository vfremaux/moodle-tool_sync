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
        'removemanual'      => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        's' => 'simulate',
        'r' => 'remotedb',
        'M' => 'removemanual',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unkown option $unrecognized\n");
}

if ($options['help']) {
    $help =
        "Transbases cohorts sync user enrolments from another moodle.

        Options:
        -v, --verbose       Provides lot of output
        -h, --help          Print out this help
        -s, --simulate      Get all data for simulation but will NOT process any writing in database.
        -r, --remotedb      The remote moodle dbname where to get cohorts from.
        -M, --removemanual  If set, this option will provoque removing of eventual manual enrols when a cohort enrol is set for the user.
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

// Replay full config whenever. If vmoodle switch is armed, will switch now config.
if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    include(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

// Here can real processing start.

global $USER;
$USER = get_admin();
if (empty($USER)) {
    $USER = $DB->get_record('user', ['username' => 'admin']);
}

if (empty($options['remotedb'])) {
    die("No remote db to import");
}

$remotedb = $options['remotedb'];

// Get all remote cohorts and memberships, using 'username' key
$sql = "
    SELECT
        CONCAT(e.id, '-', u.id) as pkey,
        e.*,
        c.shortname,
        u.username,
        r.shortname as rolename,
        ch.idnumber as chidnumber,
        ch.name as chname,
        ue.status as uestatus,
        ue.timestart as uetimestart,
        ue.timeend as uetimeend,
        ue.timecreated as uetimecreated,
        ue.timecreated as uestatus
    FROM
        {$remotedb}.{$CFG->prefix}role r,
        {$remotedb}.{$CFG->prefix}course c,
        {$remotedb}.{$CFG->prefix}cohort ch,
        {$remotedb}.{$CFG->prefix}enrol e
    LEFT JOIN
        {$remotedb}.{$CFG->prefix}user_enrolments ue
    ON
        e.id = ue.enrolid
    LEFT JOIN
        {$remotedb}.{$CFG->prefix}user u
    ON
        ue.userid = u.id
    WHERE
        e.courseid = c.id AND
        e.enrol = 'cohort' AND
        e.roleid = r.id AND
        e.customint1 = ch.id
";

$remotes = $DB->get_records_sql($sql);

if ($remotes) {

    $enrolplugin = enrol_get_plugin('cohort');
    $manualenrolplugin = enrol_get_plugin('manual');

    foreach ($remotes as $ue) {
        // save username and timeadded to cohort for binding. Both may be null on empty cohort.
        $username = $ue->username;
        $coursename = $ue->shortname;
        $rolename = $ue->rolename;
        $cohortidnumber = $ue->chidnumber;
        $cohortname = $ue->chname;
        $rolename = $ue->rolename;
        $uetimecreated = $ue->uetimecreated;
        $uetimestart = $ue->uetimestart;
        $uetimeend = $ue->uetimeend;
        $uestatus = $ue->uestatus;

        unset($ue->username);
        unset($ue->rolename);
        unset($ue->shortname);
        unset($ue->uetimecreated);
        unset($ue->uetimestart);
        unset($ue->uetimeend);
        unset($ue->pkey);

        // Match by idnumber id possible.
        if (!empty($cohortidnumber)) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $cohortidnumber]);
        } else {
            // else match by bame.
            $cohort = $DB->get_record('cohort', ['name' => $cohortname]);
        }

        if (!$cohort) {
            if (!empty($options['verbose'])) {
                echo "Missing cohort [{$ue->chidnumber}] $ue->chname. Perhaps you need to transbase cohorts first. Skipping\n";
            }
            continue;
        }
        $ue->customint1 = $cohort->id; // Remap local cohort id.

        // Get local course
        $course = $DB->get_record('course', ['shortname' => $coursename]);
        if (!$course) {
            // If completely unkown this side, forget it.
            if (!empty($options['verbose'])) {
                echo "Missing course [{$coursename}]. Perhaps you need to import courses first. Skipping\n";
            }
            continue;
        }
        $ue->courseid = $course->id; // Remap local course id.

        // Get local role
        if (!$role = $DB->get_record('role', ['shortname' => $rolename])) {
            if (!empty($options['verbose'])) {
                echo "Unknown role [{$rolename}]. Role might be custom or having been renamed. Skipping\n";
            }
            continue;
        }
        $ue->roleid = $role->id;

        // Make enrol method
        if ($oldrec = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'cohort', 'customint1' => $cohort->id])) {
            $ue->id = $oldrec->id;
            if (!empty($options['verbose'])) {
                echo "Updating enrol record for cohort {$cohort->name} ({$cohort->id}) in course \"{$course->shortname}\" ({$course->id}) .\n";
            }
            $DB->update_record('enrol', $ue);
        } else {
            if (!empty($options['verbose'])) {
                echo "Adding enrol record for cohort {$cohort->name} ({$cohort->id}) in course \"{$course->shortname}\" ({$course->id}) .\n";
            }
            $ue->id = $DB->insert_record('enrol', $ue);
        }

        // Process to enrolment on the method.
        if (!empty($username)) {
            // Finally process the membership
            $user = $DB->get_record('user', ['username' => $username]);
            if (!$user->id) {
                // If completely unkown this side, forget it.
                if (!empty($options['verbose'])) {
                    echo "Unknown user \"{$username}\" . Skipping\n";
                }
                continue;
            }

            // We have the user. Enrol it
            $enrolplugin->enrol_user($ue, $user->id, $role->id, $uetimestart, $uetimeend, $uestatus);
            if (!empty($options['verbose'])) {
                echo "Enrolling user {$user->username} ({$user->id}) in course \"{$course->shortname}\" ({$course->id}) .\n";
            }
            // Get the eventual manual enrol out, if a cohort enrol is retrieved.
            if (!empty($options['removemanual'])) {
                $manualinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
                $manualenrolplugin->unenrol_user($manualinstance, $user);
            }
        }
    }
}

echo "Done.\n";

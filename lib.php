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
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrols/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');

define('SYNC_COURSE_CHECK', 0x001);
define('SYNC_COURSE_CREATE', 0x002);
define('SYNC_COURSE_DELETE', 0x004);
define('SYNC_COURSE_RESET', 0x008);
define('SYNC_COURSE_METAS', 0x010);
define('SYNC_COURSE_CREATE_DELETE', 0x006);

define('SYNC_COHORT_CREATE_UPDATE', 0x1001);
define('SYNC_COHORT_BIND_COURSES', 0x1002);
define('SYNC_COURSE_GROUPS', 0x1004);
define('SYNC_GROUP_MEMBERS', 0x1008);

/**
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function tool_sync_supports_feature($feature) {
    static $supports;

    $config = get_config('tool_sync');

    if (!isset($supports)) {
        $supports = array(
            'pro' => array(
                'api' => array('config', 'process', 'commit', 'deploy'),
                'fileloading' => array('remote', 'wildcard'),
            ),
            'community' => array(
                'api' => array(),
            ),
        );
        $prefer = array();
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    if (in_array($feat, $supports['community'])) {
        if (in_array($subfeat, $supports['community'][$feat])) {
            // If community exists, default path points community code.
            if (isset($prefer[$feat][$subfeat])) {
                // Configuration tells which location to prefer if explicit.
                $versionkey = $prefer[$feat][$subfeat];
            } else {
                $versionkey = 'community';
            }
        }
    }

    return $versionkey;
}

/**
 * prints a report to a log stream and output ir also to screen if required
 *
 */
function tool_sync_report(&$report, $message, $onscreen = true) {
    if (empty($report)) {
        $report = '';
    }
    if ($onscreen) {
        mtrace($message);
    }
    $report .= $message."\n";
}

/**
 * Check a CSV input line format for empty or commented lines
 * Ensures compatbility to UTF-8 BOM or unBOM formats
 */
function tool_sync_is_empty_line_or_format(&$text, $resetfirst = false) {
    static $textlib;
    static $first = true;

    $config = get_config('tool_sync');

    // We may have a risk the BOM is present on first line.
    if ($resetfirst) {
        $first = true;
    }

    if (!isset($textlib)) {
        $textlib = new core_text();
    }

    if ($first && @$config->encoding == 'UTF-8') {
        $text = $textlib->trim_utf8_bom($text);
        $first = false;
    }

    $checktext = preg_replace("/\r/", '', $text);
    $checktext = preg_replace("/\n/", '', $checktext);

    if (@$config->encoding != 'UTF-8') {
        $checktext = utf8_encode($checktext);
    }

    return preg_match('/^$/', $checktext) || preg_match('/^(\(|\[|-|#|\/| )/', $checktext);
}

/**
 * Checks if a text (csv line) do contain an unwanted csv separator. this helps
 * to detect an eventually non format matching file.
 * @param string $text a raw line
 * @return bool true if no other separator found.
 */
function tool_sync_check_separator($text) {

    $config = get_config('tool_sync');

    $seps = array("\t" => "\t", ',' => ',', ';' => ';', ':' => ':');
    unset($seps[$config->csvseparator]);
    foreach (array_keys($seps) as $sep) {
        if (strpos($text, $sep) !== false) {
            return false;
        }
    }
    return true;
}

function tool_sync_validate_headers($text, $required, $processor) {

    $config = get_config('tool_sync');

    $headers = explode($config->csvseparator, $text);

    // Check for valid field names.
    array_walk($headers, 'trim_array_values');

    foreach ($headers as $h) {
        if (empty($h)) {
            $processor->report(get_string('errornullcsvheader', 'tool_sync'));
            return;
        }
    }

    // Check for required fields.
    foreach ($required as $key => $value) {
        if ($value != true) {
            $processor->report(get_string('fieldrequired', 'error', $key));
            return;
        }
    }

    return $headers;
}

/**
 * Get the primary id of the record, whatever the identifier provided.
 * @param string $table
 * @param string $source the identifier fieldname
 * @param string $identifier
 * @return int the id.
 */
function tool_sync_get_internal_id($table, $source, $identifier) {
    global $DB;

    if ($source == 'id') {
        return $identifier;
    }

    return $DB->get_field($table, 'id', array($source => $identifier));
}

/**
 * Get course and role assignations summary
 * TODO : Rework for PostGre compatibility.
 */
function tool_sync_get_all_courses($orderby = 'shortname') {
    global $DB;

    $sql = "
        SELECT
            CASE WHEN ass.roleid IS NOT NULL THEN CONCAT( c.id, '_', ass.roleid ) ELSE CONCAT( c.id, '_', '0' ) END AS recid,
            c.id,
            c.shortname,
            c.fullname,
            c.idnumber,
            count( DISTINCT ass.userid ) AS people,
            ass.rolename
        FROM
            {course} c
        LEFT JOIN
            (SELECT
                co.instanceid,
                ra.userid,
                r.name as rolename,
                r.id as roleid
             FROM
                {context} co,
                {role_assignments} ra,
                {role} r
             WHERE
                co.contextlevel = 50 AND
                co.id = ra.contextid AND
                ra.roleid = r.id) ass
        ON
            ass.instanceid = c.id
        GROUP BY
            recid
        ORDER BY
            c.$orderby
    ";
    $results = $DB->get_records_sql($sql);
    return $results;
}

/**
 * Standard cron function
 */
function tool_sync_cron() {
    // No more cron action. Everything is handled using scheduled tasks.
}

/**
 * parses a YYYY-MM-DD hh:ii:ss
 */
function tool_sync_parsetime($time, $default = 0) {

    if (preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s+(\d\d):(\d\d):(\d\d)/', $time, $matches)) {
        $y = $matches[1];
        $m = $matches[2];
        $d = $matches[3];
        $h = $matches[4];
        $i = $matches[5];
        $s = $matches[6];
        return mktime($h , $i, $s, $m, $d, $y);
    } else {
        return $default;
    }
}

/**
 * Captures input files from a system administrator accessible location
 * and store them into tool_sync filearea
 * Synchronisation checks for a lock.txt file NOT being present. A lock.txt
 * file is written as weak semaphore process. lock.txt signal will avoid
 * twice concurrent execution of file retrieval.
 * the retrieval is sensible to a alock.txt external lock written by the remote side
 * when feeding remotely the files.
 */
function tool_sync_capture_input_files($interactive = false) {
    global $CFG;

    // Ensures input directory exists.
    $syncinputdir = $CFG->dataroot.'/sync';
    if (!is_dir($syncinputdir)) {
        mkdir($syncinputdir, 0777);
    }

    $lockfile = $CFG->dataroot.'/sync/alock.txt';

    if (file_exists($lockfile)) {
        $fileinfo = stat($lockfile);
        if ($fileinfo['timecreated'] < (time() - HOURSEC * 3)) {
            // This is a too old file. May denote a remote feeder issue. Notify admin.
            if ($interactive) {
                mtrace('Too old write lock file. Resuming sync input capture.');
            } else {
                $subject = $SITE->shortname." : Too old write lock file.";
                email_to_user(get_admin(), get_admin(), $subject, 'Possible remote writer process issue.');
            }
        }
        return;
    }

    $readlockfile = $CFG->dataroot.'/sync/lock.txt';
    if (file_exists($readlockfile)) {
        $fileinfo = stat($lockfile);
        if ($fileinfo['timecreated'] < (time() - HOURSEC * 3)) {
            // This is a too old file. May denote a remote feeder issue. Notify admin.
            if ($interactive) {
                mtrace('Too old read lock file. this miht affect remote end, but continue capture.');
            } else {
                email_to_user(get_admin(), get_admin(), $SITE->shortname." : Too old read lock file.",
                'Possible local sync process issue.');
            }
        }
    }

    if ($f = fopen($readlockfile, 'w')) {
        fputs($f, time());
        fclose($f);
    } else {
        // Something wrong in sync input dir. Notify admin.
        if ($interactive) {
            mtrace('Could not create readlock file. Possible severe issue in storage. Resuming sync input capture.');
        } else {
            email_to_user(get_admin(), get_admin(), $SITE->shortname." : Could not create readlock file.",
            'Possible local sync process issue.');
        }
        return;
    }

    $d = opendir($syncinputdir);

    $fs = get_file_storage();

    while ($entry = readdir($d)) {
        if (preg_match('/^\./', $entry)) {
            continue;
        }

        // Ignore dirs. Supposed to be a flat storage.
        if (is_dir($syncinputdir.'/'.$entry)) {
            continue;
        }

        // Forget any locking file.
        if (preg_match('/lock/', $entry)) {
            continue;
        }

        // Forget any file starting with '_' (could be an output file).
        if (preg_match('/^_/', $entry)) {
            continue;
        }

        $filerec = new StdClass();
        $filerec->contextid = context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = $entry;

        // Delete previous version and avoid file collision.
        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        $fs->create_file_from_pathname($filerec, $syncinputdir.'/'.$entry);
        @unlink($syncinputdir.'/'.$entry);
    }

    closedir($d);

    @unlink($readlockfile);
}

/**
 * TODO write notification code
 */
function sync_notify_new_user_password($user, $value) {
    global $SITE, $USER;

    $subject = get_string('passwordnotification', 'tool_sync', $SITE->fullname);
    $content = get_string('passwordnotification_tpl', 'tool_sync', $value);
    email_to_user($user, $USER, $subject, $content);
}

function trim_array_values(&$e) {
    $e = trim($e);
}

function tool_sync_get_course_identifier($course, $forfile, $syncconfig) {
    $cid = false;
    switch (0 + @$syncconfig->$forfile) {
        case 0 :
            $cid = $course->idnumber;
            break;
        case 1 :
            $cid = $course->shortname;
            break;
        case 2 :
            $cid = $course->id;
            break;
    }
    return $cid;
}

/**
 *
 */
function tool_sync_receive_file($data) {
    global $USER;

    $usercontext = context_user::instance($USER->id);

    $fs = get_file_storage();

    if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

        $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);
        $uploadedfile = array_pop($areafiles);

        $manualfilerec = new StdClass();
        $manualfilerec->contextid = $usercontext->id;
        $manualfilerec->component = 'user';
        $manualfilerec->filearea = 'draft';
        $manualfilerec->itemid = $data->inputfile;
        $manualfilerec->filepath = $uploadedfile->get_filepath();
        $manualfilerec->filename = $uploadedfile->get_filename();
        return $manualfilerec;
    }
    return false;
}

/**
 * Reads a line in a stream converting to utf8 if necessary
 * @param resource $filereader the opened stream
 * @param int $length max length of read
 * @param objectref $config the surrounding configuration
 * @return a string or false if no more data
 */
function tool_sync_read($filereader, $length, &$config) {
    $input = fgets($filereader, $length);

    if (@$config->encoding != 'UTF-8') {
        return utf8_encode($input);
    }
    return $input;
}

/**
 * Extracts, filters and combine values to headers into an associative array.
 *
 * @return a keyed record as an associative array.
 */
function tool_sync_extract($headers, $line, $syncconfig) {

    $values = explode($syncconfig->csvseparator, $line);

    // Filter, clean values.
    $filtered = array();
    foreach ($values as $val) {
        $filt = trim($val);
        $filt = preg_replace('/^"/', '', $filt);
        $filt = preg_replace('/"$/', '', $filt);
        $filt = preg_replace('/\n$/s', '', $filt);
        $filtered[] = $filt;
    }

    $record = array_combine($headers, $filtered);

    return $record;
}

/**
 * Remove all users (or one user) from all groups in course
 * Add component protection handling.
 *
 * @param int $courseid
 * @param int $userid 0 means all users
 * @param bool $unused - formerly $showfeedback, is no longer used.
 * @return bool success
 * @seer /groups/lib.php groups_delete_group_members();
 */
function tool_sync_delete_group_members($courseid, $userid=0, $unused=false, $component = null) {
    global $DB;

    // Get the users in the course which are in a group.
    $sql = "SELECT gm.id as gmid, gm.userid, g.*
              FROM {groups_members} gm
        INNER JOIN {groups} g
                ON gm.groupid = g.id
             WHERE g.courseid = :courseid";
    $params = array();
    $params['courseid'] = $courseid;
    // Check if we want to delete a specific user.
    if ($userid) {
        $sql .= " AND gm.userid = :userid";
        $params['userid'] = $userid;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $usergroup) {
        tool_sync_group_remove_member($usergroup, $usergroup->userid, $component);
    }
    $rs->close();

    // TODO MDL-41312 Remove events_trigger_legacy('groups_members_removed').
    // This event is kept here for backwards compatibility, because it cannot be
    // translated to a new event as it is wrong.
    $eventdata = new stdClass();
    $eventdata->courseid = $courseid;
    $eventdata->userid   = $userid;
    events_trigger_legacy('groups_members_removed', $eventdata);

    return true;
}

/**
 * Deletes the link between the specified user and group.
 * Adds component handling.
 *
 * @param mixed $grouporid  The group id or group object
 * @param mixed $userorid   The user id or user object
 * @return bool True if deletion was successful, false otherwise
 */
function tool_sync_group_remove_member($grouporid, $userorid, $component = null) {
    global $DB;

    if (is_object($userorid)) {
        $userid = $userorid->id;
    } else {
        $userid = $userorid;
    }

    if (is_object($grouporid)) {
        $groupid = $grouporid->id;
        $group   = $grouporid;
    } else {
        $groupid = $grouporid;
        $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    }

    if (!tool_sync_groups_is_member($groupid, $userid, $component)) {
        return true;
    }

    $params = array('groupid' => $groupid,
                    'userid' => $userid);
    if ($component) {
        $params['component'] = $component;
    }
    $DB->delete_records('groups_members', $params);

    // Update group info.
    $time = time();
    $DB->set_field('groups', 'timemodified', $time, array('id' => $groupid));
    $group->timemodified = $time;

    // Trigger group event.
    $params = array(
        'context' => context_course::instance($group->courseid),
        'objectid' => $groupid,
        'relateduserid' => $userid
    );
    $event = \core\event\group_member_removed::create($params);
    $event->add_record_snapshot('groups', $group);
    $event->trigger();

    return true;
}

/**
 * Determines if the user is a member of the given group.
 * Adds component handling.
 *
 * If $userid is null, use the global object.
 *
 * @category group
 * @param int $groupid The group to check for membership.
 * @param int $userid The user to check against the group.
 * @return bool True if the user is a member, false otherwise.
 * @see /lib/grouplib.php groups_is_member
 */
function tool_sync_groups_is_member($groupid, $userid=null, $component = null) {
    global $USER, $DB;

    if (!$userid) {
        $userid = $USER->id;
    }

    $params = array('groupid' => $groupid,
                    'userid' => $userid);
    if ($component) {
        $params['component'] = $component;
    }
    return $DB->record_exists('groups_members', $params);
}

function tool_sync_check_repair_plugin_version() {
    global $DB;

    if (!$DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'version'))) {
        $version = new StdClass;
        $version->plugin = 'tool_sync';
        $version->name = 'version';
        $version->value = '2018052103';
        $DB->insert_record('config_plugins', $version);
        purge_all_caches();
    }
}
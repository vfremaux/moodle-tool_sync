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

require_once('courses/courses.class.php');
require_once('users/users.class.php');
require_once('enrol/enrols.class.php');
require_once('userpictures/userpictures.class.php');

define('SYNC_COURSE_CHECK', 0x001);
define('SYNC_COURSE_CREATE', 0x002);
define('SYNC_COURSE_DELETE', 0x004);
define('SYNC_COURSE_RESET', 0x008);
define('SYNC_COURSE_CREATE_DELETE', 0x006);

/**
 * prints a report to a log stream and output ir also to screen if required
 *
 */
function tool_sync_report(&$report, $message, $onscreen = true){
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
    global $CFG;

    static $textlib;
    static $first = true;

    // we may have a risk the BOM is present on first line
    if ($resetfirst) $first = true;    
    if (!isset($textlib)) $textlib = new textlib(); // singleton
    if ($first && $CFG->tool_sync_encoding == 'UTF-8'){
        $text = $textlib->trim_utf8_bom($text);                    
        $first = false;
    }

    $text = preg_replace("/\n?\r?/", '', $text);

    if ($CFG->tool_sync_encoding != 'UTF-8'){
        $text = utf8_encode($text);
    }

    return preg_match('/^$/', $text) || preg_match('/^(\(|\[|-|#|\/| )/', $text);
}

/**
 * Get course and role assignations summary
 * TODO : Rework for PostGre compatibility.
 */
function tool_sync_get_all_courses($orderby = 'shortname'){
    global $CFG, $DB;

    $sql = "
        SELECT
            IF(ass.roleid IS NOT NULL , CONCAT( c.id, '_', ass.roleid ) , CONCAT( c.id, '_', '0' ) ) AS recid, 
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
    global $CFG, $USER, $SITE;

    mtrace('tool_sync_cron() started at '. date('H:i:s'));

    $syncconfig = get_config('tool_sync');

    if (debugging()){ // ensures production platform cannot be attacked in deny of service that way
        $debug = optional_param('cronsyncdebug', 0, PARAM_INT);
    }
    // 0 no debug
    // 1 pass hourtime
    // 2 pass dayrun and daytime

    // Capture any file in sync external input.
    tool_sync_capture_input_files();

    $cfgh = 0 + @$syncconfig->h;
    $cfgm = 0 + @$syncconfig->m;

    $h = date('G');
    $m = date('i');

    $day = date("D");
    $var = 'tool_sync_'.$day;

    $last = 0 + @$syncconfig->lastrun; // internal

    if ($last == 0) {
        set_config('dayrun', 0, 'tool_sync'); // failtrap when never run and sync_lastrun not initialized
        $syncconfig->dayrun = 0;
    }

    $now = time();
    // $nextrun = $last + DAYSECS - 300; // assume we do it once a day

    $nextdate = $last + DAYSECS;
    $nextmidnight = mktime (0, 0, 0, date("n", $nextdate), date("j", $nextdate), date("Y", $nextdate));

    if (($now > $nextmidnight) && ($now > $last + @$syncconfig->ct) && !$debug){
        echo "Reset ... as after $nextmidnight. \n";
        set_config('dayrun', 0, 'tool_sync');
    }

    /*
    $done = 2;
    if($now < $nextrun && !$debug && $last > 0){
        if ($now > $last + $CFG->tool_sync_ct){
            // after the critical run time, we force back dayrun to false so cron can be run again.
            // the critical time ensures that previous cron has finished and a proper "sync_lastrun" date has been recorded.
            set_config('sync_dayrun', 0);
        }
        echo "Course and user sync ... nothing to do. Waiting time ".sprintf('%02d', $cfgh).':'.sprintf('%02d', $cfgm) ."\n";
        return;
    }
    */

    if (empty($CFG->$var) && !$debug){
        echo "Course and user sync ... not valid day, nothing to do. \n";
        return;
    }

    if ((($h * 60 + $m) > ($cfgh * 60 + $cfgm)) && !@$syncconfig->dayrun  || $debug){

        // we store that lock at start to lock any bouncing cron calls.
        set_config('dayrun', 1, 'tool_sync');
        $syncconfig->dayrun = 1;

        print_string('execstartsat', 'tool_sync', "$h:$m");
        echo "\n";

        $lockfile = "$CFG->dataroot/sync/locked.txt";
        $alock = "$CFG->dataroot/sync/alock.txt";

        if ((file_exists($alock))||(file_exists($lockfile))) {
            $log = "Synchronisation report\n \n";
            $log = $log . "Starting at: $h:$m \n";
            if (empty($syncconfig->ct)) {
            } else {
                $ct = $syncconfig->ct;
                $file = @fopen($lockfile, 'r');
                $line = fgets($file);
                fclose($file);
                $i = time();
                
                $field = explode(':', $line);
                
                $last = $field[1] + 60 * $ct;
                
                if ($now > $last) {
                    $str = get_string('errortoooldlock', 'tool_sync');
                    $log .= $str;
                    email_to_user(get_admin(), get_admin(), $SITE->shortname." : Synchronisation critical error", $str);                        
                }
            }
        } else {
            $log = "Synchronisation report\n\n";
            $log .= "Starting at: $h:$m \n";

            // Setting antibounce lock
            $file = @fopen($lockfile,'w');
            fputs($file,"M:".time());
            fclose($file);

            $log .= "- - - - - - - - - - - - - - - - - - - -\n \n";
            
            /// COURSE SYNC
            
            if (empty($syncconfig->courseactivation)) {
                $str = get_string('coursesync', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
            } else {
                $str = get_string('coursecronprocessing', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
                $coursesmanager = new course_sync_manager;
                $coursesmanager->cron($syncconfig);
                if (!empty($syncconfig->checkfilename)) {
                    $log .= "$CFG->tool_sync_checkfilename\n";
                }
                if(!empty($coursesmanager->log)){
                    $log .= $coursesmanager->log."\n";
                }
                $str = get_string('endofprocess', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";                    
            }

            /// USER ACCOUNTS SYNC
            
            if (empty($syncconfig->useractivation)) {
                $str = get_string('usersync', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
            } else {
                $str = get_string('usercronprocessing', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
                $userpicturemanager = new users_plugin_manager;
                $userpicturemanager->cron($syncconfig);
                if (!empty($userpicturemanager->log)){
                    $log .= $userpicturemanager->log."\n";
                }
                $str = get_string('endofprocess', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";                    
            }

            /// USER AVATARS SYNC

            if (empty($syncconfig->userpicturesactivation)) {
                $str = get_string('userpicturesync', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
            } else {
                $str = get_string('userpicturescronprocessing', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
                $userpicturesmanager = new userpictures_plugin_manager;
                $userpicturesmanager->cron($syncconfig);
                if (!empty($userpicturesmanager->log)) {
                    $log .= $userpicturesmanager."\n";
                }
                $str = get_string('endofprocess', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";
            }

            /// COHORTS SYNC

            if (empty($syncconfig->cohortsactivation)) {
                $str = get_string('cohortsync', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
            } else {
                $str = get_string('cohortcronprocessing', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
                $cohortssmanager = new cohort_plugin_manager;
                $cohortssmanager->cron($syncconfig);
                if (!empty($cohortssmanager->log)){
                    $log .= $cohortsmanager."\n";
                }
                $str = get_string('endofprocess', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";
            }

            /// ENROLLMENT SYNC

            if (empty($syncconfig->enrolactivation)) {
                $str = get_string('enrolcronprocessing', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                echo $str;
                $log .= $str;
            } else {
                $str = get_string('enrolcronprocessing', 'tool_sync');
                $str .= "\n";
                echo $str;
                $log .= $str;
                $enrolmanager = new enrol_plugin_manager;
                $enrolmanager->cron($syncconfig);
                if (!empty($enrolmanager->log)){
                    $log .= $enrolmanager->log."\n";
                }
                $str = get_string('endofprocess', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str."- - - - - - - - - - - - - - - - - - - -\n\n";
            }

            /// GROUP CLEANUP
            
            if (empty($CFG->tool_sync_enrolcleanup)) {
                $str = get_string('group_clean', 'tool_sync');
                $str .= ': ';
                $str .= get_string('disabled', 'tool_sync');
                $str .= "\n";
                $log .= $str;
                echo $str;
            } else {
                foreach ($CFG->coursesg as $courseid) {
                    $groups = groups_get_all_groups($courseid, 0, 0, 'g.*');
                    foreach ($groups as $g) {
                        $groupid = $g->id;
                        if(!groups_get_members($groupid, $fields='u.*', $sort='lastname ASC')) {
                            groups_delete_group($groupid);
                        }
                    }
                }
                $str = get_string('emptygroupsdeleted', 'tool_sync');
                $str .= "\n\n";
                echo $str;
                $log .= $str;
            }
            
            unlink($lockfile);
            $now = time();
            set_config('tool_sync_lastrun', $now);
        }

    // Creating and sending report.

        if (!empty($log)) {
            $filerec = new StdClass();
            $filerec->contextid = context_system::instance()->id;
            $filerec->component = 'tool_sync';
            $filerec->filearea = 'syncfiles';
            $filerec->itemid = 0;
            $filerec->filename = 'report-'.date('Ymd-Hi').'.txt';
            $filerec->filepath = '/reports/';

            $fs = get_file_storage();

            $fs->create_file_from_string($filerec, $log);

            if (!empty($CFG->tool_sync_enrol_mailadmins)) {
                email_to_user(get_admin(), get_admin(), $SITE->shortname." : Enrol Sync Log", $log);
            }
        }
    } else {
        if (!$syncconfig->dayrun){
            echo "Course and user sync ... not yet. Waiting time ".sprintf('%02d', $cfgh).':'.sprintf('%02d', $cfgm) ."\n";
        } else {
            echo "Course and user sync ... already passed today, nothing to do. \n";
        }
    }
    mtrace('sync: tool_sync_cron() finished at ' . date('H:i:s'));
}

/**
* parses a YYYY-MM-DD hh:ii:ss
*
*
*/
function tool_sync_parsetime($time, $default = 0){

    if (preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s+(\d\d):(\d\d):(\d\d)/', $time, $matches)){
        $Y = $matches[1];
        $M = $matches[2];
        $D = $matches[3];
        $h = $matches[4];
        $i = $matches[5];
        $s = $matches[6];
        return mktime($h , $i, $s, $M, $D, $Y);
    } else {
        return $default;
    }
}

/**
 * Captures input files from a system administrator accessible location
 * and store them into tool_sync filearea
 * Synchronisation checks for a lock.txt file NOT being present. A readlock.txt
 * file is written as weak semaphore process. Readlock.txt signal will avoid
 * twice concurrent execution of file retireval. 
 */
function tool_sync_capture_input_files($interactive = false) {
    global $CFG;

    // Ensures input directory exists
    $syncinputdir = $CFG->dataroot.'/sync';
    if (!is_dir($syncinputdir)) {
        mkdir($syncinputdir, 0777);
    }

    $lockfile = $CFG->dataroot.'/sync/lock.txt';
    if (file_exists($lockfile)) {
        $fileinfo = stat($lockfile);
        if ($fileinfo['timecreated'] < (time() - HOURSEC * 3)) {
            // This is a too old file. May denote a remote feeder issue. Notify admin.
            if ($interactive) {
                mtrace('Too old write lock file. Resuming sync input capture.');
            } else {
                email_to_user(get_admin(), get_admin(), $SITE->shortname." : Too old write lock file.", 'Possible remote writer process issue.');
            }
        }
        return;
    }

    $readlockfile = $CFG->dataroot.'/sync/readlock.txt';
    if (file_exists($readlockfile)) {
        $fileinfo = stat($lockfile);
        if ($fileinfo['timecreated'] < (time() - HOURSEC * 3)) {
            // This is a too old file. May denote a remote feeder issue. Notify admin.
            if ($interactive) {
                mtrace('Too old read lock file. this miht affect remote end, but continue capture.');
            } else {
                email_to_user(get_admin(), get_admin(), $SITE->shortname." : Too old read lock file.", 'Possible local sync process issue.');
            }
        }
    }
    if ($FILE = fopen($readlockfile, 'w')) {
        fputs($FILE, time());
        fclose($FILE);
    } else {
        // Something wrong in sync input dir. Notify admin.
        if ($interactive) {
            mtrace('Could not create readlock file. Possible severe issue in storage. Resuming sync input capture.');
        } else {
            email_to_user(get_admin(), get_admin(), $SITE->shortname." : Could not create readlock file.", 'Possible local sync process issue.');
        }
        return;
    }

    $DIR = opendir($syncinputdir);

    $fs = get_file_storage();

    while ($entry = readdir($DIR)) {
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

        $filerec = new StdClass();
        $filerec->contextid = context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = $entry;

        // Delete previous version and avoid file collision.
        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        $fs->create_file_from_pathname($filerec, $syncinputdir.'/'.$entry);
        @unlink($syncinputdir.'/'.$entry);
    }

    closedir($DIR);
    
    @unlink($readlockfile);
}
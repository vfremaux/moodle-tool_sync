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
* @package enrol
* @subpackage sync
* @author Funck Thibaut
*/

require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');

class tool_plugin_sync {

    function form_elements(&$frm) {
        $frm->addElement('header', 'globalconf1', get_string('configuration', 'tool_sync'));

        $frm->addElement('checkbox', 'tool_sync/courseactivation', get_string('synccourses', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/useractivation', get_string('syncusers', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/userpicturesactivation', get_string('syncuserpictures', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/enrolactivation', get_string('syncenrols', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/cohortactivation', get_string('synccohorts', 'tool_sync'));
        
        $encodings = array('UTF-8' => 'UTF-8', 'ISO-8859-1' => 'ISO-8859-1');
        $frm->addElement('select', 'tool_sync/encoding', get_string('encoding', 'tool_sync'), $encodings);
        $separators = array(',' => ', (coma)', ';' => '; (semicolon)');
        $frm->addElement('select', 'tool_sync/csvseparator', get_string('csvseparator', 'tool_sync'), $separators);

        $frm->addElement('header', 'globalconf2', get_string('run', 'tool_sync'));
        
        $frm->addElement('checkbox', 'tool_sync/mon', get_string('day_mon', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/tue', get_string('day_tue', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/wed', get_string('day_wed', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/thu', get_string('day_thu', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/fri', get_string('day_fri', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/sat', get_string('day_sat', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/sun', get_string('day_sun', 'tool_sync'));

        for ($i = 0 ; $i < 24 ; $i++) {
            $houroptions[$i] = $i;
        }

        for ($i = 0 ; $i < 60 ; $i += 10) {
            $minoptions[$i] = $i;
        }

        $sarr = array();
        $sarr[] =& $frm->createElement('select', 'tool_sync/h', get_string('hour', 'tool_sync'), $houroptions);
        $sarr[] =& $frm->createElement('select', 'tool_sync/m', get_string('minute', 'tool_sync'), $minoptions);
        $frm->addGroup($sarr, 'runtimegroup', get_string('runtime', 'tool_sync'), array(''), false);

        for ($i = 0 ; $i < 60 ; $i += 10) {
            $criticalduration[$i] = $i;
        }

        $frm->addElement('select', 'tool_sync/ct', get_string('criticaltime', 'tool_sync'), $criticalduration);
        
        $frm->addElement('header', 'globalconf3', get_string('final_action', 'tool_sync'));

        $frm->addElement('checkbox', 'tool_sync/enrolcleanup', get_string('group_clean', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filearchive', get_string('filearchive', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filefailed', get_string('failedfile', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filecleanup', get_string('filecleanup', 'tool_sync'));
    }

    function cron($syncconfig) {
        global $CFG, $USER, $SITE;

        if (debugging()) {
            $debug = optional_param('cronsyncdebug', 0, PARAM_INT); // ensures production platform cannot be attacked in deny of service that way
        }
        // 0 no debug
        // 1 pass hourtime
        // 2 pass dayrun and daytime
        
        $cfgh = $syncconfig->h;
        $cfgm = $syncconfig->m;

        $h = date('G');
        $m = date('i');

        $day = date("D");

        $last = 0 + @$syncconfig->lastrun;

        if ($last == 0) { 
            set_config('dayrun', 0, 'tool_sync'); // failtrap when never run and sync_lastrun not initialized
            $syncconfig->dayrun = 0;
        }

        $now = time();
        // $nextrun = $last + DAYSECS - 300; // assume we do it once a day

        $nextdate = $last + DAYSECS;
        $nextmidnight = mktime (0, 0, 0, date("n", $nextdate), date("j", $nextdate), date("Y", $nextdate));

        if (($now > $nextmidnight) && ($now > $last + $syncconfig->ct)){
            echo "Reset ... as after $nextmidnight. \n";
            set_config('dayrun', 0, 'tool_sync');
            $syncconfig->dayrun = 0;
        }

        /*
        $done = 2;
        if($now < $nextrun && !$debug && $last > 0){
            if ($now > $last + $syncconfig->ct){
                // after the critical run time, we force back dayrun to false so cron can be run again.
                // the critical time ensures that previous cron has finished and a proper "sync_lastrun" date has been recorded.
                set_config('dayrun', 0, 'tool_sync');
            }
            echo "Course and user sync ... nothing to do. Waiting time ".sprintf('%02d', $cfgh).':'.sprintf('%02d', $cfgm) ."\n";
            return;
        }
        */
        
        if (empty($syncconfig->$day) && !$debug){
            echo "Course and user sync ... not valid day, nothing to do. \n";
            return;
        }
        
        if(($h == $cfgh) && ($m >= $cfgm) && !$syncconfig->dayrun  || $debug){

            // We store that lock at start to lock any bouncing cron calls.
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
                    if (!empty($CFG->checkfilename)) {
                        $log .= "$CFG->checkfilename\n";
                    }
                    if (!empty($coursesmanager->log)) {
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
                    $usersmanager = new userpictures_plugin_manager;
                    $usersmanager->cron($syncconfig);
                    if (!empty($usersmanager->log)){
                        $log .= $usersmanager->log."\n";
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

                if (empty($syncconfig->enrolcleanup)) {
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
                            if(!groups_get_members($groupid, $fields='u.*', $sort='lastname ASC')){
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
                set_config('sync_lastrun', $now);
            }

            // processing aggregated log

            if (!empty($log)) {
                $reportfilename = 'report-'.date('Ymd-Hi').'.txt';

                if (!empty($syncconfig->enrol_mailadmins)) {
                    email_to_user(get_admin(), get_admin(), $SITE->shortname." : Enrol Sync Log", $log);
                }
            }
        } else {
            if (!$syncconfig->dayrun) {
                echo "Course and user sync ... not yet. Waiting time ".sprintf('%02d', $cfgh).':'.sprintf('%02d', $cfgm) ."\n";
            } else {
                echo "Course and user sync ... already passed today, nothing to do. \n";
            }
        }
    }
}

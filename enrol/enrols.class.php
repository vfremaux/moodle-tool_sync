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

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot use this script this way!');
}

/**
* @author Funck Thibaut
* @package tool-sync
*
**/

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/sync_manager.class.php');

/**
 * The Enrol Plugin Manager manages role assignaitns and enrollements from a CSV input file.
 *
 */
class enrol_plugin_manager extends sync_manager {

    function form_elements(&$frm) {
        global $CFG;
        $frm->addElement('text', 'tool_sync/enrol_filelocation', get_string('enrolfilelocation', 'tool_sync'));
        $frm->setType('tool_sync/enrol_filelocation', PARAM_TEXT);

        $identifieroptions = array('0' => 'idnumber', '1' => 'shortname', '2' => 'id');
        $frm->addElement('select', 'tool_sync/enrol_courseidentifier', get_string('enrolcourseidentifier', 'tool_sync'), $identifieroptions);

        $identifieroptions = array('0' => 'idnumber', '1' => 'username', '2' => 'email', '3' => 'id');
        $frm->addElement('select', 'tool_sync/enrol_useridentifier', get_string('enroluseridentifier', 'tool_sync'), $identifieroptions);

        $frm->addElement('checkbox', 'tool_sync/enrol_mailadmins', get_string('enrolemailcourseadmins', 'tool_sync'), 1);

        $frm->addElement('static', 'enrolsst1', '<hr>');

        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/enrol/execcron.php\'');
        $frm->addElement('button', 'manualenrols', get_string('manualenrolrun', 'tool_sync'), $attribs);

    }

    function cron($syncconfig) {
        global $CFG, $USER, $DB;

        $csv_encode = '/\&\#44/';
        if (isset($syncconfig->csvseparator)) {
            $csv_delimiter = '\\' . $syncconfig->csvseparator;
            $csv_delimiter2 = $syncconfig->csvseparator;

            if (isset($CFG->CSV_ENCODE)) {
                $csv_encode = '/\&\#' . $CFG->CSV_ENCODE . '/';
            }
        } else {
            $csv_delimiter = "\;";
            $csv_delimiter2 = ";";
        }

        if (empty($this->manualfilerec)) {
            $filerec = $this->get_input_file($syncconfig->enrol_filelocation, 'enrols.csv');
        } else {
            $filerec = $this->manualfilerec;
        }
        if (!($filereader = $this->open_input_file($filerec))) {
            return;
        }

        $required = array(
                'rolename' => 1,
                'cid' => 1,
                'uid' => 1);
        $optional = array(
                'hidden' => 1,
                'starttime' => 1,
                'endtime' => 1,
                'cmd' => 1,
                'enrol' => 1,
                'gcmd' => 1,
                'g1' => 1,
                'g2' => 1,
                'g3' => 1,
                'g4' => 1,
                'g5' => 1,
                'g6' => 1,
                'g7' => 1,
                'g8' => 1,
                'g9' => 1);

        // jump any empty or comment line
        $text = fgets($filereader, 1024);

        $i = 0;

        while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
            $text = fgets($filereader, 1024);
            $i++;
        }

        $headers = explode($csv_delimiter2, $text);
        
        function trim_fields(&$e){
            $e = trim($e);
        }
        
        array_walk($headers, 'trim_fields');

        foreach ($headers as $h) {
            $header[] = trim($h); // Remove whitespace.
            if (!(isset($required[$h]) or isset($optional[$h]))) {
                $this->report(get_string('errorinvalidcolumnname', 'tool_sync', $h));
                return;
            }
            if (isset($required[$h])) {
                $required[$h] = 0;
            }
        }
        foreach ($required as $key => $value) {
            if ($value) { //required field missing
                $this->report(get_string('errorrequiredcolumn', 'tool_sync', $key));
                return;
            }
        }

        // Header is validated
        $this->init_tryback($headers);

        // Starting processing lines
        $i = 2;
        while (!feof ($filereader)) {

            $record = array();

            $text = fgets($filereader, 1024);
            if (tool_sync_is_empty_line_or_format($text, false)) {
                $i++;
                continue;
            }
            $line = explode($csv_delimiter2, $text);

            foreach ($line as $key => $value) {
                //decode encoded commas
                $record[$header[$key]] = trim($value);
            }
            
            if (!array_key_exists('cmd', $record)) {
                $record['cmd'] = (empty($syncconfig->enrol_defaultcmd)) ? 'add' : $syncconfig->enrol_defaultcmd ;
            }

            if (!array_key_exists('enrol', $record)) {
                $record['enrol'] = '';
            } else {
                if (empty($record['enrol'])){
                    $record['enrol'] = 'manual';
                }
            }

            if (array_key_exists('starttime', $record)) {
                $record['starttime'] = tool_sync_parsetime($record['starttime'], time());
            } else {
                $record['starttime'] = time();
            }

            if (array_key_exists('endtime', $record)) {
                $record['endtime'] = tool_sync_parsetime($record['endtime'], 0);
            } else {
                $record['endtime'] = 0;
            }

            $e = new StdClass;
            $e->i = $i;
            $e->mycmd = $record['cmd'];
            $e->myrole = $record['rolename'];

            $cidentifieroptions = array('idnumber', 'shortname', 'id');
            $cidentifiername = $cidentifieroptions[0 + @$syncconfig->enrol_courseidentifier];

            $uidentifieroptions = array('idnumber', 'username', 'email', 'id');
            $uidentifiername = $uidentifieroptions[0 + @$syncconfig->enrol_useridentifier];

            $e->myuser = $record['uid']; // user identifier
            $e->mycourse = $record['cid']; // course identifier

            if (!$user = $DB->get_record('user', array($uidentifiername => $record['uid'])) ) {
                $this->report(get_string('errornouser', 'tool_sync', $e));
                $i++;
                if (!empty($syncconfig->filefailed)) {
                    $this->feed_tryback($text);
                }
                continue;
            }

            $e->myuser = $user->username.' ('.$e->myuser.')'; // complete idnumber with real username

            if (empty($record['cid'])){
                $this->report(get_string('errornullcourseidentifier', 'tool_sync', $i));
                $i++;
                if (!empty($syncconfig->filefailed)) {
                    $this->feed_tryback($text);
                }
                continue;
            }

            if (!$course = $DB->get_record('course', array($cidentifiername => $record['cid'])) ) {
                $this->report(get_string('errornocourse', 'tool_sync', $e));
                $i++;
                if (!empty($syncconfig->filefailed)) {
                    $this->feed_tryback($text);
                }
                continue;
            }

            $syncconfig->coursesg[$i - 1] = $course->id;
            $context = context_course::instance($course->id);
            
            // get enrolment plugin and method
            if ($enrolments = enrol_get_instances($course->id, true)){
                $enrol = array_pop($enrolments);
                $enrolcomponent = 'enrol_'.$enrol->enrol;
                $enrolinstance = $enrol->id;
            } else {
                $enrolcomponent = '';
                $enrolinstance = 0;
            }
            
            $enrol = enrol_get_plugin('manual');

            if (!$enrols = $DB->get_records('enrol', array('enrol' => $record['enrol'], 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED), 'sortorder ASC')) {
                $this->report(get_string('errornomanualenrol', 'tool_sync'));
                $record['enrol'] = '';
            } else {
                $enrol = reset($enrols);
                $enrolplugin = enrol_get_plugin($record['enrol']);
            }

            // start process record
            
            if($record['cmd'] == 'del' || $record['cmd'] == 'delete'){
                if (!empty($record['enrol'])){

                    // unenrol also removes all role assigniations
                    try{
                        $enrolplugin->unenrol_user($enrol, $user->id);
                        $this->report(get_string('unenrolled', 'tool_sync', $e));
                    } catch (Exception $exc) {
                        $this->report(get_string('errorunenrol', 'tool_sync', $e));
                    }

                } else {
                    if($role = $DB->get_record('role', array('shortname' => $record['rolename']))){
                        // avoids weird behaviour of role assignement in other assignement admin
                        $enrolcomponent = '';
                        $enrolinstance = 0;
                        if(!role_unassign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, time())){
                            $this->report(get_string('errorunassign', 'tool_sync', $e));
                        } else {
                            $this->report(get_string('unassign', 'tool_sync', $e));
                        }
                    } else {
                        if(!role_unassign(null, $user->id, $context->id, $enrolcomponent, $enrolinstance)){
                            $this->report(get_string('errorunassign', 'tool_sync', $e));
                        } else {
                            $this->report(get_string('unassignall', 'tool_sync', $e));
                        }                                    
                    }
                }
                
            } elseif ($record['cmd'] == 'add'){
                if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))){

                    if (!empty($record['enrol'])){
                        // Uses manual enrolment plugin to enrol AND assign role properly
                        // enrollment with explicit role does role_assignation
                        try {
                            $enrolplugin->enrol_user($enrol, $user->id, $role->id, $record['starttime'], $record['endtime'], ENROL_USER_ACTIVE);
                            $this->report(get_string('enrolled', 'tool_sync', $e));
                        } catch (Exception $exc){
                            $this->report(get_string('errorenrol', 'tool_sync', $e));
                        }
                    } else {
                        if (!$DB->get_record('role_assignments', array('roleid' => $role->id, 'contextid' => $context->id, 'userid' => $user->id, 'component' => ''))){
                            if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, $record['starttime'])){
                            // if(!role_assign($role->id, $user->id, $context->id)){
                                if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($filename, $text, $headers);
                                $this->report(get_string('errorline', 'tool_sync')." $i : $mycmd $myrole $myuser $mycourse : $user->lastname $user->firstname == $role->shortname ==> $course->shortname");
                            } else {
                                $this->report(get_string('assign', 'tool_sync', $e));
                            }
                        } else {
                            $this->report(get_string('alreadyassigned', 'tool_sync', $e));
                        }
                    }

                } else {
                    if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($filename, $text, $headers);
                    $this->report(get_string('errornorole', 'tool_sync', $e));
                }
            } elseif ($record['cmd'] == 'shift'){

                // check this role exists in this moodle
                if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))){

                    // unenrol also unassign all roles
                    if (!empty($record['enrol'])){
                        try {
                            $enrolplugin->unenrol_user($enrol, $user->id);
                            $this->report(get_string('unenrolled', 'tool_sync', $e));
                        } catch (Exception $exc) {
                            $this->report(get_string('errorunenrol', 'tool_sync', $e));
                        }
                    } else {
                        if ($roles = get_user_roles($context, $user->id)) {
                            foreach ($roles as $r){
                                // weird behaviour 
                                $enrolcomponent = '';
                                $enrolinstance = 0;
                                if (!role_unassign($r->roleid, $user->id, $context->id, $enrolcomponent, $enrolinstance)){
                                    $this->report(get_string('unassignerror', 'tool_sync', $e));
                                } else {
                                    $this->report(get_string('unassign', 'tool_sync', $e));
                                }
                            }
                        }
                    }

                    // maybe we need enrol this user (if first time in shift list)
                    // enrolement does perform role_assign
                    if (!empty($record['enrol'])){
                        try {
                            $enrolplugin->enrol_user($enrol, $user->id, $role->id, $record['starttime'], $record['endtime'], ENROL_USER_ACTIVE);
                            $this->report(get_string('enrolled', 'tool_sync', $e));
                        } catch(Exception $exc){
                            $this->report(get_string('errorenrol', 'tool_sync', $e));
                        }
                    } else {
                        if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, $record['starttime'])){
                            if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($filename, $text, $headers);
                            $this->report(get_string('errorassign', 'tool_sync', $e));
                        } else {
                            $this->report(get_string('assign', 'tool_sync', $e));
                        }
                    }

                } else {
                    if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($filename, $text, $headers);
                    $this->report(get_string('errornorole', 'tool_sync', $e));
                    $i++;
                    continue;
                }
            } else {
                if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($filename, $text, $headers);
                $this->report(get_string('errorbadcmd', 'tool_sync', $e));
            }
            
            if (!empty($record['gcmd'])){
                if ($record['gcmd'] == 'gadd' || $record['gcmd'] == 'gaddcreate'){
                    for ($i = 1 ; $i < 10 ; $i++){
                        if (!empty($record['g'.$i])){
                            if ($gid = groups_get_group_by_name($course->id, $record['g'.$i])) {
                                $groupid[$i] = $gid;
                            } else {
                                if ($record['gcmd'] == 'gaddcreate'){
                                    $groupsettings->name = $record['g'.$i];
                                    $groupsettings->courseid = $course->id;
                                    if ($gid = groups_create_group($groupsettings)) {
                                        $groupid[$i] = $gid;
                                        $e->group = $record['g'.$i];
                                        $this->report(get_string('groupcreated', 'tool_sync', $e));
                                    } else {
                                        $e->group = $record['g'.$i];
                                        $this->report(get_string('errorgroupnotacreated', 'tool_sync', $e));
                                    }
                                } else {
                                    $e->group = $record['g'.$i];
                                    $this->report(get_string('groupunknown','tool_sync',$e));
                                    continue;
                                }
                            }

                            $e->group = $record['g'.$i];
                            
                            if (count(get_user_roles($context, $user->id))) {
                                if (groups_add_member($groupid[$i], $user->id)) {
                                    $this->report(get_string('addedtogroup','tool_sync',$e));
                                } else {
                                    $this->report(get_string('addedtogroupnot','tool_sync',$e));
                                }
                            } else {
                                $this->report(get_string('addedtogroupnotenrolled','',$record['g'.$i]));
                            }
                        }
                    }
                } elseif ($record['gcmd'] == 'greplace' || $record['gcmd'] == 'greplacecreate'){
                    groups_delete_group_members($course->id, $user->id); 
                    $this->report(get_string('groupassigndeleted', 'tool_sync', $e));
                    for ($i = 1 ; $i < 10 ; $i++){
                        if (!empty($record['g'.$i])){
                            if ($gid = groups_get_group_by_name($course->id, $record['g'.$i])) {
                                $groupid[$i] = $gid;
                            } else {
                                if ($record['gcmd'] == 'greplacecreate'){
                                    $groupsettings->name = $record['g'.$i];
                                    $groupsettings->courseid = $course->id;
                                    if ($gid = groups_create_group($groupsettings)) {
                                        $groupid[$i] = $gid;
                                        $e->group = $record['g'.$i];
                                        $this->report(get_string('groupcreated', 'tool_sync', $e));
                                    } else {
                                        $e->group = $record['g'.$i];
                                        $this->report(get_string('errorgroupnotacreated', 'tool_sync', $e));
                                    }
                                } else {
                                    $e->group = $record['g'.$i];
                                    $this->report(get_string('groupunknown','tool_sync',$e));
                                }
                            }
                            
                            if (count(get_user_roles($context, $user->id))) {
                                if (groups_add_member($groupid[$i], $user->id)) {
                                    $this->report(get_string('addedtogroup','tool_sync',$e));
                                } else {
                                    $this->report(get_string('addedtogroupnot','tool_sync',$e));
                                }
                            } else {
                                $this->report(get_string('addedtogroupnotenrolled','',$record['g'.$i]));
                            }
                        }
                    }
                } else {
                    $this->report(get_string('errorgcmdvalue', 'tool_sync', $e));
                }
            }
            //echo "\n";
            $i++;
        }
        fclose($filereader);

        if (!empty($syncconfig->filearchive)) {
            $this->archive_input_file($filerec);
        }

        if (!empty($syncconfig->filecleanup)) {
            $this->cleanup_input_file($filerec);
        }

        $this->report("\n".get_string('endofreport', 'tool_sync'));
        
        return true;
    }
}

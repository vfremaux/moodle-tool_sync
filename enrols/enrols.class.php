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
 * @package     tool_sync
 * @category    tool
 * @author      Funck Thibaut
 * @copyright   2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sync;

use \StdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/classes/sync_manager.class.php');

/**
 * The Enrol Plugin Manager manages role assignations and enrollements from a CSV input file.
 */
class enrol_sync_manager extends sync_manager {

    public function form_elements(&$frm) {

        $key = 'tool_sync/enrols_filelocation';
        $label = get_string('enrolfilelocation', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/enrols_filelocation', PARAM_TEXT);

        $key = 'tool_sync/enrols_courseidentifier';
        $label = get_string('enrolcourseidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_coursefields());

        $key = 'tool_sync/enrols_useridentifier';
        $label = get_string('enroluseridentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_userfields());

        $key = 'tool_sync/enrols_protectgroups';
        $label = get_string('enrolprotectgroups', 'tool_sync');
        $frm->addElement('advcheckbox', $key, $label, '', array('group' => 1), array(0, 1));

        $key = 'tool_sync/enrols_enableenrolondisabled';
        $label = get_string('enableenrolondisabled', 'tool_sync');
        $frm->addElement('advcheckbox', $key, $label, '', array('group' => 3), array(0, 1));

        $key = 'tool_sync/enrols_mailadmins';
        $label = get_string('enrolemailcourseadmins', 'tool_sync');
        $frm->addElement('advcheckbox', $key, $label, '', array('group' => 2), array(0, 1));

        $frm->addElement('static', 'enrolsst1', '<hr>');

        $cronurl = new \moodle_url('/admin/tool/sync/enrols/execcron.php');
        $attribs = array('onclick' => 'document.location.href= \''.$cronurl.'\'');
        $frm->addElement('button', 'manualenrols', get_string('manualenrolrun', 'tool_sync'), $attribs);

    }

    public function get_userfields() {
        return array('id' => 'id',
                     'idnumber' => get_string('idnumber'),
                     'username' => get_string('username'),
                     'email' => get_string('email'));
    }

    public function get_coursefields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'shortname' => get_string('shortname'));
    }

    public function cron($syncconfig) {
        global $CFG, $DB;

        raise_memory_limit(MEMORY_HUGE);

        $csvencode = '/\&\#44/';
        if (isset($syncconfig->csvseparator)) {
            $csvdelimiter = '\\' . $syncconfig->csvseparator;
            $csvdelimiter2 = $syncconfig->csvseparator;

            if (isset($CFG->CSV_ENCODE)) {
                $csvencode = '/\&\#' . $CFG->CSV_ENCODE . '/';
            }
        } else {
            $csvdelimiter = "\;";
            $csvdelimiter2 = ";";
        }

        if (empty($this->manualfilerec)) {
            $filerec = $this->get_input_file(@$syncconfig->enrols_filelocation, 'enrols.csv');
        } else {
            $filerec = $this->manualfilerec;
        }

        // We have no file to process. Probably because never setup.
        if (!($filereader = $this->open_input_file($filerec, 'enrol'))) {
            set_config('lastrunning_enrol', null, 'tool_sync');
            return;
        }

        $required = array(
                'rolename' => 1,
                'cid' => 1,
                'uid' => 1);
        $optional = array(
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

        // Jump any empty or comment line.
        $text = tool_sync_read($filereader, 1024, $syncconfig);

        $i = 0;

        while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
            $text = fgets($filereader, 1024);
            $i++;
        }

        $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
        $headers = explode($csvdelimiter2, $text);

        array_walk($headers, 'trim_array_values');

        foreach ($headers as $h) {
            $header[] = trim($h); // Remove whitespace.
            if (!(isset($required[$h]) or isset($optional[$h]))) {
                $this->report(get_string('errorinvalidcolumnname', 'tool_sync', $h));
                set_config('lastrunning_enrol', null, 'tool_sync');
                return;
            }
            if (isset($required[$h])) {
                $required[$h] = 0;
            }
        }

        foreach ($required as $key => $value) {
            if ($value) { // Required field missing.
                $this->report(get_string('errorrequiredcolumn', 'tool_sync', $key));
                set_config('lastrunning_enrol', null, 'tool_sync');
                return;
            }
        }

        // Header is validated.
        $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

        // Starting processing lines.
        $i = 2;
        while (!feof ($filereader)) {

            $this->report("# -- $i");

            $record = array();

            $text = tool_sync_read($filereader, 1024, $syncconfig);
            $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
            if (tool_sync_is_empty_line_or_format($text, false)) {
                $i++;
                continue;
            }
            $line = explode($csvdelimiter2, $text);

            if ($CFG->debug, DEBUG_DEVELOPER) {
                echo ">> Decoding \n";
            }
            foreach ($line as $key => $value) {
                // Decode encoded commas.
                $record[$header[$key]] = trim($value);
            }

            if (!array_key_exists('cmd', $record)) {
                $record['cmd'] = (empty($syncconfig->enrols_defaultcmd)) ? 'add' : $syncconfig->enrols_defaultcmd;
            }

            if (!array_key_exists('enrol', $record)) {
                $record['enrol'] = '';
            } else {
                if (empty($record['enrol'])) {
                    $record['enrol'] = 'manual';
                }
            }

            if ($CFG->debug, DEBUG_DEVELOPER) {
                echo ">> Fixing time \n";
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

            $cidentifiername = @$syncconfig->enrols_courseidentifier;

            $uidentifiername = @$syncconfig->enrols_useridentifier;

            $e->courseby = $cidentifiername;
            $e->myuser = $record['uid']; // User identifier.
            $e->userby = $uidentifiername;
            $e->mycourse = $record['cid']; // Course identifier.

            if ($CFG->debug == DEBUG_DEVELOPER) {
                echo ">> Check user \n";
            }
            if (!$user = $DB->get_record('user', array($uidentifiername => $record['uid'])) ) {
                $this->report(get_string('errornouser', 'tool_sync', $e));
                if (!empty($syncconfig->filefailed)) {
                    $this->feed_tryback($text);
                }
                $i++;
                continue;
            }

            $e->myuser = $user->username.' ('.$e->myuser.')'; // Complete idnumber with real username.

            if ($CFG->debug == DEBUG_DEVELOPER) {
                echo ">> Check course \n";
            }
            if (empty($record['cid'])) {
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
            $context = \context_course::instance($course->id);

            // Get enrolment plugin and method.
            if ($enrolments = enrol_get_instances($course->id, true)) {
                $enrol = array_pop($enrolments);
                $enrolcomponent = 'enrol_'.$enrol->enrol;
                $enrolinstance = $enrol->id;
            } else {
                $enrolcomponent = '';
                $enrolinstance = 0;
            }

            if ($record['enrol'] != 'sync') {
                $enrol = enrol_get_plugin('manual');
                $params = array('enrol' => $record['enrol'], 'courseid' => $course->id);
                if (empty($syncconfig->enrols_enableenrolondisabled)) {
                    $params['status'] = ENROL_INSTANCE_ENABLED;
                }
                if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                    $this->report(get_string('errornoenrolmethod', 'tool_sync'));
                    $record['enrol'] = '';
                } else {
                    $enrol = reset($enrols);
                    $enrolplugin = enrol_get_plugin($record['enrol']);
                }
            } else {
                require_once($CFG->dirroot.'/enrol/sync/lib.php');
            }

            // Start process record.

            if ($record['cmd'] == 'del' || $record['cmd'] == 'delete') {
                if (!empty($record['enrol'])) {

                    // Unenrol also removes all role assignations.
                    if (empty($syncconfig->simulate)) {
                        try {
                            if ($record['enrol'] != 'sync') {
                                $enrolplugin->unenrol_user($enrol, $user->id);
                            } else {
                                \enrol_sync_plugin::static_unenrol_user($course, $user->id);
                            }
                            $this->report(get_string('unenrolled', 'tool_sync', $e));
                        } catch (Exception $exc) {
                            $this->report(get_string('errorunenrol', 'tool_sync', $e));
                        }
                    } else {
                        $this->report('SIMULATION : '.get_string('unenrolled', 'tool_sync', $e));
                    }

                } else {
                    if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))) {
                        // Avoids weird behaviour of role assignement in other assignement admin.
                        $enrolcomponent = '';
                        $enrolinstance = 0;

                        if (empty($syncconfig->simulate)) {
                            try {
                                role_unassign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, time());
                                $this->report(get_string('unassign', 'tool_sync', $e));
                            } catch (Exception $ex) {
                                $this->report(get_string('errorunassign', 'tool_sync', $e));
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('unassign', 'tool_sync', $e));
                        }
                    } else {
                        if (empty($syncconfig->simulate)) {
                            try {
                                role_unassign(null, $user->id, $context->id, $enrolcomponent, $enrolinstance);
                                $this->report(get_string('unassignall', 'tool_sync', $e));
                            } catch (Exception $ex) {
                                $this->report(get_string('errorunassign', 'tool_sync', $e));
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('unassignall', 'tool_sync', $e));
                        }
                    }
                }

            } else if ($record['cmd'] == 'add') {
                if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))) {

                    if (!empty($record['enrol'])) {
                        /*
                         * Uses manual enrolment plugin to enrol AND assign role properly
                         * enrollment with explicit role does role_assignation
                         */
                        if (empty($syncconfig->simulate)) {
                            try {
                                if ($record['enrol'] != 'sync') {
                                    $enrolplugin->enrol_user($enrol, $user->id, $role->id, $record['starttime'],
                                                             $record['endtime'], ENROL_USER_ACTIVE);
                                } else {
                                    \enrol_sync_plugin::static_enrol_user($course, $user->id, $role->id, $record['starttime'],
                                                             $record['endtime']);
                                }
                                $this->report(get_string('enrolled', 'tool_sync', $e));
                            } catch (Exception $exc) {
                                $this->report(get_string('errorenrol', 'tool_sync', $e));
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('enrolled', 'tool_sync', $e));
                        }
                    } else {
                        $params = array('roleid' => $role->id,
                                        'contextid' => $context->id,
                                        'userid' => $user->id,
                                        'component' => '');
                        if (!$DB->get_record('role_assignments', $params)) {
                            if (empty($syncconfig->simulate)) {
                                $failed = false;
                                try {
                                    if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent,
                                                     $enrolinstance, $record['starttime'])) {
                                        $failed = true;
                                    } else {
                                        $this->report(get_string('assign', 'tool_sync', $e));
                                    }
                                } catch (Exception $e) {
                                    $failed = true;
                                }
                                if ($failed) {
                                    if (!empty($syncconfig->filefailed)) {
                                        $this->feed_tryback($text);
                                    }
                                    $errorline = get_string('errorline', 'tool_sync')." $i :";
                                    $errorline .= " {$record['cmd']} role : $user->lastname $user->firstname ";
                                    $errorline .= "== $role->shortname ==> $course->shortname";
                                    $this->report($errorline);
                                    $i++;
                                    continue;
                                }
                            } else {
                                $this->report('SIMULATION : '.get_string('assign', 'tool_sync', $e));
                            }
                        } else {
                            $this->report(get_string('alreadyassigned', 'tool_sync', $e));
                        }
                    }

                } else {
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    $this->report(get_string('errornorole', 'tool_sync', $e));
                }
            } else if ($record['cmd'] == 'shift') {

                // Check this role exists in this moodle.
                if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))) {

                    // Unenrol also unassign all roles.
                    if (!empty($record['enrol'])) {
                        if (empty($syncconfig->simulate)) {
                            try {
                                if ($record['enrol'] != 'sync') {
                                    $enrolplugin->unenrol_user($enrol, $user->id);
                                } else {
                                    \enrol_sync_plugin::static_unenrol_user($course, $user->id);
                                }
                                $this->report(get_string('unenrolled', 'tool_sync', $e));
                            } catch (Exception $exc) {
                                $this->report(get_string('errorunenrol', 'tool_sync', $e));
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('unenrolled', 'tool_sync', $e));
                        }
                    } else {
                        if ($roles = get_user_roles($context, $user->id)) {
                            foreach ($roles as $r) {
                                // Weird behaviour.
                                $enrolcomponent = '';
                                $enrolinstance = 0;
                                if (empty($syncconfig->simulate)) {
                                    try {
                                        role_unassign($r->roleid, $user->id, $context->id, $enrolcomponent, $enrolinstance);
                                        $this->report(get_string('unassign', 'tool_sync', $e));
                                    } catch (Exception $ex) {
                                        $this->report(get_string('unassignerror', 'tool_sync', $e));
                                    }
                                } else {
                                    $this->report('SIMULATION : '.get_string('unassign', 'tool_sync', $e));
                                }
                            }
                        }
                    }

                    /*
                     * maybe we need enrol this user (if first time in shift list)
                     * enrolement does perform role_assign
                     */
                    if (!empty($record['enrol'])) {
                        if (empty($syncconfig->simulate)) {
                            try {
                                if ($record['enrol'] == 'sync') {
                                    \enrol_sync_plugin::static_enrol_user($course, $user->id, $role->id, $record['starttime'],
                                                             $record['endtime']);
                                } else {
                                    $enrolplugin->enrol_user($enrol, $user->id, $role->id, $record['starttime'],
                                                             $record['endtime'], ENROL_USER_ACTIVE);
                                }
                                $this->report(get_string('enrolled', 'tool_sync', $e));
                            } catch (Exception $exc) {
                                $this->report(get_string('errorenrol', 'tool_sync', $e));
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('enrolled', 'tool_sync', $e));
                        }
                    } else {
                        if (empty($syncconfig->simulate)) {
                            $failed = false;
                            try {
                                if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent,
                                                 $enrolinstance, $record['starttime'])) {
                                    $failed = true;
                                } else {
                                    $this->report(get_string('assign', 'tool_sync', $e));
                                }
                            } catch (Exception $e) {
                                $failed = true;
                            }

                            if ($failed) {
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback_file($text);
                                }
                                $this->report(get_string('errorassign', 'tool_sync', $e));
                                $i++;
                                continue;
                            }
                        } else {
                            $this->report('SIMULATION : '.get_string('assign', 'tool_sync', $e));
                        }
                    }

                } else {
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    $this->report(get_string('errornorole', 'tool_sync', $e));
                    $i++;
                    continue;
                }
            } else {
                if (!empty($syncconfig->filefailed)) {
                    $this->feed_tryback($text);
                }
                $this->report(get_string('errorbadcmd', 'tool_sync', $e));
            }

            $component = '';
            if ($syncconfig->enrols_protectgroups) {
                $component = 'tool_sync';
            }

            if ($CFG->debug == DEBUG_DEVELOPER) {
                echo "Grouping...\n";
            }
            if (!empty($record['gcmd'])) {
                if ($record['gcmd'] == 'gadd' || $record['gcmd'] == 'gaddcreate') {
                    try {
                        for ($j = 1; $j < 10; $j++) {
                            if (!empty($record['g'.$j])) {
                                $gname = $record['g'.$j];
                                if ($gid = $DB->get_field('groups', 'id', array('courseid' => $course->id, 'name' => $gname))) {
                                    $groupid[$j] = $gid;
                                } else {
                                    if ($record['gcmd'] == 'gaddcreate') {
                                        if (debugging(null, DEBUG_DEVELOPER)) {
                                            echo ">> Creating group \n";
                                        }
                                        $groupsettings = new StdClass;
                                        $groupsettings->name = $record['g'.$j];
                                        $groupsettings->courseid = $course->id;
                                        if (empty($syncconfig->simulate)) {
                                            if ($gid = groups_create_group($groupsettings)) {
                                                $groupid[$j] = $gid;
                                                $e->group = $record['g'.$j];
                                                $e->mycourse = $course->shortname;
                                                $this->report(get_string('groupcreated', 'tool_sync', $e));
                                            } else {
                                                $e->group = $record['g'.$j];
                                                $e->mycourse = $course->shortname;
                                                $this->report(get_string('errorgroupnotacreated', 'tool_sync', $e));
                                                continue;
                                            }
                                        } else {
                                            $e->group = $record['g'.$j];
                                            $e->mycourse = $course->shortname;
                                            $this->report('SIMULATION : '.get_string('groupcreated', 'tool_sync', $e));
                                            $gid = 999999; // Simulate a created gtoup.
                                        }
                                    } else {
                                        $e->group = $record['g'.$j];
                                        $e->mycourse = $course->shortname;
                                        $this->report(get_string('groupunknown', 'tool_sync', $e));
                                        // Next j same i.
                                        continue; // Continue inner groups loop.
                                    }
                                }

                                $e = new StdClass;
                                $e->group = $record['g'.$j];
                                $e->myuser = $user->username.' ('.$user->id.')';

                                if (count(get_user_roles($context, $user->id))) {
                                    if (empty($syncconfig->simulate)) {
                                        if (groups_add_member($groupid[$j], $user->id, $component)) {
                                            $this->report(get_string('addedtogroup', 'tool_sync', $e));
                                        } else {
                                            $this->report(get_string('addedtogroupnot', 'tool_sync', $e));
                                        }
                                    } else {
                                        $this->report('SIMULATION : '.get_string('addedtogroup', 'tool_sync', $e));
                                    }
                                } else {
                                    $this->report(get_string('addedtogroupnotenrolled', '', $record['g'.$j]));
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $this->report("Global group gadd/gaddcreate exception on line $i");
                    }
                } else if ($record['gcmd'] == 'greplace' || $record['gcmd'] == 'greplacecreate') {
                    try {
                        if (empty($syncconfig->simulate)) {
                            tool_sync_delete_group_members($course->id, $user->id, $component);
                            $this->report(get_string('groupassigndeleted', 'tool_sync', $e));
                        } else {
                            $this->report('SIMULATION : '.get_string('groupassigndeleted', 'tool_sync', $e));
                        }
                        for ($j = 1; $j < 10; $j++) {
                            if (!empty($record['g'.$j])) {
                                if (debugging(null, DEBUG_DEVELOPER)) {
                                    echo ">> Checking group \n";
                                }
                                $e = new StdClass();
                                $e->group = $record['g'.$j];
                                $e->myuser = $user->username.' ('.$user->id.')';
                                $e->mycourse = $course->shortname;
                                $gname = $record['g'.$j];
                                if ($gid = $DB->get_field('groups', 'id', array('courseid' => $course->id, 'name' => $record['g'.$j]))) {
                                    if ($CFG->debug == DEBUG_DEVELOPER) {
                                        echo ">> Got group $gid by name $e->group\n";
                                    }
                                    $groupid[$j] = $gid;
                                } else {
                                    if ($record['gcmd'] == 'greplacecreate') {
                                        if ($CFG->debug == DEBUG_DEVELOPER) {
                                            echo ">> Creating group \n";
                                        }
                                        $groupsettings = new StdClass;
                                        $groupsettings->name = $record['g'.$j];
                                        $groupsettings->courseid = $course->id;
                                        if (empty($syncconfig->simulate)) {
                                            if ($gid = groups_create_group($groupsettings)) {
                                                $groupid[$j] = $gid;
                                                $this->report(get_string('groupcreated', 'tool_sync', $e));
                                            } else {
                                                // We could not create the missing group.
                                                $this->report(get_string('errorgroupnotacreated', 'tool_sync', $e));
                                                continue;
                                            }
                                        } else {
                                            $this->report('SIMULATION : '.get_string('groupcreated', 'tool_sync', $e));
                                        }
                                    } else {
                                        $this->report(get_string('groupunknown', 'tool_sync', $e));
                                        // Next j same i.
                                        continue; // Continue inner groups loop.
                                    }
                                }

                                if (count(get_user_roles($context, $user->id))) {
                                    if (empty($syncconfig->simulate)) {
                                        if (debugging(null, DEBUG_DEVELOPER)) {
                                            echo ">> Adding membership \n";
                                        }
                                        $e = new StdClass();
                                        $e->group = $groupid[$j];
                                        $e->myuser = $user->username.' ('.$user->id.')';
                                        if (groups_add_member($groupid[$j], $user->id, $component)) {
                                            $this->report(get_string('addedtogroup', 'tool_sync', $e));
                                        } else {
                                            $this->report(get_string('addedtogroupnot', 'tool_sync', $e));
                                        }
                                    } else {
                                        $this->report('SIMULATION : '.get_string('addedtogroup', 'tool_sync', $e));
                                    }
                                } else {
                                    $this->report(get_string('addedtogroupnotenrolled', '', $record['g'.$j]));
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $this->report("Global group greplace/greplacecreate exception on line $i");
                    }
                } else if ($record['gcmd'] == 'gdel') {
                    // TODO : Remove membership.
                    for ($j = 1; $j < 10; $j++) {
                        if (!empty($record['g'.$j])) {
                            if (empty($syncconfig->simulate)) {
                                if ($CFG->debug == DEBUG_DEVELOPER) {
                                    echo " >> Removing membership \n";
                                }
                                $e = new StdClass();
                                $e->group = $record['g'.$j];
                                $e->myuser = $user->username.' ('.$user->id.')';
                                if ($gid = $DB->get_field('groups', 'id', array('courseid' => $course->id, 'name' => $record['g'.$j]))) {
                                    try {
                                        tool_sync_group_remove_member($gid, $user->id, $component);
                                        $this->report(get_string('removedfromgroup', 'tool_sync', $e));
                                    } catch (Exception $e) {
                                        $this->report(get_string('removedfromgroupnot', 'tool_sync', $e));
                                    }
                                } else {
                                    $this->report(get_string('removedfromgroupnot', 'tool_sync', $e));
                                }
                            } else {
                                $e = new StdClass();
                                $e->group = $record['g'.$j];
                                if ($gid = $DB->get_field('groups', 'id', array('courseid' => $course->id, 'name' => $record['g'.$j]))) {
                                    $this->report('SIMULATION: '.get_string('removedfromgroup', 'toll_sync', $e));
                                } else {
                                    $this->report('SIMULATION: '.get_string('removedfromgroupnot', 'tool_sync', $e));
                                }
                            }
                        }
                    }
                } else {
                    $this->report(get_string('errorgcmdvalue', 'tool_sync', $e));
                }
            }
            $i++;
        }
        fclose($filereader);

        if ($CFG->debug == DEBUG_DEVELOPER) {
            mtrace("Finalization");
        }

        if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'storereport'))) {
            $this->store_report_file($filerec);
        }

        if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filefailed'))) {
            $this->write_tryback(clone($filerec));
        }

        if (empty($syncconfig->simulate)) {
            if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filearchive'))) {
                $this->archive_input_file(clone($filerec));
            }

            if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filecleanup'))) {
                $this->cleanup_input_file(clone($filerec));
            }
        }

        set_config('lastrunning_enrol', null, 'tool_sync');

        $this->report("\n".get_string('endofreport', 'tool_sync'));

        return true;
    }
}

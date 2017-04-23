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
 * @author    Funck Thibaut
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sync;

use StdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/classes/sync_manager.class.php');

class cohorts_sync_manager extends sync_manager {

    protected $manualfilerec;

    public $execute;

    public function __construct($execute = SYNC_COHORT_CREATE_UPDATE, $manualfilerec = null) {
        $this->execute = $execute;
        $this->manualfilerec = $manualfilerec;
    }

    public function form_elements(&$frm) {

        $frm->addElement('text', 'tool_sync/cohorts_filelocation', get_string('cohortfilelocation', 'tool_sync'));
        $frm->setType('tool_sync/cohorts_filelocation', PARAM_TEXT);

        $label = get_string('cohortuseridentifier', 'tool_sync');
        $frm->addElement('select', 'tool_sync/cohorts_useridentifier', $label, $this->get_userfields());
        $frm->setDefault('tool_sync/cohorts_useridentifier', 'idnumber');
        $frm->setType('tool_sync/cohorts_useridentifier', PARAM_TEXT);

        $label = get_string('cohortcohortidentifier', 'tool_sync');
        $frm->addElement('select', 'tool_sync/cohorts_cohortidentifier', $label, $this->get_cohortfields());
        $frm->setDefault('tool_sync/cohorts_cohortidentifier', 'idnumber');
        $frm->setType('tool_sync/cohorts_cohortidentifier', PARAM_TEXT);

        $label = get_string('cohortcoursebindingfilelocation', 'tool_sync');
        $frm->addElement('text', 'tool_sync/cohorts_coursebindingfilelocation', $label);
        $frm->setType('tool_sync/cohorts_coursebindingfilelocation', PARAM_TEXT);

        $label = get_string('cohortcourseidentifier', 'tool_sync');
        $frm->addElement('select', 'tool_sync/cohorts_courseidentifier', $label, $this->get_coursefields());
        $frm->setDefault('tool_sync/cohorts_courseidentifier', 'idnumber');
        $frm->setType('tool_sync/cohorts_courseidentifier', PARAM_TEXT);

        $label = get_string('cohortroleidentifier', 'tool_sync');
        $frm->addElement('select', 'tool_sync/cohorts_roleidentifier', $label, $this->get_rolefields());
        $frm->setDefault('tool_sync/cohorts_roleidentifier', 'idnumber');
        $frm->setType('tool_sync/cohorts_roleidentifier', PARAM_TEXT);

        $frm->addElement('checkbox', 'tool_sync/cohorts_autocreate', get_string('cohortautocreate', 'tool_sync'));
        $frm->setDefault('tool_sync/cohorts_autocreate', 1);
        $frm->setType('tool_sync/cohorts_autocreate', PARAM_BOOL);

        $frm->addElement('checkbox', 'tool_sync/cohorts_syncdelete', get_string('cohortsyncdelete', 'tool_sync'));
        $frm->setDefault('tool_sync/cohorts_syncdelete', 1);
        $frm->setType('tool_sync/cohorts_syncdelete', PARAM_BOOL);

        $frm->addElement('static', 'usersst1', '<hr>');

        $execurl = new \moodle_url('/admin/tool/sync/cohorts/execcron.php', array('action' => SYNC_COHORT_CREATE_UPDATE));
        $params = array('onclick' => 'document.location.href= \''.$execurl.'\'');
        $frm->addElement('button', 'manualcohorts', get_string('manualcohortrun', 'tool_sync'), $params);

        $execurl = new \moodle_url('/admin/tool/sync/cohorts/execcron.php', array('action' => SYNC_COHORT_BIND_COURSES));
        $params = array('onclick' => 'document.location.href= \''.$execurl.'\'');
        $frm->addElement('button', 'manualcohorts', get_string('manualcohortbindingrun', 'tool_sync'), $params);

    }

    /**
     * Provides the acceptable user identifiers menu
     */
    public function get_userfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'username' => 'username',
                     'email' => 'email');
    }

    /**
     * Provides the acceptable cohorts identifiers menu
     */
    public function get_cohortfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'name' => 'name');
    }

    /**
     * Provides the acceptable cohorts identifiers menu
     */
    public function get_coursefields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'shortname' => 'shortname');
    }

    /**
     * Provides the acceptable cohorts identifiers menu
     */
    public function get_rolefields() {
        return array('id' => 'id',
                     'shortname' => 'shortname');
    }

    /**
     *
     */
    public function cron($syncconfig) {
        global $DB;

        $systemcontext = \context_system::instance();

        if ($this->execute == SYNC_COHORT_CREATE_UPDATE) {

            $this->report('Starting cohorts operations...');

            // Internal process controls.
            $autocreatecohorts = 0 + @$syncconfig->cohorts_autocreate;

            if (!get_admin()) {
                return;
            }

            $uploaded = $this->manualfilerec;
            if (empty($uploaded)) {
                $filerec = $this->get_input_file(@$syncconfig->cohorts_filelocation, 'cohorts.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            // Make arrays of valid fields for error checking.
            $required = array(
            );
            $optionaldefaults = array();
            $optional = array(
                'cmd',
                'userid' => 1,
                'cid' => 1,
                'cname',
                'cdescription',
                'cidnumber',
            );
            $patterns = array();
            $metas = array();

            $this->report(get_string('cohortsstarting', 'tool_sync'));

            // We have no file to process. Probably because never setup.
            if (!($filereader = $this->open_input_file($filerec))) {
                return;
            }

            /*
             * File that is used is currently hardcoded here!
             * Large files are likely to take their time and memory. Let PHP know
             * that we'll take longer, and that the process should be recycled soon
             * to free up memory.
             */
            @set_time_limit(0);
            @raise_memory_limit("256M");
            if (function_exists('apache_child_terminate')) {
                @apache_child_terminate();
            }

            $text = tool_sync_read($filereader, 1024, $syncconfig);

            $i = 0;

            // Skip comments and empty lines.
            while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                $text = tool_sync_read($filereader, 1024, $syncconfig);
                $i++;
                continue;
            }

            if (!tool_sync_check_separator($text)) {
                // This is a column name line that should NOT contain any of other separators.
                $this->report(get_string('invalidseparatordetected', 'tool_sync'));
                return;
            }

            $headers = explode($syncconfig->csvseparator, $text);

            if (!$this->check_headers($headers, $required, $patterns, $metas, $optional, $optionaldefaults)) {
                return;
            }

            // Header is validated.
            $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

            $userscohortassign = 0;
            $userscohortunassign = 0;
            $userserrors  = 0;

            while (!feof ($filereader)) {

                // Note: semicolon within a field should be encoded as &#59 (for semicolon separated csv files).
                $text = tool_sync_read($filereader, 1024, $syncconfig);
                if (tool_sync_is_empty_line_or_format($text, false)) {
                    $i++;
                    continue;
                }
                $valueset = explode($syncconfig->csvseparator, $text);

                $record = array();
                foreach ($valueset as $key => $value) {
                    // Decode encoded commas.
                    $record[$headers[$key]] = trim($value);
                }

                // Find assignable items.
                if (empty($syncconfig->cohorts_useridentifier)) {
                    $syncconfig->cohorts_useridentifier = 'username';
                }

                if (empty($syncconfig->cohorts_cohortidentifier)) {
                    $syncconfig->cohorts_cohortidentifier = 'idnumber';
                }


                if (empty($record['cmd'])) {
                    $record['cmd'] = 'add';
                }

                $cid = $syncconfig->cohorts_cohortidentifier;
                $uid = $syncconfig->cohorts_useridentifier;

                // Bind user to cohort.
                if ($record['cmd'] == 'add') {

                    // $cid can be name, id or idnumber

                    if (empty($record['c'.$cid])) {
                        $e = new StdClass;
                        $e->cid = $cid;
                        $e->line = $i;
                        $this->report(get_string('cohortprimaryidentifiermissing', 'tool_sync', $e));
                        continue;
                    }

                    $cohort = $DB->get_record('cohort', array( $cid => $record['c'.$cid]));
                    if (!empty($record['userid'])) {
                        $user = $DB->get_record('user', array($uid => $record['userid']));
                    } else {
                        $user = false;
                    }

                    // Create cohort if missing.
                    if (!$cohort) {
                        if (!$autocreatecohorts) {
                            // Creation is forbidden. Nust report the case.
                            // TODO track in log, push in runback file.
                            $e = new StdClass;
                            $e->cid = $cid;
                            $e->identifier = $record['c'.$cid];
                            $this->report(get_string('cohortnotfound', 'tool_sync', $e));
                            continue;
                        } else {
                            // Make cohort if cohort info explicit and not existing.
                            $t = time();
                            $cohort = new StdClass();
                            if (!empty($record['cname'])) {
                                $cohort->name = $record['cname'];
                            } else {
                                $cohort->name = $record['cohortid'];
                            }
                            $cohort->description = @$record['cdescription'];
                            $cohort->idnumber = @$record['cidnumber'];
                            $cohort->descriptionformat = FORMAT_MOODLE;
                            $cohort->contextid = $systemcontext->id;
                            $cohort->timecreated = $t;
                            $cohort->timemodified = $t;

                            $e = new StdClass;
                            if ($cid == 'name') {
                                $e->by = 'idnumber';
                                if ($conflict = $DB->get_record('cohort', array('idnumber' => $cohort->idnumber))) {
                                    $this->report(get_string('cohortconflict', 'tool_sync', $e));
                                    continue;
                                }
                            } else {
                                $e->by = 'name';
                                if ($conflict = $DB->get_record('cohort', array('name' => $cohort->name))) {
                                    $this->report(get_string('cohortconflict', 'tool_sync', $e));
                                    continue;
                                }
                            }

                            $cohort->id = $DB->insert_record('cohort', $cohort);
                            $this->report(get_string('cohortcreated', 'tool_sync', $cohort));
                        }
                    } else {
                        if (!empty($record['cdescription'])) {
                            $cohort->description = $record['cdescription'];
                        }
                        if (!empty($record['idnumber'])) {
                            $cohort->idnumber = @$record['cidnumber'];
                        }
                        if (!empty($record['name'])) {
                            $cohort->idnumber = @$record['name'];
                        }
                        $DB->update_record('cohort', $cohort);
                        $this->report(get_string('cohortupdated', 'tool_sync', $cohort));
                    }

                    if ($user) {
                        $params = array('userid' => $user->id, 'cohortid' => $cohort->id);
                        if (!$cohortmembership = $DB->get_record('cohort_members', $params)) {
                            $cohortmembership = new StdClass();
                            $cohortmembership->userid = $user->id;
                            $cohortmembership->cohortid = ''.@$cohort->id;
                            $cohortmembership->timeadded = time();
                            $cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
                            $userscohortassign++;
    
                            $e = new StdClass;
                            $e->username = $user->username;
                            $e->idnumber = $user->idnumber;
                            $e->cname = $cohort->name;
                            $this->report(get_string('cohortmemberadded', 'tool_sync', $e));
                        } else {
                            $e = new StdClass;
                            $e->username = $user->username;
                            $e->idnumber = $user->idnumber;
                            $e->cname = $cohort->name;
                            $this->report(get_string('cohortalreadymember', 'tool_sync', $e));
                        }
                    }

                } else if ($record['cmd'] == 'del') {

                    // $cid can be name, id or idnumber
                    $cohort = $DB->get_record('cohort', array( $cid => $record['c'.$cid] ));
                    if (!empty($record['userid'])) {
                        $user = $DB->get_record('user', array($uid => $record['userid']));
                    } else {
                        $user = false;
                    }

                    if ($user) {
                        if ($cohort) {
                            cohort_remove_member($cohort->id, $user->id);

                            $e = new StdClass;
                            $e->username = $user->username;
                            $e->idnumber = $user->idnumber;
                            $e->cname = $cohort->name;
                            $this->report(get_string('cohortmemberremoved', 'tool_sync', $e));
                        }
                    } else {
                        // Delete the whole cohort.
                        if ($cohort) {
                            cohort_delete_cohort($cohort);

                            // Removing all related enrolment data.
                            $enrols = $DB->get_records('enrol', array('enrol' => 'cohort', 'customint1' => $cohort->id));
                            if ($enrols) {
                                $DB->delete_records('enrol', array('enrol' => 'cohort', 'customint1' => $cohort->id));
                                $DB->delete_records_list('user_enrolments', 'enrolid', array_keys($enrols));
                            }

                            $this->report(get_string('cohortdeleted', 'tool_sync', $cohort));
                        } else {
                            $e = new StdClass;
                            $e->idnumber = $cohort->idnumber;
                            $e->cname = $cohort->name;
                            $this->report(get_string('cohortnotexists', 'tool_sync', $e));
                        }
                    }

                } else if ($record['cmd'] == 'free') {

                    $cohort = $DB->get_record('cohort', array( $cid => $record['cid'] ));
                    if (empty($cohort)) {
                        $e = new StdClass;
                        $e->idnumber = $cohort->idnumber;
                        $e->cname = $cohort->name;
                        $this->report(get_string('cohortnotexists', 'tool_sync', $e));
                        continue;
                    }
                    $members = $DB->get_records('cohort_members', array('cohortid' => $cohort->id));
                    if ($members) {
                        foreach($members as $member) {
                            cohort_remove_member($cohort->id, $member->userid);
                        }
                        $e = new StdClass;
                        $e->idnumber = $cohort->idnumber;
                        $e->cname = $cohort->name;
                        $this->report(get_string('cohortfreed', 'tool_sync', $e));
                    }
                }
            }
            fclose($filereader);

            $this->report('... finished.');
        }

        if ($this->execute == SYNC_COHORT_BIND_COURSES) {

            $this->report('Starting binding cohorts...');

            if (!get_admin()) {
                return;
            }

            $defaultrolestudent = $DB->get_record('role', array('shortname' => 'student'));

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->cohorts_coursebindingfilelocation, 'cohortscourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            // Make arrays of valid fields for error checking.
            $required = array(
                'cohort' => 1,
                'course' => 1,
            );
            $optionaldefaults = array();
            $optional = array(
                'cmd' => 1,
                'enrolstart' => 1,
                'enrolend' => 1,
                'role' => 1,
            );
            $patterns = array();
            $metas = array();

            // We have no file to process. Probably because never setup.
            if (!($filereader = $this->open_input_file($filerec))) {
                return;
            }

            $i = 0;

            // Skip comments and empty lines.
            while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                $text = tool_sync_read($filereader, 1024, $syncconfig);
                $i++;
                continue;
            }

            if (!tool_sync_check_separator($text)) {
                // This is a column name line that should NOT contain any of other separators.
                $this->report(get_string('invalidseparatordetected', 'tool_sync'));
                return;
            }

            $headers = explode($syncconfig->csvseparator, $text);

            if (!$this->check_headers($headers, $required, $patterns, $metas, $optional, $optionaldefaults)) {
                return;
            }

            // Header is validated for metas.
            $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

            while (!feof ($filereader)) {

                $text = tool_sync_read($filereader, 1024, $syncconfig);
                if (tool_sync_is_empty_line_or_format($text, false)) {
                    $i++;
                    continue;
                }
                $valueset = explode($syncconfig->csvseparator, $text);

                // Validate incoming values.
                $valuearr = array_combine($headers, $valueset);

                if (!array_key_exists('cmd', $valuearr)) {
                    $valuearr['cmd'] = 'add';
                }

                if (!array_key_exists('role', $valuearr)) {
                    $roleid = $defaultrolestudent->id;
                } else {
                    if (empty($valuearr['role'])) {
                        $roleid = $defaultrolestudent->id;
                    } else {
                        if ($valuearr['role'] != '*') {
                            $source = $syncconfig->cohorts_roleidentifier;
                            $roleid = tool_sync_get_internal_id('role', $source, $valuearr['role']);
                            if (!$roleid) {
                                $this->report(get_string('cohortbindingbadroleid', 'tool_sync', $valuearr['role']));
                                continue;
                            }
                        } else {
                            // Only for deletion. Means delete enrols for all roles).
                            $roleid = '*';
                        }
                    }
                }

                // Check we have a meta binding master to meta.

                $source = $syncconfig->cohorts_courseidentifier;
                $courseid = tool_sync_get_internal_id('course', $source, $valuearr['course']);
                if (!$courseid) {
                    $this->report(get_string('cohortbindingbadcourseid', 'tool_sync', $valuearr['course']));
                    continue;
                }
                $source = $syncconfig->cohorts_cohortidentifier;
                $cohortid = tool_sync_get_internal_id('cohort', $source, $valuearr['cohort']);
                if (!$cohortid) {
                    $this->report(get_string('cohortbindingbadcohortid', 'tool_sync', $valuearr['cohort']));
                    continue;
                }

                switch ($valuearr['cmd']) {
                    case 'add': {
                        $params = array('enrol' => 'cohort', 'courseid' => $courseid, 'customint1' => $cohortid, 'roleid' => $roleid);
                        if (!$oldrec = $DB->get_record('enrol', $params)) {
                            $enrol = new StdClass;
                            $enrol->enrol = 'cohort';
                            $enrol->status = 0;
                            $enrol->courseid = $courseid;
                            $enrol->enrolstartdate = time();
                            $enrol->enrolenddate = 0;
                            $enrol->roleid = $roleid;
                            $enrol->customint1 = $cohortid;
                            $DB->insert_record('enrol', $enrol);
                        } else {
                            if ($oldrec->status == 1) {
                                $oldrec->status = 0;
                                $enrol->enrolstartdate = time();
                                $DB->update_record('enrol', $oldrec);
                            }
                        }
                        $e = new StdClass;
                        $e->course = $valuearr['course'];
                        $e->cohort = $valuearr['cohort'];
                        $e->role = $valuearr['role'];
                        $this->report(get_string('cohortbindingadded', 'tool_sync', $e));
                        break;
                    }

                    case 'del': {
                        if ($roleid != '*') {
                            $params = array('enrol' => 'cohort', 'courseid' => $courseid, 'customint1' => $cohortid, 'roleid' => $roleid);
                        } else {
                            $params = array('enrol' => 'cohort', 'courseid' => $courseid, 'customint1' => $cohortid);
                        }
                        if ($oldrecs = $DB->get_records('enrol', $params)) {
                            foreach ($oldrecs as $oldrec) {
                                // Disable all enrols of any role on this cohort.
                                $oldrec->status = 1;
                                $DB->update_record('enrol', $oldrec);

                                $e = new StdClass;
                                $e->course = $valuearr['course'];
                                $e->cohort = $valuearr['cohort'];
                                $e->role = $DB->get_field('role', 'shortname', array('id' => $oldrec->roleid));
                                $this->report(get_string('cohortbindingdisabled', 'tool_sync', $e));
                            }
                        }
                        break;
                    }

                    default:
                }
            }

            fclose($filereader);

            $this->report('... finished');
        }

        if (!empty($syncconfig->storereport)) {
            $this->store_report_file($filerec);
        }

        if (!empty($syncconfig->filearchive)) {
            $this->archive_input_file($filerec);
        }

        if (!empty($syncconfig->filecleanup)) {
            $this->cleanup_input_file($filerec);
        }

        if (!empty($syncconfig->filefailed)) {
            $this->write_tryback($filerec);
        }

        return true;
    }
}

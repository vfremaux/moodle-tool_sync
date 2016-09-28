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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/sync_manager.class.php');

class cohorts_sync_manager extends sync_manager {

    protected $manualfilerec;

    public function __construct($manualfilerec = null) {
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

        $frm->addElement('checkbox', 'tool_sync/cohorts_autocreate', get_string('cohortautocreate', 'tool_sync'));
        $frm->setDefault('tool_sync/cohorts_autocreate', 1);
        $frm->setType('tool_sync/cohorts_autocreate', PARAM_BOOL);

        $frm->addElement('checkbox', 'tool_sync/cohorts_syncdelete', get_string('cohortsyncdelete', 'tool_sync'));
        $frm->setDefault('tool_sync/cohorts_syncdelete', 1);
        $frm->setType('tool_sync/cohorts_syncdelete', PARAM_BOOL);

        $frm->addElement('static', 'usersst1', '<hr>');

        $execurl = new moodle_url('/admin/tool/sync/cohorts/execcron.php');
        $params = array('onclick' => 'document.location.href= \''.$execurl.'\'');
        $frm->addElement('button', 'manualcohorts', get_string('manualcohortrun', 'tool_sync'), $params);

    }

    public function get_userfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'username' => 'username',
                     'email' => 'email');
    }

    public function get_cohortfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'name' => 'name');
    }

    /**
     *
     */
    public function cron($syncconfig) {
        global $CFG, $DB;

        $systemcontext = \context_system::instance();

        $config = get_config('tool_sync');

        // Internal process controls.
        $autocreatecohorts = 0 + @$config->cohorts_autocreate;

        if (!get_admin()) {
            return;
        }

        if (empty($this->manualfilerec)) {
            $filerec = $this->get_input_file(@$syncconfig->cohorts_filelocation, 'cohorts.csv');
        } else {
            $filerec = $this->manualfilerec;
        }

        // We have no file to process. Probably because never setup.
        if (!($filereader = $this->open_input_file($filerec))) {
            return;
        }

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

        // Make arrays of valid fields for error checking.
        $required = array('userid' => 1, 'cohortid' => 1);
        $optionaldefaults = array();
        $optional = array('cmd', 'cdescription', 'cidnumber');
        $patterns = array();
        $metas = array();

        $this->report(get_string('cohortsstarting', 'tool_sync'));

        // Jump any empty or comment line.
        $text = fgets($filereader, 1024);
        $i = 0;
        while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
            $text = tool_sync_read($filereader, 1024, $syncconfig);
            $i++;
        }

        $headers = explode($csvdelimiter2, $text);

        // Check for valid field names.
        foreach ($headers as $h) {
            $header[] = trim($h);
            $patternized = implode('|', $patterns) . "\\d+";
            $metapattern = implode('|', $metas);
            if (!(isset($required[$h]) ||
                    isset($optionaldefaults[$h]) ||
                            isset($optional[$h]) ||
                                    preg_match("/$patternized/", $h) ||
                                            preg_match("/$metapattern/", $h))) {
                $this->report(get_string('invalidfieldname', 'error', $h));
                return;
            }

            if (isset($required[$h])) {
                $required[$h] = 0;
            }
        }

        // Check for required fields.
        foreach ($required as $key => $value) {
            if ($value) {
                // Required field missing.
                $this->report(get_string('fieldrequired', 'error', $key));
                return;
            }
        }

        // Header is validated.
        $this->init_tryback(implode(';', $headers));

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
            $valueset = explode($csvdelimiter2, $text);

            $record = array();
            foreach ($valueset as $key => $value) {
                // Decode encoded commas.
                $record[$header[$key]] = preg_replace($csvencode, $csvdelimiter2, trim($value));
            }

            // Find assignable items.
            $userfields = $this->get_userfields();
            if (empty($syncconfig->cohorts_useridentifier)) {
                $syncconfig->cohorts_useridentifier = 0;
            }
            $uid = $userfields[$syncconfig->cohorts_useridentifier];
            if (!$user = $DB->get_record('user', array( $uid => $record['userid'] ))) {
                // TODO track in log, push in runback file.
                $e = new \StdClass();
                $e->uid = $uid;
                $e->identifier = $record['userid'];
                $this->report(get_string('cohortusernotfound', 'tool_sync', $e));
                $userserrors++;
                continue;
            }

            $cohortfields = $this->get_cohortfields();
            if (empty($syncconfig->cohorts_cohortidentifier)) {
                $syncconfig->cohorts_cohortidentifier = 0;
            }
            $cid = $cohortfields[$syncconfig->cohorts_cohortidentifier];
            if (!$cohort = $DB->get_record('cohort', array( $cid => $record['cohortid'] ))) {
                if (!$autocreatecohorts) {
                    if (($syncconfig->cohorts_cohortidentifier != 1) && empty($record['cohort'])) {
                        // TODO track in log, push in runback file.
                        $e = new \StdClass;
                        $e->cid = $cid;
                        $e->identifier = $record['cohortid'];
                        $this->report(get_string('cohortnotfound', 'tool_sync', $e));
                        continue;
                    }
                } else {
                    // Make cohort if cohort info explicit and not existing.
                    $t = time();
                    $cohort = new \StdClass();
                    $cohort->name = $record['cohortid'];
                    $cohort->description = @$record['cdescription'];
                    $cohort->idnumber = @$record['cidnumber'];
                    $cohort->descriptionformat = FORMAT_MOODLE;
                    $cohort->contextid = $systemcontext->id;
                    $cohort->timecreated = $t;
                    $cohort->timemodified = $t;
                    $cohort->id = $DB->insert_record('cohort', $cohort);
                    $this->report(get_string('cohortcreated', 'tool_sync', $cohort));
                }
            }

            // Bind user to cohort.
            if (!array_key_exists('cmd', $record) || $record['cmd'] == 'add') {
                $params = array('userid' => $user->id, 'cohortid' => $cohort->id);
                if (!$cohortmembership = $DB->get_record('cohort_members', $params)) {
                    $cohortmembership = new \StdClass();
                    $cohortmembership->userid = $user->id;
                    $cohortmembership->cohortid = ''.@$cohort->id;
                    $cohortmembership->timeadded = $t;
                    $cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
                    $userscohortassign++;

                    $e = new \StdClass;
                    $e->username = $user->username;
                    $e->idnumber = $user->idnumber;
                    $e->cname = $cohort->name;
                    $this->report(get_string('cohortmemberadded', 'tool_sync', $e));
                } else {
                    $e = new \StdClass;
                    $e->username = $user->username;
                    $e->idnumber = $user->idnumber;
                    $e->cname = $cohort->name;
                    $this->report(get_string('cohortalreadymember', 'tool_sync', $e));
                }
            } else if ($record['cmd'] == 'del') {
                $params = array('userid' => $user->id, 'cohortid' => $cohort->id);
                if ($cohortmembership = $DB->get_record('cohort_members', $params)) {
                    $DB->delete_records('cohort_members', array('id' => $cohortmembership->id));
                    $userscohortunassign++;

                    $e = new \StdClass;
                    $e->username = $user->username;
                    $e->idnumber = $user->idnumber;
                    $e->cname = $cohort->name;
                    $this->report(get_string('cohortmemberremoved', 'tool_sync', $e));
                }
            }
        }
        fclose($filereader);

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

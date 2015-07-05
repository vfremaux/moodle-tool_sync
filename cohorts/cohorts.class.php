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

// The following flags are set in the configuration
// $CFG->users_filelocation:       where is the file we are looking for?
// author - Funck Thibaut

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/sync_manager.class.php');

class cohorts_sync_manager extends sync_manager {

    protected $manualfilerec;

    public function __construct($manualfilerec = null){
        $this->manualfilerec = $manualfilerec;
    }

    public function form_elements(&$frm) {
        global $CFG;

        $frm->addElement('text', 'tool_sync/cohorts_filelocation', get_string('cohortfilelocation', 'tool_sync'));
        $frm->setType('tool_sync/cohorts_filelocation', PARAM_TEXT);

        $identifieroptions = array('0' => 'idnumber', '1' => 'shortname', '2' => 'id');
        $frm->addElement('select', 'tool_sync/cohorts_useridentifier', get_string('cohortuseridentifier', 'tool_sync'), $identifieroptions);

        $identifieroptions = array('0' => 'idnumber', '1' => 'name', '2' => 'id');
        $frm->addElement('select', 'tool_sync/cohorts_cohortidentifier', get_string('cohortcohortidentifier', 'tool_sync'), $identifieroptions);

        $frm->addElement('static', 'usersst1', '<hr>');

        $params = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/cohorts/execcron.php\'');
        $frm->addElement('button', 'manualcohorts', get_string('manualcohortrun', 'tool_sync'), $params);

    }

    /// Override the get_access_icons() function
    public function get_access_icons($course) {
    }

    /**
    *
    */
    public function cron($syncconfig) {
        global $CFG, $USER, $DB;

        $systemcontext = context_system::instance();

        // Internal process controls
        $syncdeletions = true;
        $autocreatecohorts = true;

        if (!$adminuser = get_admin()) {
            // print_error('errornoadmin', 'tool_sync');
            return;
        }

        if (empty($this->manualfilerec)) {
            $filerec = $this->get_input_file(@$syncconfig->cohorts_filelocation, 'cohorts.csv');
        } else {
            $filerec = $this->manualfilerec;
        }

        // We have no file to process. Probably because never setup
        if (!($filereader = $this->open_input_file($filerec))) {
            return;
        }

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

        //*NT* File that is used is currently hardcoded here!
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        @raise_memory_limit("256M");
        if (function_exists('apache_child_terminate')) {
            @apache_child_terminate();
        }

        // make arrays of valid fields for error checking
        $required = array('userid' => 1,
                'cohortid' => 1);
        $optionalDefaults = array();
        $optional = array('cdescription', 'cidnumber');

        // --- get header (field names) ---

        $textlib = new core_text();

        // jump any empty or comment line
        $text = fgets($filereader, 1024);
        $i = 0;
        while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
            $text = tool_sync_read($filereader, 1024, $syncconfig);
            $i++;
        }

        $headers = explode($csv_delimiter2, $text);

        // Check for valid field names.
        foreach ($headers as $h) {
            $header[] = trim($h); 
            $patternized = implode('|', $patterns) . "\\d+";
            $metapattern = implode('|', $metas);
            if (!(isset($required[$h]) or isset($optionalDefaults[$h]) or isset($optional[$h]) or preg_match("/$patternized/", $h) or preg_match("/$metapattern/", $h))) {
                $this->report(get_string('invalidfieldname', 'error', $h));
                return;
            }

            if (isset($required[$h])) {
                $required[$h] = 0;
            }
        }
        // Check for required fields.
        foreach ($required as $key => $value) {
            if ($value) { //required field missing
                $this->report(get_string('fieldrequired', 'error', $key));
                return;
            }
        }
        $linenum = 2; // since header is line 1

        $userscohortassign = 0;
        $usercohortunassign = 0;
        $userserrors  = 0;

        while (!feof ($filereader)) {

            //Note: semicolon within a field should be encoded as &#59 (for semicolon separated csv files)
            $text = tool_sync_read($filereader, 1024, $syncconfig);
            if (tool_sync_is_empty_line_or_format($text, false)) {
                $i++;
                continue;
            }
            $valueset = explode($csv_delimiter2, $text);

            $record = array();
            foreach ($valueset as $key => $value) {
                //decode encoded commas
                $record[$header[$key]] = preg_replace($csv_encode, $csv_delimiter2, trim($value));
            }

            // Find assignable items.
            if (empty($syncconfig->cohorts_useridentifier)) {
                $syncconfig->cohorts_useridentifier = 'idnumber';
            }
            $uid = $syncconfig->cohorts_useridentifier;
            if (!$user = $DB->get_record('user', array( $uid => $record['userid'] ))) {
                // @TODO trak in log, push in runback file
                continue;
            }

            if (empty($syncconfig->cohorts_cohortidentifier)) {
                $syncconfig->cohorts_cohortidentifier = 'idnumber';
            }
            $cid = $syncconfig->cohorts_cohortidentifier;
            if (!$cohort = $DB->get_record('cohort', array( $cid => $record['cohortid'] ))) {
                if (!$autocreatecohorts || empty($record['cohort'])) {
                    // @TODO trak in log, push in runback file
                    continue;
                }
            }

            // make cohort if cohort info explicit and not existing
            if (!$cohort) {
                $t = time();
                $cohort = new StdClass();
                $cohort->name = $record['cohort'];
                $cohort->description = @$record['cdescription'];
                $cohort->idnumber = @$record['cidnumber'];
                $cohort->descriptionformat = FORMAT_MOODLE;
                $cohort->contextid = $systemcontext->id;
                $cohort->timecreated = $t;
                $cohort->timemodified = $t;
                $cohort->id = $DB->insert_record('cohort', $cohort);
            }

            // bind user to cohort
            if (!$cohortmembership = $DB->get_record('cohort_members', array('userid' => $user->id, 'cohortid' => $cohort->id))) {
                $cohortmembership = new StdClass();
                $cohortmembership->userid = $user->id;
                $cohortmembership->cohortid = ''.@$cohort->id;
                $cohortmembership->timeadded = $t;
                $cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
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
        return true;
    }
}

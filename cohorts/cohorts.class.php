<?php

if (!defined('MOODLE_INTERNAL')) die('You cannot use this script this way!');

// The following flags are set in the configuration
// $CFG->users_filelocation:       where is the file we are looking for?
// author - Funck Thibaut

require_once $CFG->dirroot.'/admin/tool/sync/lib.php';
require_once($CFG->dirroot.'/user/profile/lib.php');

class cohorts_plugin_manager {

    var $log;  

	/// Override the base config_form() function
	function config_form($frm) {
    	global $CFG, $DB;

	    $vars = array('cohort_filelocation');
	    foreach ($vars as $var) {
	        if (!isset($frm->$var)) {
	            $frm->$var = '';
	        } 
	    }

	    include ($CFG->dirroot.'/admin/tool/sync/cohorts/config.html');    
	}


	/// Override the base process_config() function
	function process_config($config) {
		
	     if (!isset($config->tool_sync_cohorts_filelocation)) {
	        $config->tool_sync_cohorts_filelocation = '';
	    }
	    set_config('tool_sync_cohorts_filelocation', $config->tool_sync_cohorts_filelocation);
		if (!isset($config->tool_sync_cohorts_useridentifier)) {
	        $config->tool_sync_cohorts_useridentifier = '';
	    }
	    set_config('tool_sync_cohorts_useridentifier', $config->tool_sync_cohorts_useridentifier);
	    return true;	
	}

	/// Override the get_access_icons() function
	function get_access_icons($course) {
	}

	/**
	*/
    function cron() {
        global $CFG, $USER, $DB;
        
        $systemcontext = context_system::instance();

		// Internal process controls
		$syncdeletions = true;
		$autocreatecohorts = true;

		if (!$adminuser = get_admin()) {
        	// print_error('errornoadmin', 'tool_sync');
        	return;
		}

		if (empty($CFG->tool_sync_cohorts_filelocation)) {
            $filename = $CFG->dataroot.'/sync/cohortsimport.csv';  // Default location
        } else {
            $filename = $CFG->dataroot.'/'.$CFG->tool_sync_cohorts_filelocation;
        }

        if (!file_exists($filename)) {
			tool_sync_report($CFG->tool_sync_cohortlog, get_string('filenotfound', 'tool_sync', $filename));
			return;        	
        }

		$csv_encode = '/\&\#44/';
		if (isset($CFG->tool_sync_csvseparator)) {
			$csv_delimiter = '\\' . $CFG->tool_sync_csvseparator;
			$csv_delimiter2 = $CFG->tool_sync_csvseparator;

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
		$optional = array('cdescription',
			'cidnumber');

		// --- get header (field names) ---

		$textlib = new textlib();

		$fp = fopen($filename, 'rb');
		// jump any empty or comment line
		$text = fgets($fp, 1024);
		$i = 0;
		while(sync_is_empty_line_or_format($text, $i == 0)){				
			$text = fgets($fp, 1024);
			$i++;
		}

		$headers = explode($csv_delimiter2, $text);

		// check for valid field names
		foreach ($headers as $h) {
			$header[] = trim($h); 
			$patternized = implode('|', $patterns) . "\\d+";
			$metapattern = implode('|', $metas);
			if (!(isset($required[$h]) or isset($optionalDefaults[$h]) or isset($optional[$h]) or preg_match("/$patternized/", $h) or preg_match("/$metapattern/", $h))) {
				tool_sync_report($CFG->tool_sync_userlog, get_string('invalidfieldname', 'error', $h));
				return;
			}

			if (isset($required[$h])) {
				$required[$h] = 0;
			}
		}
		// check for required fields
		foreach ($required as $key => $value) {
			if ($value) { //required field missing
				tool_sync_report($CFG->tool_sync_userlog, get_string('fieldrequired', 'error', $key));
				return;
			}
		}
		$linenum = 2; // since header is line 1

		$userscohortassign = 0;
		$usercohortunassign = 0;
		$userserrors  = 0;

		while (!feof ($fp)) {

			//Note: semicolon within a field should be encoded as &#59 (for semicolon separated csv files)
			$text = fgets($fp, 1024);
			if (sync_is_empty_line_or_format($text, false)) {
				$i++;
				continue;
			}
			$valueset = explode($csv_delimiter2, $text);				

			$record = array();
			foreach ($valueset as $key => $value) {
				//decode encoded commas
				$record[$header[$key]] = preg_replace($csv_encode, $csv_delimiter2, trim($value));
			}
			
			// find assignable items
			if (empty(@$CFG->tool_sync_cohorts_useridentifier)){
				$CFG->tool_sync_cohorts_useridentifier = 'idnumber';
			}
			$uid = $CFG->tool_sync_cohorts_useridentifier;
			if (!$user = $DB->get_record('user', array( $uid => $record['userid'] ){
				// @TODO trak in log, push in runback file
				continue;
			}

			if (empty(@$CFG->tool_sync_cohorts_cohortidentifier)){
				$CFG->tool_sync_cohorts_cohortidentifier = 'idnumber';
			}
			$cid = $CFG->tool_sync_cohorts_cohortidentifier;
			if (!$cohort = $DB->get_record('cohort', array( $cid => $record['cohortid'] ){
				if (!$autocreatecohorts || empty($record['cohort'])){
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
			if (!$cohortmembership = $DB->get_record('cohort_members', array('userid' => $user->id, 'cohortid' => $cohort->id))){
				$cohortmembership = new StdClass();
				$cohortmembership->userid = $user->id;
				$cohortmembership->cohortid = ''.@$cohort->id;
				$cohortmembership->timeadded = $t;
				$cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
			}
		}
		fclose($fp);

		if (!empty($CFG->tool_sync_filearchive)){
			$archivename = basename($filename);
			$now = date('Ymd-hi', time());
			$archivename = $CFG->dataroot."/sync/archives/{$now}_cohorts_$archivename";
			copy($filename, $archivename);
		}
		if (!empty($CFG->tool_sync_filecleanup)){
			@unlink($filename);
		}		
		return true;
    }
}

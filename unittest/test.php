<?php

require_once($CFG->dirroot.'/admin/tool/sync/courses/courses2.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrol.php');

class enrolment_plugin_sync {

	/// Override the base config_form() function
	function config_form($frm) {
		global $CFG;

		$vars = array('sync_coursescleanup', 'sync_userscleanup', 'sync_enrolcleanup');
		foreach ($vars as $var) {
			if (!isset($frm->$var)) {
				$frm->$var = '';
			} 
		}

		$roles = $DB->get_records('role', null, '', 'id, name, shortname');
		$ffconfig = get_config('course');

		$frm->enrol_flatfilemapping = array();
		foreach($roles as $id => $record) {

			$frm->enrol_flatfilemapping[$id] = array(
				$record->name,
				isset($ffconfig->{"map_{$record->shortname}"}) ? $ffconfig->{"map_{$record->shortname}"} : $record->shortname
			);
		}		
		include ($CFG->dirroot.'/admin/tool/sync/config.html');    
	}

	function process_config($config) {	
		global $CFG;

		if (!isset($config->tool_sync_coursescleanup)) {
			$config->tool_sync_coursescleanup = '';
		}
		set_config('tool_sync_coursescleanup', $config->tool_sync_coursescleanup);
		if (!isset($config->tool_sync_userscleanup)) {
			$config->tool_sync_userscleanup = '';
		}
		set_config('tool_sync_userscleanup', $config->tool_sync_userscleanup);
		if (!isset($config->tool_sync_enrolcleanup)) {
			$config->tool_sync_enrolcleanup = '';
		}
		set_config('tool_sync_enrolcleanup', $config->tool_sync_enrolcleanup);		

		return true;
	}		

    function cron() { 
        global $CFG;
        global $USER;
		require_once($CFG->dirroot.'/admin/tool/sync/file_checker.php');
		$filechecker = new file_checker;	
		$filechecker->transform_users_file($CFG->tool_sync_users_filelocation);
		$filechecker->transform_enrol_file($CFG->tool_sync_enrol_filelocation);
		$coursesmanager = new course_sync_manager;
		$coursesmanager->cron();
		$usersmanager = new users_plugin_manager;
		$usersmanager->cron();
		$enrolmanager = new enrolment_plugin_flatfile2;
		$enrolmanager->cron();

		if (empty($CFG->tool_sync_coursescleanup)) {
            echo "done";
        } else {
            //TODO
        }		

		if (empty($CFG->tool_sync_userscleanup)) {
            echo "done";
        } else {
            //TODO
        }				
		if (empty($CFG->tool_sync_enrolcleanup)) {
            echo "done";
        } else {
            //TODO
        }				
    } // end of function
} // end of class

?>

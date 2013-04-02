<?php	   

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

	/// Override the base config_form() function
	function config_form($frm) {
		global $CFG, $DB;

		$vars = array('sync_coursescleanup', 'sync_userscleanup', 'sync_enrolcleanup', 'sync_Mon', 'sync_Tue', 'sync_Wed', 'sync_Thu', 'sync_Fri', 'sync_Sat', 'sync_Sun', 'sync_courseactivation', 'sync_useractivation', 'sync_enrolactivation', 'sync_h', 'sync_m', 'sync_ct');
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
		
		if (!isset($config->sync_coursescleanup)) {
			$config->sync_coursescleanup = '';
		}
		
		set_config('sync_coursescleanup', $config->sync_coursescleanup);
		
		if (!isset($config->sync_userscleanup)) {
			$config->sync_userscleanup = '';
		}
		
		set_config('sync_userscleanup', $config->sync_userscleanup);

		if (!isset($config->sync_enrolcleanup)) {
			$config->sync_enrolcleanup = '';
		}
		
		set_config('sync_enrolcleanup', $config->sync_enrolcleanup);	
		
		if (!isset($config->sync_courseactivation)) {
			$config->sync_courseactivation = '';
		}
		
		set_config('sync_courseactivation', $config->sync_courseactivation);
		
		if (!isset($config->sync_useractivation)) {
			$config->sync_useractivation = '';
		}
		
		set_config('sync_useractivation', $config->sync_useractivation);

		if (!isset($config->sync_userpicturesactivation)) {
			$config->sync_userpicturesactivation = '';
		}
		
		set_config('sync_userpicturesactivation', $config->sync_userpicturesactivation);

		if (!isset($config->sync_enrolactivation)) {
			$config->sync_enrolactivation = '';
		}
		
		set_config('sync_enrolactivation', $config->sync_enrolactivation);			

		if (!isset($config->sync_Mon)) {
			$config->sync_Mon = '';
		}
		
		set_config('sync_Mon', $config->sync_Mon);			
		
		if (!isset($config->sync_Tue)) {
			$config->sync_Tue = '';
		}
		
		set_config('sync_Tue', $config->sync_Tue);			

		if (!isset($config->sync_Wed)) {
			$config->sync_Wed = '';
		}
		
		set_config('sync_Wed', $config->sync_Wed);		

		if (!isset($config->sync_Thu)) {
			$config->sync_Thu = '';
		}
		
		set_config('sync_Thu', $config->sync_Thu);		

		if (!isset($config->sync_Fri)) {
			$config->sync_Fri = '';
		}
		
		set_config('sync_Fri', $config->sync_Fri);		

		if (!isset($config->sync_Sat)) {
			$config->sync_Sat = '';
		}
		
		set_config('sync_Sat', $config->sync_Sat);		

		if (!isset($config->sync_Sun)) {
			$config->sync_Sun = '';
		}
		
		set_config('sync_Sun', $config->sync_Sun);		

		if (!isset($config->sync_h)) {
			$config->sync_h = '';
		}
		
		set_config('sync_h', $config->sync_h);		

		if (!isset($config->sync_m)) {
			$config->sync_m = '';
		}
		
		set_config('sync_m', $config->sync_m);		

		if (!isset($config->sync_ct)) {
			$config->sync_ct = '';
		}
		
		set_config('sync_ct', $config->sync_ct);				
		
		if (!isset($config->sync_filecleanup)) {
			$config->sync_filecleanup = '';
		}
		
		set_config('sync_filecleanup', $config->sync_filecleanup);		

		if (!isset($config->sync_filearchive)) {
			$config->sync_filearchive = '';
		}
		
		set_config('sync_filearchive', $config->sync_filearchive);		

		if (!isset($config->sync_filefailed)) {
			$config->sync_filefailed = 0;
		}
		
		set_config('sync_filefailed', $config->sync_filefailed);		

		if (!isset($config->sync_encoding)) {
			$config->sync_encoding = '';
		}
		
		set_config('sync_encoding', $config->sync_encoding);			

		if (!isset($config->sync_csvseparator)) {
			$config->sync_csvseparator = ';';
		}
		
		set_config('sync_csvseparator', $config->sync_csvseparator);			
		
		return true;
	}		

    function cron() {
        global $CFG, $USER, $SITE;
		
		if (debugging()){
			$debug = optional_param('cronsyncdebug', 0, PARAM_INT); // ensures production platform cannot be attacked in deny of service that way
		}
		// 0 no debug
		// 1 pass hourtime
		// 2 pass dayrun and daytime
		
		$cfgh = $CFG->sync_h;
		$cfgm = $CFG->sync_m;
		
		$h = date('G');
		$m = date('i');
		
		$day = date("D");
		$var = 'sync_'.$day;
		
		$last = 0 + @$CFG->sync_lastrun;

		if ($last == 0) set_config('sync_dayrun', 0); // failtrap when never run and sync_lastrun not initialized

		$now = time();
		// $nextrun = $last + DAYSECS - 300; // assume we do it once a day
		
		$nextdate = $last + DAYSECS;
		$nextmidnight = mktime (0, 0, 0, date("n", $nextdate), date("j", $nextdate), date("Y", $nextdate));
		
		if (($now > $nextmidnight) && ($now > $last + $CFG->sync_ct)){
			echo "Reset ... as after $nextmidnight. \n";
			set_config('sync_dayrun', 0);
		}

		/*		
		$done = 2;
		if($now < $nextrun && !$debug && $last > 0){
			if ($now > $last + $CFG->sync_ct){
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
		
		if(($h == $cfgh) && ($m >= $cfgm) && !$CFG->sync_dayrun  || $debug){

			// we store that lock at start to lock any bouncing cron calls.
			set_config('sync_dayrun', 1);
		
			print_string('execstartsat', 'tool_sync', "$h:$m");
			echo "\n";
			
			$lockfile = "$CFG->dataroot/sync/locked.txt";
			$alock = "$CFG->dataroot/sync/alock.txt";
			
			if((file_exists($alock))||(file_exists($lockfile))){
				$log = "Synchronisation report\n \n";
				$log = $log . "Starting at: $h:$m \n";
				if (empty($CFG->sync_ct)) {	
				} else {
					$ct = $CFG->sync_ct;
					$file = @fopen($lockfile, 'r');
					$line = fgets($file);
					fclose($file);
					$i = time();
					
					$field = explode(':', $line);
					
					$last = $field[1] + 60 * $ct;
					
					if($now > $last){
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
				
				if (empty($CFG->sync_courseactivation)) {
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
					$coursesmanager = new courses_plugin_manager;
					$coursesmanager->cron();
					if(!empty($CFG->checkfilename)){
						$log .= "$CFG->checkfilename\n";
					}
					if(!empty($CFG->courselog)){
						$log .= "$CFG->courselog\n";
					}
					$str = get_string('endofprocess', 'tool_sync');	
					$str .= "\n\n";
					echo $str;
					$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
				}

				/// USER ACCOUNTS SYNC
				
				if (empty($CFG->sync_useractivation)) {
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
					$userpicturemanager->cron();
					if (!empty($CFG->userlog)){
						$log .= "$CFG->userlog\n";
					}
					$str = get_string('endofprocess', 'tool_sync');	
					$str .= "\n\n";
					echo $str;
					$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
				}

				/// USER AVATARS SYNC

				if (empty($CFG->sync_userpicturesactivation)) {
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
					$usersmanager->cron();
					if (!empty($CFG->userpictureslog)){
						$log .= "$CFG->userpictureslog\n";
					}
					$str = get_string('endofprocess', 'tool_sync');	
					$str .= "\n\n";
					echo $str;
					$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
				}

				/// ENROLLMENT SYNC
				
				if (empty($CFG->sync_enrolactivation)) {
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
					$enrolmanager->cron();
					if (!empty($CFG->enrollog)){
						$log .= "$CFG->enrollog\n";
					}
					$str = get_string('endofprocess', 'tool_sync');
					$str .= "\n\n";
					echo $str;
					$log .= $str."- - - - - - - - - - - - - - - - - - - -\n\n";
				}		

				/// GROUP CLEANUP
				
				if (empty($CFG->sync_enrolcleanup)) {
					$str = get_string('group_clean', 'tool_sync');
					$str .= ': ';
					$str .= get_string('disabled', 'tool_sync');
					$str .= "\n";
					$log .= $str;
					echo $str;
				} else {
					foreach($CFG->coursesg as $courseid){						
						$groups = groups_get_all_groups($courseid, 0, 0, 'g.*'); 						
						foreach($groups as $g){						
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

		/// creating and sending report

			if(!empty($log)){
				if (!is_dir($CFG->dataroot.'/sync/reports')){
					mkdir($CFG->dataroot.'/sync/reports', 0777);
				}
				$reportfilename = $CFG->dataroot.'/sync/reports/report-'.date('Ymd-Hi').'.txt';
				$reportfile = @fopen($reportfilename, 'wb');
				fputs($reportfile, $log);
				fclose($reportfile);

				if (!empty($CFG->enrol_mailadmins)) {
		            email_to_user(get_admin(), get_admin(), $SITE->shortname." : Enrol Sync Log", $log);
		        }
			}
		} else {
			if (!$CFG->sync_dayrun){
				echo "Course and user sync ... not yet. Waiting time ".sprintf('%02d', $cfgh).':'.sprintf('%02d', $cfgm) ."\n";
			} else {
				echo "Course and user sync ... already passed today, nothing to do. \n";
			}
		}
    } 
} 

?>

<?php

require_once('courses/courses.class.php');
require_once('users/users.class.php');
require_once('enrol/enrols.class.php');
require_once('userpictures/userpictures.class.php');

define('SYNC_COURSE_CHECK', 0x001);
define('SYNC_COURSE_CREATE', 0x002);
define('SYNC_COURSE_DELETE', 0x004);
define('SYNC_COURSE_CREATE_DELETE', 0x006);

/**
* prints a report to a log stream and output ir also to screen if required
*
*/
function tool_sync_report(&$report, $message, $onscreen = true){

	if (empty($report)) $report = '';
	if ($onscreen) mtrace($message);
	$report .= $message."\n";
}

/**
* Check a CSV input line format for empty or commented lines
* Ensures compatbility to UTF-8 BOM or unBOM formats
*/
function sync_is_empty_line_or_format(&$text, $resetfirst = false){
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
* prints a remote file upload for processing form
*
*/
function sync_print_remote_tool_portlet($titlekey, $targeturl, $filefieldname, $submitlabel, $return = false){
	global $CFG, $USER;
	
	$maxuploadsize = get_max_upload_file_size();

	$str = '<fieldset>';
	$str .= '<legend><strong>'.get_string($titlekey, 'tool_sync').'</strong></legend>';
	$str .= '<center>';
	$str .= '<form method="post" enctype="multipart/form-data" action="'.$targeturl.'">'.
		 ' <input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
		 '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
		 '<input type="file" name="'.$filefieldname.'" size="30">'.
		 ' <input type="submit" value="'.get_string($submitlabel, 'tool_sync').'">'.
		 '</form></br>';
	$str .= '</center>';
	$str .= '</fieldset>';

	if ($return) return $str;
	echo $str;
}

/**
* prints the form for using the registered commande file (locally on server)
*
*/
function sync_print_local_tool_portlet($config, $titlekey, $targeturl, $return = false){
	global $USER, $CFG;
	
	$str = '<fieldset>';
	$str .= '<legend><strong>'.get_string($titlekey, 'tool_sync').'</strong></legend><br/>';

	if(empty($config)){
	 	$nofilestoredstr = get_string('nofileconfigured', 'tool_sync');
		$str .= "<center>$nofilestoredstr<br/>";
	} else {
		if(file_exists($CFG->dataroot.'/'.$config)){
			$filestoredstr = get_string('storedfile', 'tool_sync', $config); 			
			$syncfilelocation = str_replace('sync/', '', $config);
			$str .= "<center>$filestoredstr. <a href=\"$CFG->wwwroot/admin/tool/sync/file.php?file=/{$syncfilelocation}&forcedownload=1\" target=\"_blank\">".get_string('getfile', 'tool_sync')."</a><br/><br/></center>";
			$str .= '<form method="post" action="'.$targeturl.'"><center>';
			$str .= '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
			$str .= '<input type="hidden" name="uselocal" value="1">';
			$str .= get_string('createtextreport', 'tool_sync');
			$str .= ' <input type="radio" name="report" value="1" checked/> '.get_string('yes').'. <input type=radio name="report" value="0"/> '.get_string('no').'<br/><br/>';
			$str .= ' <input type="submit" value="'.get_string('process', 'tool_sync').'">';
			$str .= '</center></form>';	
		} else {
			$filenotfoundstr = get_string('filenotfound', 'tool_sync', $config);
			$str .= "<center>$filenotfoundstr<br/><br/>";
		}
	}		 
	$str .= '</br></fieldset>';
	
	if ($return) return $str;
	echo $str;
}

function sync_print_return_button(){
	global $CFG, $OUTPUT;
	
	echo '<center>';
	echo '<hr/>';
	echo '<br/>';
	$url = new moodle_url($CFG->wwwroot.'/admin/tool/sync/index.php', array('sesskey' => sesskey()));
	$text = get_string('returntotools', 'tool_sync');
	$single_button = new single_button($url, $text, 'get');
	echo $OUTPUT->render($single_button);
	echo '<br/>';			 
	echo '</center>';
}

/**
* Get course and role assignations summary
* TODO : Rework for PostGre compatibility.
*/
function sync_get_all_courses($orderby = 'shortname'){
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
* Create and feeds tryback file with failed records from an origin command file
* @param string $originfilename the origin command fiale name the tryback name will be guessed from
* @param string $line the initial command line that has failed (and should be replayed after failure conditions have been fixed)
* @param mixed $header the header fields to be reproduced in the tryback file as a string, or an array of string.
*/
function sync_feed_tryback_file($originfilename, $line, $header = ''){
	global $CFG;
	
	static $TRYBACKFILE = null;
	static $ORIGINFILE = '';

	// guess the name of the tryback
	$path_parts = pathinfo($originfilename);
	$trybackfilename = $path_parts['dirname'].'/'.$path_parts['filename'].'_tryback_'.date('Ymd-Hi').'.'.$path_parts['extension'];
	
	// if changing dump, close opened
	if ($originfilename != $ORIGINFILE){
		if (!is_null($TRYBACKFILE)){
			fclose($TRYBACKFILE);
		}
		$TRYBACKFILE = fopen($trybackfilename, 'wb');
		$ORIGINFILE = $originfilename;
		if (!empty($header)){
			if (is_string($header)){
				fputs($TRYBACKFILE, $header."\n");
			} else {
				fputs($TRYBACKFILE, implode($CFG->tool_sync_csvseparator, $header)."\n");
			}
			fputs($TRYBACKFILE, '--------------------------------------------------'."\n");
		}
	}
	
	// dumpline
	fputs($TRYBACKFILE, $line."\n");
}

/**
 * Standard cron function
 */
function tool_sync_cron() {
    global $CFG, $USER, $SITE;

    mtrace('tool_sync_cron() started at '. date('H:i:s'));
	
	if (debugging()){ // ensures production platform cannot be attacked in deny of service that way
		$debug = optional_param('cronsyncdebug', 0, PARAM_INT); 
	}
	// 0 no debug
	// 1 pass hourtime
	// 2 pass dayrun and daytime
	
	$cfgh = 0 + @$CFG->tool_sync_h;
	$cfgm = 0 + @$CFG->tool_sync_m;
	
	$h = date('G');
	$m = date('i');
	
	$day = date("D");
	$var = 'tool_sync_'.$day;
	
	$last = 0 + @$CFG->tool_sync_lastrun; // internal

	if ($last == 0) set_config('tool_sync_dayrun', 0); // failtrap when never run and sync_lastrun not initialized

	$now = time();
	// $nextrun = $last + DAYSECS - 300; // assume we do it once a day
	
	$nextdate = $last + DAYSECS;
	$nextmidnight = mktime (0, 0, 0, date("n", $nextdate), date("j", $nextdate), date("Y", $nextdate));
	
	if (($now > $nextmidnight) && ($now > $last + @$CFG->tool_sync_ct) && !$debug){
		echo "Reset ... as after $nextmidnight. \n";
		set_config('tool_sync_dayrun', 0);
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
	
	if(($h == $cfgh) && ($m >= $cfgm) && !@$CFG->tool_sync_dayrun  || $debug){

		// we store that lock at start to lock any bouncing cron calls.
		set_config('tool_sync_dayrun', 1);
	
		print_string('execstartsat', 'tool_sync', "$h:$m");
		echo "\n";
		
		$lockfile = "$CFG->dataroot/sync/locked.txt";
		$alock = "$CFG->dataroot/sync/alock.txt";
		
		if((file_exists($alock))||(file_exists($lockfile))){
			$log = "Synchronisation report\n \n";
			$log = $log . "Starting at: $h:$m \n";
			if (empty($CFG->tool_sync_ct)) {	
			} else {
				$ct = $CFG->tool_sync_ct;
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
			
			if (empty($CFG->tool_sync_courseactivation)) {
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
				if(!empty($CFG->tool_sync_checkfilename)){
					$log .= "$CFG->tool_sync_checkfilename\n";
				}
				if(!empty($CFG->tool_sync_courselog)){
					$log .= "$CFG->tool_sync_courselog\n";
				}
				$str = get_string('endofprocess', 'tool_sync');	
				$str .= "\n\n";
				echo $str;
				$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
			}

			/// USER ACCOUNTS SYNC
			
			if (empty($CFG->tool_sync_useractivation)) {
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
				if (!empty($CFG->tool_sync_userlog)){
					$log .= "$CFG->tool_sync_userlog\n";
				}
				$str = get_string('endofprocess', 'tool_sync');	
				$str .= "\n\n";
				echo $str;
				$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
			}

			/// USER AVATARS SYNC

			if (empty($CFG->tool_sync_userpicturesactivation)) {
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
				if (!empty($CFG->tool_sync_userpictureslog)){
					$log .= "$CFG->tool_sync_userpictureslog\n";
				}
				$str = get_string('endofprocess', 'tool_sync');	
				$str .= "\n\n";
				echo $str;
				$log .= $str."- - - - - - - - - - - - - - - - - - - -\n \n";					
			}

			/// ENROLLMENT SYNC
			
			if (empty($CFG->tool_sync_enrolactivation)) {
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
				if (!empty($CFG->tool_sync_enrollog)){
					$log .= "$CFG->tool_sync_enrollog\n";
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
			set_config('tool_sync_lastrun', $now);
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

			if (!empty($CFG->tool_sync_enrol_mailadmins)) {
	            email_to_user(get_admin(), get_admin(), $SITE->shortname." : Enrol Sync Log", $log);
	        }
		}
	} else {
		if (!$CFG->tool_sync_dayrun){
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

?>
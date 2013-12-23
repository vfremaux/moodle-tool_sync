<?php
/**
* @author Funck Thibaut
* @package tool_sync
*
**/

require_once $CFG->dirroot.'/admin/tool/sync/lib.php';
require_once $CFG->dirroot.'/group/lib.php';

class enrol_plugin_manager {

    var $log;    

	/// Override the base config_form() function
	function config_form($frm) {
	    global $CFG, $DB;
	    
	    $vars = array('enrol_flatfilelocation', 'enrol_mailadmins', 'enrol_defaultcmd');

	    foreach ($vars as $var) {
	        if (!isset($frm->$var)) {
	            $frm->$var = '';
	        } 	
	    }
	    $roles = $DB->get_records('role', null, '', 'id, name, shortname');
	    $ffconfig = get_config('tool_sync_enrol_flatfile');
	    $frm->tool_sync_enrol_flatfilemapping = array();
	    foreach($roles as $id => $record) {
	    	$mapkey = "map_{$record->shortname}";
	        $frm->enrol_flatfilemapping[$id] = array(
	            $record->name,
	            isset($ffconfig->$mapkey) ? $ffconfig->$mapkey : $record->shortname
	        );
	    }	    
	    include ($CFG->dirroot.'/admin/tool/sync/enrol/config.html');    
	}

	/// Override the base process_config() function
	function process_config($config) {
	
	    if (!isset($config->tool_sync_enrol_filelocation)) {
	        $config->tool_sync_enrol_filelocation = '';
	    }
	    set_config('tool_sync_enrol_filelocation', $config->tool_sync_enrol_filelocation);

	    if (!isset($config->tool_sync_enrol_courseidentifier)) {
	        $config->tool_sync_enrol_courseidentifier = '';
	    }
	    set_config('tool_sync_enrol_courseidentifier', $config->tool_sync_enrol_courseidentifier);

	    if (!isset($config->tool_sync_enrol_useridentifier)) {
	        $config->tool_sync_enrol_useridentifier = '';
	    }
	    set_config('tool_sync_enrol_useridentifier', $config->tool_sync_enrol_useridentifier);

	    if (!isset($config->tool_sync_enrol_mailadmins)) {
	        $config->tool_sync_enrol_mailadmins = '';
	    }
	    set_config('tool_sync_enrol_mailadmins', $config->tool_sync_enrol_mailadmins);
		
	    if (!isset($config->tool_sync_enrol_defaultcmd)) {
	        $config->tool_sync_enrol_defaultcmd = '';
	    }
	    set_config('tool_sync_enrol_defaultcmd', $config->tool_sync_enrol_defaultcmd);
	    return true;
	}

    function cron() {
        global $CFG, $USER, $DB;
        
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
		
        if (empty($CFG->tool_sync_enrol_filelocation)) {
            $filename = $CFG->dataroot.'/sync/enrolments.txt';  // Default location
        } else {
            $filename = $CFG->dataroot.'/'.$CFG->tool_sync_enrol_filelocation;
        }

        if (!file_exists($filename) ) {
			tool_sync_report($CFG->tool_sync_enrollog, get_string('filenotfound', 'tool_sync', "$filename"));		
			return;
        }
        
		tool_sync_report($CFG->tool_sync_enrollog, get_string('flatfilefoundforenrols', 'tool_sync').$filename."\n");
		
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
		
		$fp = fopen($filename, 'rb');
		
		// jump any empty or comment line
		$text = fgets($fp, 1024);
		
		$i = 0;
		
		while(sync_is_empty_line_or_format($text, $i == 0)){
			$text = fgets($fp, 1024);
			$i++;
		}

		$headers = explode($csv_delimiter2, $text);
		
		function trim_fields(&$e){
			$e = trim($e);
		}
		
		array_walk($headers, 'trim_fields');
		
		foreach ($headers as $h) {				
			$header[] = trim($h); // remove whitespace			
			if (!(isset($required[$h]) or isset($optional[$h]))) {
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errorinvalidcolumnname', 'tool_sync', $h));
				return;
			}
			if (isset($required[$h])) {
				$required[$h] = 0;
			}
		}			
		foreach ($required as $key => $value) {
			if ($value) { //required field missing
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errorrequiredcolumn', 'tool_sync', $key));
				return;
			}
		}
		
		// Starting processing lines
		$i = 2;
		while (!feof ($fp)) {
			
			$record = array();

			$text = fgets($fp, 1024);
			if (sync_is_empty_line_or_format($text, false)) {
				$i++;
				continue;
			}
			$line = explode($csv_delimiter2, $text);

			foreach ($line as $key => $value) {
				//decode encoded commas
				$record[$header[$key]] = trim($value);
			}
			
			if (!array_key_exists('cmd', $record)) {
				$record['cmd'] = (empty($CFG->tool_sync_enrol_defaultcmd)) ? 'add' : $CFG->tool_sync_enrol_defaultcmd ;
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
			$cidentifiername = $cidentifieroptions[0 + @$CFG->tool_sync_enrol_courseidentifier];

			$uidentifieroptions = array('idnumber', 'username', 'email', 'id');
			$uidentifiername = $uidentifieroptions[0 + @$CFG->tool_sync_enrol_useridentifier];

			$e->myuser = $record['uid']; // user identifier
			$e->mycourse = $record['cid']; // course identifier

			if (!$user = $DB->get_record('user', array($uidentifiername => $record['uid'])) ) {
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errornouser', 'tool_sync', $e));
				$i++;
				if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
				continue;
			}

			$e->myuser = $user->username.' ('.$e->myuser.')'; // complete idnumber with real username

			if(empty($record['cid'])){
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errornullcourseidentifier', 'tool_sync', $i));
				$i++;
				if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
				continue;
			}

			if (!$course = $DB->get_record('course', array($cidentifiername => $record['cid'])) ) {
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errornocourse', 'tool_sync', $e));
				$i++;
				if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
				continue;
			}

			$CFG->tool_sync_coursesg[$i - 1] = $course->id;
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
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errornomanualenrol', 'tool_sync'));
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
						tool_sync_report($CFG->tool_sync_enrollog, get_string('unenrolled', 'tool_sync', $e));
					} catch (Exception $exc) {
						tool_sync_report($CFG->tool_sync_enrollog, get_string('errorunenrol', 'tool_sync', $e));
					}

				} else {
					if($role = $DB->get_record('role', array('shortname' => $record['rolename']))){
						// avoids weird behaviour of role assignement in other assignement admin
						$enrolcomponent = '';
						$enrolinstance = 0;
						if(!role_unassign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, time())){
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorunassign', 'tool_sync', $e));
						} else {
							tool_sync_report($CFG->tool_sync_enrollog, get_string('unassign', 'tool_sync', $e));
						}
					} else {
						if(!role_unassign(null, $user->id, $context->id, $enrolcomponent, $enrolinstance)){
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorunassign', 'tool_sync', $e));
						} else {
							tool_sync_report($CFG->tool_sync_enrollog, get_string('unassignall', 'tool_sync', $e));
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
							tool_sync_report($CFG->tool_sync_enrollog, get_string('enrolled', 'tool_sync', $e));
						} catch (Exception $exc){
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorenrol', 'tool_sync', $e));
						}
					} else {
						if(!$DB->get_record('role_assignments', array('roleid' => $role->id, 'contextid' => $context->id, 'userid' => $user->id, 'component' => ''))){
							if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, $record['starttime'])){
							// if(!role_assign($role->id, $user->id, $context->id)){
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
								tool_sync_report($CFG->tool_sync_enrollog, get_string('errorline', 'tool_sync')." $i : $mycmd $myrole $myuser $mycourse : $user->lastname $user->firstname == $role->shortname ==> $course->shortname");
							} else {
								tool_sync_report($CFG->tool_sync_enrollog, get_string('assign', 'tool_sync', $e));
							}
						} else {
							tool_sync_report($CFG->tool_sync_enrollog, get_string('alreadyassigned', 'tool_sync', $e));
						}
					}

				} else {
					if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
					tool_sync_report($CFG->tool_sync_enrollog, get_string('errornorole', 'tool_sync', $e));
				}
			} elseif ($record['cmd'] == 'shift'){

				// check this role exists in this moodle
				if ($role = $DB->get_record('role', array('shortname' => $record['rolename']))){

					// unenrol also unassign all roles
					if (!empty($record['enrol'])){
						try {
							$enrolplugin->unenrol_user($enrol, $user->id);
							tool_sync_report($CFG->tool_sync_enrollog, get_string('unenrolled', 'tool_sync', $e));
						} catch (Exception $exc) {
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorunenrol', 'tool_sync', $e));
						}
					} else {
						if ($roles = get_user_roles($context, $user->id)) {
							foreach ($roles as $r){
								// weird behaviour 
								$enrolcomponent = '';
								$enrolinstance = 0;
								if (!role_unassign($r->roleid, $user->id, $context->id, $enrolcomponent, $enrolinstance)){
									tool_sync_report($CFG->tool_sync_enrollog, get_string('unassignerror', 'tool_sync', $e));
								} else {
									tool_sync_report($CFG->tool_sync_enrollog, get_string('unassign', 'tool_sync', $e));
								}
							}
						}
					}

					// maybe we need enrol this user (if first time in shift list)
					// enrolement does perform role_assign
					if (!empty($record['enrol'])){
						try {
							$enrolplugin->enrol_user($enrol, $user->id, $role->id, $record['starttime'], $record['endtime'], ENROL_USER_ACTIVE);
							tool_sync_report($CFG->tool_sync_enrollog, get_string('enrolled', 'tool_sync', $e));
						} catch(Exception $exc){
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorenrol', 'tool_sync', $e));
						}
					} else {
						if (!role_assign($role->id, $user->id, $context->id, $enrolcomponent, $enrolinstance, $record['starttime'])){
							if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
							tool_sync_report($CFG->tool_sync_enrollog, get_string('errorassign', 'tool_sync', $e));
						} else {
							tool_sync_report($CFG->tool_sync_enrollog, get_string('assign', 'tool_sync', $e));
						}
					}

				} else {
					if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
					tool_sync_report($CFG->tool_sync_enrollog, get_string('errornorole', 'tool_sync', $e));
					$i++;
					continue;
				}
			} else {
				if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($filename, $text, $headers);
				tool_sync_report($CFG->tool_sync_enrollog, get_string('errorbadcmd', 'tool_sync', $e));
			}
			
			if (!empty($record['gcmd'])){
				if ($record['gcmd'] == 'gadd' || $record['gcmd'] == 'gaddcreate'){
					for ($i = 1 ; $i < 10 ; $i++){
						if(!empty($record['g'.$i])){
							if ($gid = groups_get_group_by_name($course->id, $record['g'.$i])) {
								$groupid[$i] = $gid;
							} else {
								if ($record['gcmd'] == 'gaddcreate'){
									$groupsettings->name = $record['g'.$i];
									$groupsettings->courseid = $course->id;
									if ($gid = groups_create_group($groupsettings)) {
										$groupid[$i] = $gid;
										$e->group = $record['g'.$i];
										tool_sync_report($CFG->tool_sync_enrollog, get_string('groupcreated', 'tool_sync', $e));
									} else {
										$e->group = $record['g'.$i];
										tool_sync_report($CFG->tool_sync_enrollog, get_string('errorgroupnotacreated', 'tool_sync', $e));
									}
								} else {
									$e->group = $record['g'.$i];
									tool_sync_report($CFG->tool_sync_enrollog, get_string('groupunknown','tool_sync',$e));
									continue;
								}
							}

							$e->group = $record['g'.$i];
							
							if (count(get_user_roles($context, $user->id))) {
								if (groups_add_member($groupid[$i], $user->id)) {
									tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroup','tool_sync',$e));
								} else {
									tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroupnot','tool_sync',$e));
								}
							} else {
								tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroupnotenrolled','',$record['g'.$i]));
							}
						}
					}
				} elseif ($record['gcmd'] == 'greplace' || $record['gcmd'] == 'greplacecreate'){
					groups_delete_group_members($course->id, $user->id); 
					tool_sync_report($CFG->tool_sync_enrollog, get_string('groupassigndeleted', 'tool_sync', $e));
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
										tool_sync_report($CFG->tool_sync_enrollog, get_string('groupcreated', 'tool_sync', $e));
									} else {
										$e->group = $record['g'.$i];
										tool_sync_report($CFG->tool_sync_enrollog, get_string('errorgroupnotacreated', 'tool_sync', $e));
									}
								} else {
									$e->group = $record['g'.$i];
									tool_sync_report($CFG->tool_sync_enrollog, get_string('groupunknown','tool_sync',$e));
								}
							}
							
							if (count(get_user_roles($context, $user->id))) {
								if (groups_add_member($groupid[$i], $user->id)) {
									tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroup','tool_sync',$e));
								} else {
									tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroupnot','tool_sync',$e));
								}
							} else {
								tool_sync_report($CFG->tool_sync_enrollog, get_string('addedtogroupnotenrolled','',$record['g'.$i]));
							}
						}
					}								
				} else {
					tool_sync_report($CFG->tool_sync_enrollog, get_string('errorgcmdvalue', 'tool_sync', $e));
				}
			}							
			//echo "\n";
			$i++;
		}
		
		if (!empty($CFG->tool_sync_filearchive)){
			$archivename = basename($filename);
			$now = date('Ymd-hi', time());
			$archivename = $CFG->dataroot."/sync/archives/{$now}_enrolments_$archivename";
			copy($filename, $archivename);
		}
		
		if (!empty($CFG->tool_sync_filecleanup)){
			@unlink($filename);
		}		
		
		tool_sync_report($CFG->tool_sync_enrollog, "\n".get_string('endofreport', 'tool_sync'));
		
		return true;
    }
}

?>
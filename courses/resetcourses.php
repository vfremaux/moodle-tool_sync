
<?php
/**
* @author Funck Thibaut
* @package tool_sync
*/

	require_once('../../../../config.php');
	require_once($CFG->dirroot."/course/lib.php");
	require_once($CFG->libdir.'/adminlib.php');	
	require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

	require_login();

	if (!is_siteadmin()) {
        print_error('erroradminrequired', 'tool_sync');
    }
	if (! $site = get_site()) {
        print_error('errornosite', 'tool_sync');
    }
	if (!$adminuser = get_admin()) {
        print_error('errornoadmin', 'tool_sync');
    }

	$url = $CFG->wwwroot.'/admin/tool/sync/courses/resetcourses.php';
	$PAGE->set_url($url);
	$PAGE->set_context(null);
	$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
	$PAGE->navigation->add(get_string('coursereset', 'tool_sync'), null);
	$PAGE->set_title("$site->shortname");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();

	echo $OUTPUT->heading(get_string('resettingcourses', 'tool_sync'));

	sync_print_remote_tool_portlet('importfile', $CFG->wwwroot.'/admin/tool/sync/courses/resetcourses.php', 'resetcourse', 'upload');	
	sync_print_local_tool_portlet(@$CFG->tool_sync_reset_course_file, 'commandfile', 'resetcourses.php');
	require_once($CFG->dirroot.'/lib/uploadlib.php');			 

	// If there is a file to upload... do it... else do the rest of the stuff
	$um = new upload_manager('resetcourses', false, false, null, false, 0);

	$identifieroptions = array('idnumber', 'shortname', 'id');
	$identifiername = $identifieroptions[0 + @$CFG->tool_sync_course_resetfileidentifier];

    if ($um->preprocess_files() || isset($_POST['uselocal'])) {
		$now = date("m-d-y");
		$reportname = $CFG->dataroot."/sync/reports/reset[".$now."].txt";		
		$report = @fopen($reportname,"w");
		fputs($report, "-----------------------------------\n");
		fputs($report, "-- Course reset report ( $now ) --\n");
		fputs($report, "-----------------------------------\n\n");
		// All file processing stuff will go here. ID=2...
  		echo $OUTPUT->notification('Parsing file...', 'notifysuccess');
        if (isset($um->files['resetcourses'])) {
			$filename = $um->files['resetcourses']['tmp_name'];		
		}
		$uselocal = optional_param('uselocal', false, PARAM_BOOL);
		if(!empty($uselocal)){
			$filename = $CFG->tool_sync_reset_course_file;
			$filename = $CFG->dataroot."/".$filename;
		}
        if (file_exists($filename)) {
			$required = array(
					$identifiername => 1,
					'events' => 1,
					'logs' => 1,
					'notes' => 1,
					'grades' => 1,
					'roles' => 1,
					'groups' => 1,
					'modules' => 1);
			$optional = array(
					'forum_all' => 1,
					'forum_subscriptions' => 1,  /*forum*/          
					'glossary_all' => 1, /*glossary*/
					'chat' => 1, /*chat*/
					'data' => 1, /*database*/
					'slots' => 1, /*scheduler*/
					'apointments' => 1,
					'assignment_submissions' => 1, /*assignment*/
					'survey_answers' => 1, /*survey*/
					'lesson' => 1, /*lesson*/
					'choice' => 1,
					'scorm' => 1,
					'quiz_attempts' => 1);		

			if ($allmods = $DB->get_records('modules') ) {
				foreach ($allmods as $mod) {
					$modname = $mod->name;
					$modfile = $CFG->dirroot."/mod/{$modname}/resetlib.php";
					$mod_reset_course_form_definition = $modname.'_reset_course_form_definition';
					if (file_exists($modfile)) {
						include_once($modfile);
						if (function_exists($mod_reset_course_form_definition)) {
							$vars = $mod_reset_course_form_definition();
							foreach($vars as $var){
								$optional[$var] = 1;
							}
						}
					} 
				}
			}
			$fp = fopen($filename,'rb');

			$text = fgets($fp, 1024);
			$i = 0;
			// skip comments and empty lines
			while (sync_is_empty_line_or_format($text, $i == 0)){
				$text = fgets($fp, 1024);
				$i++;
				continue;
			}			
			$header = explode($CFG->tool_sync_csvseparator, $text);

			function trim_elements(&$e){
				$e = trim($e); // remove whitespaces
			}
						
			array_walk($header, 'trim_elements');
			
			foreach ($header as $h) {				
				if (!isset($required[$h]) and !isset($optional[$h])) {				
					fputs($report,"[Error] column name:\n");
					fputs($report,"$h is not a valid field name");
					fputs($report,"\n\n");
					tool_sync_report($CFG->tool_sync_resetlog, get_string('invalidfieldname', 'error', $h));
					return;
				}
				if (isset($required[$h])) {
					$required[$h] = 0;
				}
			}
			foreach ($required as $key => $value) {
				if ($value) { //required field missing
					tool_sync_log($CFG->tool_sync_resetlog, get_string('fieldrequired', 'error', $key));			
					return;
				}
			}		
			while (!feof ($fp)) {
				$text = fgets($fp, 1024);

				if (sync_is_empty_line_or_format($text, false)){
					continue;
				}

				$line = explode($CFG->tool_sync_csvseparator, $text);
				foreach ($line as $key => $value) {
					//decode encoded commas
					$record[$header[$key]] = trim($value);						
				}		
				$data['reset_start_date'] = 0;

				// Adaptative identifier
				
				if (@$CFG->tool_sync_course_resetfileidentifier == 0 && $DB->count_records('course', array('idnumber' => $record['idnumber']))){
					tool_sync_report($CFG->tool_sync_resetlog, get_string('nonuniqueidentifierexception', 'tool_sync', $i));
					continue;
				}

				if ($course = $DB->get_record('course', array($identifiername => $record[$identifiername])) ) {		
					$data['id'] = $course->id;
					$data['reset_start_date_old'] = $course->startdate;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('unkownshortname', 'tool_sync', $i));
					continue;
				}
				tool_sync_report($CFG->tool_sync_resetlog, get_string('resettingcourse', 'tool_sync').$course->fullname.' ('.$course->shortname.')');

				// processing events
				if ($record['events'] == 'yes') {		
					$data['reset_events'] = 1;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('noeventstoprocess', 'tool_sync', $i), false);
				}

				// processing logs
				if ($record['logs'] == 'yes') {		
					$data['reset_logs'] = 1;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('nologstoprocess', 'tool_sync', $i), false);
				}
				// processing notes
				if ($record['notes'] == 'yes') {
					$data['reset_notes'] = 1;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('nonotestoprocess', 'tool_sync', $i), false);
				}			

				// processing grades
				if ($record["grades"] == 'items') {		
					$data['reset_gradebook_items'] = 1;
				} else if ($record['grades'] == 'grades'){
					$data['reset_gradebook_grades'] = 1;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('nogradestoprocess', 'tool_sync', $i), false);
				}							
				// processing role assignations
				$roles = explode(' ', $record['roles']);
				$reset_roles = array();
				$nbrole = 0;
				foreach($roles as $rolename){
					if($role = $DB->get_record('role', array('shortname' => $rolename))){
						$reset_roles[$nbrole] = $role->id;
						$data['reset_roles'] = $reset_roles;
						$nbrole++;
					} else {
						tool_sync_report($CFG->tool_sync_resetlog, "[Error] role $rolename unkown.\n", false);
					}
				}
				// processing groups
				if ($record['groups'] == 'groups') {		
					$data['reset_groups_remove'] = 1;
				} else if ($record['groups'] == 'members'){
					$data['reset_groups_members'] = 1;
				} else {
					tool_sync_report($CFG->tool_sync_resetlog, get_string('nogrouptoprocess', 'tool_sync', $i), false);
				}													

				echo '<br/>';

				// processing course modules
				if ($allmods = $DB->get_records('modules') ) {
					$modmap = array();		
					$modlist = array();
					$allmodsname = array();
					foreach ($allmods as $mod) {
						$modname = $mod->name;
						$allmodsname[$modname] = 1;
						if (!$DB->count_records($modname, array('course' => $data['id']))) {
							continue; // skip mods with no instances
						}
						$modlist[$modname] = 1;
						$modfile = $CFG->dirroot."/mod/$modname/lib.php";
						$mod_reset_course_form_definition = $modname.'_reset_course_form_defaults';
						$mod_reset_userdata = $modname.'_reset_userdata';
						if (file_exists($modfile)) {
							include_once($modfile);
							if (function_exists($mod_reset_course_form_definition)) {
								$modmap[$modname] = $mod_reset_course_form_definition($data['id']);
							} else if (!function_exists($mod_reset_userdata)) {
								$unsupported_mods[] = $mod;
							}
						} else {
							debugging('Missing lib.php in '.$modname.' module');
						}
					}
				}
				$avalablemods = array();
				foreach($modmap as $modname => $mod){
					foreach($mod as $key => $value){
						$avalablemods[$modname][$key] = $value;
					}
				}							
				if (count($header)>8){
					if((isset($record['forum_all']))&&($record['forum_all'] == 1)){
						$data['reset_forum_all'] = 1;	
					}
					if((isset($record['forum_subscriptions'])) && ($record['forum_subscriptions'] == 1)){
						$data['reset_forum_subscriptions'] = 1;	
					}		
					if((isset($record['glossary_all'])) && ($record['glossary_all'] == 1)){
						$data['reset_glossary_all'] = 1;	
					}		
					if((isset($record['chat'])) && ($record['chat'] == 1)){
						$data['reset_chat'] = 1;	
					}			
					if((isset($record['data'])) && ($record['data'] == 1)){
						$data['reset_data'] = 1;	
					}			
					if((isset($record['slots']))&&($record['slots'] == 1)){
						$data['reset_slots'] = 1;	
					}			
					if((isset($record['apointments'])) && ($record['apointments'] == 1)){
						$data['reset_apointments'] = 1;	
					}		
					if((isset($record['assignment_submissions'])) && ($record['assignment_submissions'] == 1)){
						$data['reset_assignment_submissions'] = 1;	
					}		
					if((isset($record['survey_answers'])) && ($record['survey_answers'] == 1)){
						$data['reset_survey_answers'] = 1;	
					}					
					if((isset($record['lesson']))&&($record['lesson'] == 1)){
						$data['reset_lesson'] = 1;	
					}											
					if((isset($record['choice']))&&($record['choice'] == 1)){
						$data['reset_choice'] = 1;	
					}		
					if((isset($record['scorm'])) && ($record['scorm'] == 1)){
						$data['reset_scorm'] = 1;	
					}					
					if((isset($record['quiz_attempts'])) && ($record['quiz_attempts'] == 1)){
						$data['reset_quiz_attempts'] = 1;	
					}				
				} else {
					$mods = explode(' ', $record['modules']);
					$nbmods = 0;
					$modlist = array();
					foreach($mods as $mod){
						$modlist[$mod] = 1;
					}
					if(isset($modlist['all']) && ($modlist['all'] == 1)){
						foreach($avalablemods as $modname => $fcts){
							foreach($fcts as $fct => $value){
								$data[$fct]=$value;
							}
						}					
					} else {
						if(!isset($modlist[''])){
							$neg = 0;
							$negmods = array();
							foreach($modlist as $mymod => $value){									
								if($mymod[0] == '-'){
									$neg = 1;
									$modnam = substr($mymod,1);
									$negmods[$modnam]=1;
								}
							}
							if($neg == 1){
								foreach($avalablemods as $modname => $fcts){
									foreach($fcts as $fct => $value){
										$data[$fct]=$value;
									}
								}
								foreach($negmods as $k => $v){
									foreach($avalablemods as $mod => $fcts){
										if($k == $mod){
											foreach($fcts as $fct => $value){
												unset($data[$fct]);
											}
										}
									}							
								}
							}
							if($neg == 0){
								foreach($avalablemods as $modname => $fcts){
									if(isset($modlist[$modname]) && ($modlist[$modname] == 1)){
										foreach($fcts as $fct => $value){
											$data[$fct] = $value;
										}
									}
								}		
							}
						}
					}
				}
				$data = (object)$data;

				//	print_object($data);
				$status = reset_course_userdata($data);

				// array operation ligne par ligne avec array component / item / error
				tool_sync_report($CFG->tool_sync_resetlog, "Summary:", false);
				foreach($status as $line){

					$str = $line['component'] ." : ";
					if(!empty($line['item'])){ 
						$str .= $line['item'] ." : ";
					}
					if(empty($line['error'])){ 
						$str .= 'OK';
					} else {
						$str .= $line['error'];
					}
					tool_sync_report($CFG->tool_sync_resetlog, $str, false);
				}
				$data = array();;
				foreach ($status as $item) {
					$line = array();
					$line[] = $item['component'];
					$line[] = $item['item'];
					$line[] = ($item['error'] === false) ? get_string('ok') : '<div class="notifyproblem">'.$item['error'].'</div>';
					$data[] = $line;
				}

				$table = new html_table();
				$table->head  = array(get_string('resetcomponent'), get_string('resettask'), get_string('resetstatus'));
				$table->size  = array('20%', '40%', '40%');
				$table->align = array('left', 'left', 'left');
				$table->width = '80%';
				$table->data  = $data;

				echo '<fieldset>';
				echo html_writer::table($table);				
				echo '</fieldset>';
			}
		}
    } 


	// always return to main tool view.
	sync_print_return_button();

	echo $OUTPUT->footer();
?>
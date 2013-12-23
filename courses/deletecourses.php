<?php
/* 
 * A moodle addon to quickly remove a number of courses by uploading an
 *       unformatted text file containing the shortnames of the courses
 *       each on its own line
 *
 *
 * Date: 4/11/07
 * Date review: 03/05/11
 * Employed By; Appalachian State University
 *
 * $productname = "Bulk Course Delete";
 * $version = "v1.1";
 * $author = "Ashley Gooding & Cole Spicer";
 * &reviewer = "Funck Thibaut";
 *
 */

	require_once('../../../../config.php');
	require_once($CFG->dirroot.'/course/lib.php');
	require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->libdir.'/moodlelib.php');
	require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
	require_once($CFG->dirroot.'/lib/uploadlib.php');	
	
	require_login();	 

// security
	if (!is_siteadmin()) {
        print_error('erroradminrequired', 'tool_sync');
    }
	if (! $site = get_site()) {
        print_error('errornosite', 'tool_sync');
    }
	if (!$adminuser = get_admin()) {
        print_error('errornoadmin', 'tool_sync');
    }

	$strenrolname = get_string('enrolname', 'tool_sync');
	$strdeletecourses = get_string('coursedeletion', 'tool_sync');
	$strchoose = get_string('choose');
	
	set_time_limit(300);

	list($usec, $sec) = explode(' ', microtime());
    $time_start = ((float)$usec + (float)$sec);
    $url = $CFG->wwwroot.'/admin/tool/sync/courses/deletecourses.php';
	$PAGE->set_context(null);
	$PAGE->navigation->add($strenrolname);
	$PAGE->navigation->add($strdeletecourses);
	$PAGE->set_url($url);
	$PAGE->set_title("$site->shortname: $strdeletecourses");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();
	echo $OUTPUT->heading_with_help(get_string('coursedeletion', 'tool_sync'), 'coursedeletion', 'tool_sync');

// Page controller

	$identifiers = array();
	if(!isset($_POST['ids'])) {

		// Start page... display instructions and upload buttons etc...
		echo $OUTPUT->box_start();
		print_string('deletefileinstructions', 'tool_sync');
		echo $OUTPUT->box_end();
		
		echo '<br/><br/>';
			
		sync_print_remote_tool_portlet('deletefromremote', $CFG->wwwroot.'/admin/tool/sync/courses/deletecourses.php', 'deletefileupload', 'upload');
		sync_print_local_tool_portlet(@$CFG->tool_sync_course_filedeletelocation, 'deletefile', 'deletecourses.php');
	
		// If there is a file to upload... do it... else do the rest of the stuff
		
		$um = new upload_manager('deletefileupload', false, false, null, false, 0);
		
	    if ($um->preprocess_files() || isset($_POST['uselocal'])) {

			// All file processing stuff will go here. ID=2...
		    if (isset($um->files['deletefileupload'])) {
				// All file processing stuff will go here. ID=2...
		  		echo $OUTPUT->notification(get_string('parsingfile', 'tool_sync'), 'notifysuccess');		
		  		$systemfilename = $um->files['deletefileupload']['tmp_name'];
		  		$filename = $CFG->dataroot.'/temp/'.basename($systemfilename);
		  		copy($systemfilename, $filename);
		  		@unlink($systemfilename);
			} 
	
			$uselocal = optional_param('uselocal', false, PARAM_BOOL);
			if(!empty($uselocal)){
				$filename = $CFG->tool_sync_course_filedeletelocation;
				$filename = $CFG->dataroot.'/'.$filename;
			}
		}

		// now with a filename build the confirmation form		
		if (isset($filename)){
			$i = 0;
			$file = @fopen($filename, 'rb', 0);

			if ($file) {
				while (!feof($file)) {
					$text = fgets($file, 1024);
					
					if (sync_is_empty_line_or_format($text, $i == 0)){
						$i++;
						continue;
					}
					$identifiers[] = trim($text);
				}
				fclose($file);
			}
						
			// Ok now we have the file in a proper format... lets parse it for course
			//    shortnames and show the list of courses with id #s followed by a confirm button
			
			// Fill this with a list of comma seperated id numbers to delete courses.
			$deleteids = '';
			$idnums = array();
			
			$identifieroptions = array('idnumber', 'shortname', 'id');
			$identifiername = $identifieroptions[0 + @$CFG->tool_sync_course_filedeleteidentifier];
			$report = optional_param('report', false, PARAM_BOOL);
			
			foreach($identifiers as $cid) {
				if(!($c = $DB->get_record('course', array($identifiername => $cid))) ) {
					// Say we couldnt find that course
					echo $OUTPUT->notification(get_string('coursenodeleteadvice', 'tool_sync', $cid), 'notifyproblem');
					continue;
				}
				
				if(!empty($idnums[$c->id])) {
					continue;
				} else {
					$idnums[$c->id] = $c->fullname;
				}
			}
			
			// Remove last comma
			$deleteids = '';
			if (!empty($idnums)){
				$deleteids = implode(',', array_keys($idnums));
			}
			
			// Show execute time
			list($usec, $sec) = explode(' ', microtime());
	    	$time_end = ((float)$usec + (float)$sec);
	        echo $OUTPUT->notification(get_string('totaltime', 'tool_sync').' '.round(($time_end - $time_start),2).' s', 'notifysuccess');
			
			echo '<center>';
			echo '<hr /><br />'.get_string('predeletewarning', 'tool_sync').'<br />';
			echo '<p><table border="0">';
			echo '<tr><td width="50" align="left"><b> ID </b></td><td align="left"><b>'.get_string('coursefullname', 'tool_sync').'</b></td></tr>';
			foreach($idnums as $id => $name) {
				echo '<tr><td align="left">'.$id.'</td><td align="left">'.$name.'</td></tr>';
			}
			echo '</table></p>';
			echo '<br />'.get_string('deletecoursesconfirmquestion', 'tool_sync').'<br /><br />';
					
			echo '<table border="0"><tr><td>';
			if (!empty($idnums)){
				echo '<form method="post" action="deletecourses.php">';
				echo '<input type="hidden" name="ids" value="'.$deleteids.'">';
				echo '<input type="hidden" name="using" value="'.basename($filename).'">';
				echo '<input type="submit" value="'.get_string('confirm', 'tool_sync').'">';
				echo '</form>';
			}
			echo '</td><td>';
			echo '<form method="post" action="deletecourses.php">';
			echo '<input type="submit" name="cancel" value="'.get_string('cancel').'">';
			echo '</form></td></tr></table></br>';
			echo '</center>';

			echo $OUTPUT->footer();
			die;
		} else if (!isset($_POST['cancel'])) {
			echo $OUTPUT->notification(get_string('nofile', 'tool_sync'), 'notifyproblem');
		}
		
	} else {
		// We got passed a list of id's to delete... they pressed the confirm button. Go ahead and delete the courses
		
		$ids = optional_param('ids', '', PARAM_TEXT);
		if (!empty($ids)){
		
			$count = 0;
			
			$idarr = explode(',', $ids);
			foreach($idarr as $id) {
				if(!delete_course($id, false)) {
					tool_sync_report($CFG->tool_sync_deletereport, get_string('errorcoursedeletion', 'tool_sync', $id));
				} else {
					$count++;
				}
			}
			
			fix_course_sortorder();
	
			tool_sync_report($CFG->tool_sync_deletereport, get_string('coursedeleted', 'tool_sync', $count));
		}
		
		// Show execute time
		list($usec, $sec) = explode(' ', microtime());
    	$time_end = ((float)$usec + (float)$sec);
        tool_sync_report($CFG->tool_sync_deletereport, get_string('totaltime', 'tool_sync').' '.round(($time_end - $time_start),2).' s');		

		if (!empty($CFG->sync_filearchive)){
			$filename = $CFG->dataroot.'/temp/'.required_param('using', PARAM_TEXT);
			$archivename = basename($filename);
			$now = date('Ymd-hi', time());
			$archivename = $CFG->dataroot."/sync/archives/deletion_{$now}_$archivename";
			copy($filename, $archivename);
		}
		
		if (!empty($CFG->tool_sync_filecleanup)){
			@unlink($filename);
		}
 	}

	// always return to main tool view.
	sync_print_return_button();

	echo $OUTPUT->footer();
?>
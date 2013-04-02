<?php
/* 
 * A moodle addon to quickly remove all empty categories and cleanup category tree
 *
 * Date: 23/12/2012
 * Date review: 23/12/2012
 *
 * $productname = "";
 * $version = "v1.1";
 * $author = "Valery Fremaux";
 *
 */

	require_once('../../../../config.php');
	require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
	require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');

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

	$cleancatnamestr = get_string('cleancategories', 'tool_sync');
	
	set_time_limit(300);

	list($usec, $sec) = explode(' ', microtime());
    $time_start = ((float)$usec + (float)$sec);
    $url = $CFG->wwwroot.'/admin/tool/sync/courses/clearemptycategories.php';
	$PAGE->set_context(null);
	$PAGE->set_url($url);
	$PAGE->navigation->add($cleancatnamestr);
	$PAGE->set_title("$site->shortname: $cleancatnamestr");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();
	echo $OUTPUT->heading_with_help(get_string('cleancategories', 'tool_sync'), 'cleancategories', 'tool_sync');

// Page controller

	if(!isset($_POST['ids'])) {

		echo '<center>';
		echo '<table width="70%">';
		$path = '';
		sync_scan_empty_categories(0, $catids, $path);
		echo '</table>';

		if (!empty($catids)){
			$deleteids = implode(',', $catids);

			echo '<form method="post" action="clearemptycategories.php">';
			echo '<input type="hidden" name="ids" value="'.$deleteids.'">';
			echo '<input type="submit" value="'.get_string('confirm', 'tool_sync').'">';
			echo '</form>';
		} else if (!isset($_POST['cancel'])) {
			echo $OUTPUT->notification(get_string('nothingtodelete', 'tool_sync'), 'notifyproblem');
		}
		echo '</center>';
	} else {
		// We got passed a list of id's to delete... they pressed the confirm button. Go ahead and delete the courses
		
		$ids = optional_param('ids', '', PARAM_TEXT);
		if (!empty($ids)){
		
			$count = 0;
			
			$idarr = explode(',', $ids);
			echo '<pre>';
			foreach($idarr as $id) {
				$deletedcat = $DB->get_record('course_categories', array('id' => $id));
				if ($DB->delete_records('course_categories', array('id' => $id))){
					if(delete_context(CONTEXT_COURSECAT, $id)) {
						tool_sync_report($CFG->tool_sync_deletereport, get_string('categoryremoved', 'tool_sync', $deletedcat->name));
						$count++;
					} else {
						tool_sync_report($CFG->tool_sync_deletereport, get_string('errorcategorycontextdeletion', 'tool_sync', $id));
					}
				} else {
					tool_sync_report($CFG->tool_sync_deletereport, get_string('errorcategorydeletion', 'tool_sync', $id));
				}
			}
			
			tool_sync_report($CFG->tool_sync_deletereport, get_string('ncategoriesdeleted', 'tool_sync', $count));
			echo '</pre>';
		}
		
		// Show execute time
		list($usec, $sec) = explode(' ', microtime());
    	$time_end = ((float)$usec + (float)$sec);
        tool_sync_report($CFG->tool_sync_deletereport, get_string('totaltime', 'tool_sync').' '.round(($time_end - $time_start),2).' s');				
 	}

	// always return to main tool view.
	sync_print_return_button();

	echo $OUTPUT->footer();
?>
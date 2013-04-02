<?php  	   // author - Funck Thibaut

    require_once('../../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->dirroot."/course/lib.php");

	set_time_limit(1800);
	raise_memory_limit('512M');	
	
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

	require_once("$CFG->dirroot/admin/tool/sync/courses/courses.class.php");

	$url = $CFG->wwwroot.'/admin/tool/sync/courses/execcron.php';
	$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
	$PAGE->navigation->add(get_string('coursesync', 'tool_sync'));
	$PAGE->set_url($url);
	$PAGE->set_context(null);
	$PAGE->set_title("$site->shortname");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();
	echo $OUTPUT->heading_with_help(get_string('coursesync', 'tool_sync'), 'coursesync', 'tool_sync');

	// execron do everything a cron will do
    $coursesmanager = new courses_plugin_manager(null, SYNC_COURSE_CHECK | SYNC_COURSE_DELETE | SYNC_COURSE_CREATE);

	$coursesmanager->process_config($CFG);

	echo $OUTPUT->heading(get_string('coursemanualsync', 'tool_sync'), 3);

	$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $CFG->tool_sync_course_fileexistlocation);
	echo "<center>$cronrunmsg</center>";

	$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $CFG->tool_sync_course_filedeletelocation);
	echo "<center>$cronrunmsg</center>";

	$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $CFG->tool_sync_course_fileuploadlocation);
	echo "<center>$cronrunmsg</center>";

	echo $OUTPUT->heading(get_string('processresult', 'tool_sync'), 3);

	echo '<pre>';
	$coursesmanager->cron();
	echo '</pre>';

	sync_print_return_button();

	echo $OUTPUT->footer();

///    exit;
?>

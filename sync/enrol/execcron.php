<?PHP  	   // author - Funck Thibaut

    require_once("../../../../config.php");
    require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->dirroot.'/course/lib.php');

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

	set_time_limit(1800);
	raise_memory_limit('512M');		

	$url = $CFG->wwwroot.'/admin/tool/sync/enrol/execcron.php';
	$PAGE->set_url($url);
	$PAGE->set_context(null);
	$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
	$PAGE->navigation->add(get_string('enrolmgtmanual', 'tool_sync'));
	$PAGE->set_title("$site->shortname");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();
	echo $OUTPUT->heading_with_help(get_string('enrolsync', 'tool_sync'), 'enrolsync', 'tool_sync');

	require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
    $enrolmanager = new enrol_plugin_manager;

	$enrolmanager->process_config($CFG);

	echo $OUTPUT->heading(get_string('enrolmanualsync', 'tool_sync'), 3);

	$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $CFG->tool_sync_enrol_filelocation);
	echo "<center>$cronrunmsg</center>";

	echo $OUTPUT->heading(get_string('processresult', 'tool_sync'), 3);

	echo "<pre>";
	$enrolmanager->cron();
	echo "</pre>";

	sync_print_return_button();

	echo $OUTPUT->footer();

///    exit;
?>

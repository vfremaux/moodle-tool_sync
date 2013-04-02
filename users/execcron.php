<?php
	   // author - Funck Thibaut

    require_once("../../../../config.php");
    require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->libdir.'/moodlelib.php');
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

	require_once("$CFG->dirroot/admin/tool/sync/users/users.class.php");
	$usersmanager = new users_plugin_manager;

	$url = $CFG->wwwroot.'/admin/tool/sync/users/execron.php';
	$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
	$PAGE->navigation->add(get_string('usermgtmanual', 'tool_sync'));
	$PAGE->set_url($url);
	$PAGE->set_context(null);
	$PAGE->set_title("$site->shortname");
	$PAGE->set_heading($site->fullname);

	echo $OUTPUT->header();

	echo $OUTPUT->heading_with_help(get_string('usermgtmanual', 'tool_sync'), 'usersync', 'tool_sync');

	$usersmanager->process_config($CFG);
	echo '<pre>';
	$usersmanager->cron();
	echo '</pre>';
	$address = $CFG->tool_sync_users_filelocation;

	$usermgtmanual = get_string('usermgtmanual', 'tool_sync');
	$cronrunmsg = get_string('cronrunmsg', 'tool_sync', $address);

	echo "<br/><fieldset><legend><strong>$usermgtmanual</strong></legend>";
	echo "<center>$cronrunmsg</center>";
	echo '</fieldset>';

	// always return to main tool view.
	sync_print_return_button();

	echo $OUTPUT->footer();
///    exit;
?>

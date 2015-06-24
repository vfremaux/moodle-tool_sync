<?php

/**
 * author - Funck Thibaut
 */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/inputfileload_form.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}

// Capture incoming files in <moodledata>/sync.
tool_sync_capture_input_files(false);

set_time_limit(1800);
raise_memory_limit('512M');

$renderer = $PAGE->get_renderer('tool_sync');
$syncconfig = get_config('tool_sync');

$url = $CFG->wwwroot.'/admin/tool/sync/enrol/execcron.php';
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('enrolmgtmanual', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_title("$SITE->shortname");
$PAGE->set_heading($SITE->fullname);

$form = new InputfileLoadform($url, array('localfile' => $syncconfig->enrol_filelocation));

$canprocess = false;

if ($data = $form->get_data()) {

    if (!empty($data->uselocal)) {
        // Use the server side stored file.
        $enrolsmanager = new enrol_plugin_manager();
        $processedfile = $syncconfig->enrol_filelocation;
        $canprocess = true;
    } else {
        // Use the just uploaded file.

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->inputfile)) {

            $areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->inputfile);

            // Take last as former is the / directory
            $uploadedfile = array_pop($areafiles);

            $manualfilerec = new StdClass();
            $manualfilerec->contextid = $usercontext->id;
            $manualfilerec->component = 'user';
            $manualfilerec->filearea = 'draft';
            $manualfilerec->itemid = $data->inputfile;
            $manualfilerec->filepath = $uploadedfile->get_filepath();
            $manualfilerec->filename = $uploadedfile->get_filename();
            $processedfile = $manualfilerec->filename;
    
            $enrolsmanager = new enrol_plugin_manager($manualfilerec);
            $canprocess = true;
        } else {
            $errormes = "Failed loading a file";
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading_with_help(get_string('enrolmgtmanual', 'tool_sync'), 'enrolsync', 'tool_sync');

$form->display();

if ($canprocess) {
    echo '<pre>';
    $enrolsmanager->cron($syncconfig);
    echo '</pre>';

    $enrolmgtmanual = get_string('enrolmgtmanual', 'tool_sync');
    $cronrunmsg = get_string('cronrunmsg', 'tool_sync', $processedfile);

    echo "<br/><fieldset><legend><strong>$enrolmgtmanual</strong></legend>";
    echo "<center>$cronrunmsg</center>";
    echo '</fieldset>';
}


// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

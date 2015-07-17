<?php

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/cohorts.class.php');
require_once("$CFG->dirroot/admin/tool/sync/tool.php");

class ToolForm extends moodleform {

    function definition() {
        global $CFG;

        $coursemanager = new \tool_sync\course_sync_manager();
        $usermanager = new \tool_sync\users_sync_manager();
        $userpicturemanager = new \tool_sync\userpictures_sync_manager();
        $enrolmanager = new \tool_sync\enrol_sync_manager();
        $cohortmanager = new \tool_sync\cohorts_sync_manager();
        $mainmanager = new \tool_sync\tool_plugin_sync();

        $fileoptions = array('context' => context_system::instance());

        $mform = $this->_form;

        $mform->addElement('header', 'h1', get_string('filemanager', 'tool_sync'));

        $fileareaurl = new moodle_url('/admin/tool/sync/filearea.php');
        $mform->addElement('static', 'files', '<a href="'.$fileareaurl.'">'.get_string('filemanager2', 'tool_sync').'</a>'); 

        $mform->addElement('header', 'h2', get_string('coursesync', 'tool_sync'));
        $coursemanager->form_elements($mform);

        $mform->addElement('header', 'h3', get_string('usersync', 'tool_sync'));
        $usermanager->form_elements($mform);

        $mform->addElement('header', 'h4', get_string('enrolsync', 'tool_sync'));
        $enrolmanager->form_elements($mform);

        $mform->addElement('header', 'h5', get_string('userpicturesync', 'tool_sync'));
        $userpicturemanager->form_elements($mform);

        $mform->addElement('header', 'h6', get_string('cohortsync', 'tool_sync'));
        $cohortmanager->form_elements($mform);

        // $mform->addElement('header', get_string('optionheader', 'tool_sync'));
        $mainmanager->form_elements($mform);
        
        $this->add_action_buttons();

    }
}
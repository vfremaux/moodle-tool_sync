<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * @package   tool_sync
 * @category  tool
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/cohorts.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/tool.php');

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
        $mform->addHelpButton('h2', 'coursecreateformat', 'tool_sync');
        $coursemanager->form_elements($mform);

        $mform->addElement('header', 'h3', get_string('usersync', 'tool_sync'));
        $mform->addHelpButton('h3', 'userformat', 'tool_sync');
        $usermanager->form_elements($mform);

        $mform->addElement('header', 'h4', get_string('enrolsync', 'tool_sync'));
        $mform->addHelpButton('h4', 'enrolformat', 'tool_sync');
        $enrolmanager->form_elements($mform);

        $mform->addElement('header', 'h5', get_string('userpicturesync', 'tool_sync'));
        $mform->addHelpButton('h5', 'userpicturesformat', 'tool_sync');
        $userpicturemanager->form_elements($mform);

        $mform->addElement('header', 'h6', get_string('cohortsync', 'tool_sync'));
        $mform->addHelpButton('h6', 'cohortformat', 'tool_sync');
        $cohortmanager->form_elements($mform);

        // $mform->addElement('header', get_string('optionheader', 'tool_sync'));
        $mainmanager->form_elements($mform);

        $this->add_action_buttons();

    }
}
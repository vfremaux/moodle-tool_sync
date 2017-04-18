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

/**
 * Tests webservices external functions
 *
 * @package    local_shop
 * @category   test
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot.'/admin/tool/sync/enrols/externallib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/cohorts.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrols/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');

/**
 *  tests class for local_shop.
 */
class admin_tool_sync_testcase extends advanced_testcase {

    /**
     * Given an initialised shop with a TEST product, will run the entire
     * purchase controller chain using test payment method.
     * This test assumes we have a shop,purchasereqs,users,customer,order,payment,bill sequence
     *
     */
    public function test_sync() {
        global $DB;

        $this->resetAfterTest();

        // Setup moodle content environment.

        $category = $this->getDataGenerator()->create_category();
        $params = array('name' => 'Template course', 'shortname' => 'TEMPLATE', 'category' => $category->id, 'idnumber' => 'TEMPLATEIDNUM');
        $course = $this->getDataGenerator()->create_course($params);

        $this->setAdminUser();

        $this->assertTrue(empty($enrolled));

        // Load sample files into filesystem.

        $this->load_file('webservices/cohort_create_sample.csv');
        $this->load_file('webservices/course_create_sample.csv');
        $this->load_file('webservices/course_delete_sample.csv');
        $this->load_file('webservices/course_reset_sample.csv');
        $this->load_file('webservices/course_metabindings_sample.csv');
        $this->load_file('webservices/user_create_sample.csv');
        $this->load_file('webservices/user_delete_sample.csv');
        $this->load_file('webservices/user_suspend_sample.csv');
        $this->load_file('webservices/user_update_sample.csv');
        $this->load_file('webservices/enrol_sample.csv');

        // Set component config for tests.
        set_config('csvseparator', ';', 'tool_sync');
        set_config('encoding', ';', 'UTF-8');
        set_config('enrolcleanup', 1, 'tool_sync');
        set_config('storereport', 1, 'tool_sync');
        set_config('filearchive', 1, 'tool_sync');
        set_config('filefailed', 1, 'tool_sync');
        set_config('filecleanup', 0, 'tool_sync');

        // Configure course tool.
        set_config('courses_fileuploadlocation', 'course_create_sample.csv', 'tool_sync');
        set_config('courses_filedeletelocation', 'course_delete_sample.csv', 'tool_sync');
        set_config('courses_filedeleteidentifier', 'shortname', 'tool_sync');
        set_config('courses_fileexistlocation', 'course_check_sample.csv', 'tool_sync');
        set_config('courses_existfileidentifier', 'shortname', 'tool_sync');
        set_config('courses_fileresetlocation', 'course_reset_sample.csv', 'tool_sync');
        set_config('courses_fileresetidentifier', 'shortname', 'tool_sync');
        set_config('courses_filemetabindinglocation', 'course_metabindings_sample.csv', 'tool_sync');
        set_config('courses_filemetabindingidentifier', 'shortname', 'tool_sync');

        // Configure users tool.
        set_config('users_createpasswords', 0, 'tool_sync');
        set_config('users_sendpasswordtousers', 0, 'tool_sync');
        set_config('users_allowrename', 0, 'tool_sync');
        set_config('users_protectemails', 0, 'tool_sync');
        set_config('users_primaryidentity', 'username', 'tool_sync');

        // Configure cohort tool.
        set_config('cohorts_filelocation', 'cohort_create_sample.csv', 'tool_sync');
        set_config('cohorts_useridentifier', 'username', 'tool_sync');
        set_config('cohorts_cohortidentifier', 'idnumber', 'tool_sync');

        set_config('cohorts_coursebindingfilelocation', 'cohort_course_sample.csv', 'tool_sync');
        set_config('cohorts_courseidentifier', 'shortname', 'tool_sync');
        set_config('cohorts_autocreate', 1, 'tool_sync');
        set_config('cohorts_syncdelete', 1, 'tool_sync');

        // Get updated config.
        $config = get_config('tool_sync');

        // Start tests.

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CREATE);
        $coursemanager->cron($config);
        $this->assertNotEmpty($DB->get_record('course', array('shortname' => 'TESTCOURSE1')));
        $this->assertNotEmpty($DB->get_record('course', array('shortname' => 'TESTCOURSE2')));
        $this->assertNotEmpty($DB->get_record('course', array('shortname' => 'TESTCOURSE3')));

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_METAS);
        $coursemanager->cron($config);

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_RESET);
        $coursemanager->cron($config);

        set_config('users_filelocation', 'user_create_sample.csv');
        $config->users_filelocation = 'user_create_sample.csv';
        $usersmanager = new \tool_sync\user_sync_manager();
        $usersmanager->cron($config);

        set_config('users_filelocation', 'user_update_sample.csv');
        $config->users_filelocation = 'user_update_sample.csv';
        $usersmanager->cron($config);

        set_config('users_filelocation', 'user_suspend_sample.csv');
        $config->users_filelocation = 'user_suspend_sample.csv';
        $usersmanager->cron($config);

        set_config('users_filelocation', 'user_delete_sample.csv');
        $config->users_filelocation = 'user_delete_sample.csv';
        $usersmanager->cron($config);

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_DELETE);
        $coursemanager->cron($config);
    }

    protected function load_file($filepath) {
        global $CFG;

        $fs = get_file_storage();

        $filerec = new StdClass();
        $filerec->contextid = context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = basename($filepath);

        $fs->create_file_from_pathname($filerec, $CFG->dirroot.'/admin/tool/sync/tests/'.$filepath);
    }
}
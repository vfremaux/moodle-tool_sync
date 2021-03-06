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
require_once($CFG->dirroot.'/admin/tool/sync/groups/groups.class.php');
require_once($CFG->dirroot.'/cohort/lib.php');

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
        global $DB, $CFG;

        $this->resetAfterTest();

        set_config('trace', $CFG->dirroot.'/trace.log');

        // Setup moodle content environment.

        $category = $this->getDataGenerator()->create_category();
        $params = array('name' => 'Template course', 'shortname' => 'TEMPLATE', 'category' => $category->id, 'idnumber' => 'TEMPLATEIDNUM');
        $course = $this->getDataGenerator()->create_course($params);

        $this->setAdminUser();

        $this->assertTrue(empty($enrolled));

        // Load sample files into filesystem.

        $this->load_file('webservices/cohort_create_only.csv');
        $this->load_file('webservices/cohort_delete_only_by_name.csv');
        $this->load_file('webservices/cohort_delete_only_by_idnumber.csv');
        $this->load_file('webservices/cohort_create_sample.csv');
        $this->load_file('webservices/cohort_delete_sample.csv');
        $this->load_file('webservices/cohort_add_members_by_idnumber.csv');
        $this->load_file('webservices/cohort_free_cohorts_by_idnumber.csv');
        $this->load_file('webservices/cohort_delete_members_by_idnumber.csv');
        $this->load_file('webservices/cohort_bind_courses_sample.csv');
        $this->load_file('webservices/course_create_sample.csv');
        $this->load_file('webservices/course_delete_sample.csv');
        $this->load_file('webservices/course_reset_sample.csv');
        $this->load_file('webservices/course_metabindings_sample.csv');
        $this->load_file('webservices/user_create_sample.csv');
        $this->load_file('webservices/user_delete_sample.csv');
        $this->load_file('webservices/user_suspend_sample.csv');
        $this->load_file('webservices/user_update_sample.csv');
        $this->load_file('webservices/enrol_sample.csv');
        $this->load_file('webservices/groups.csv');
        $this->load_file('webservices/groups_update.csv');
        $this->load_file('webservices/groupings_update.csv');
        $this->load_file('webservices/group_members.csv');
        $this->load_file('webservices/shift_group_members.csv');

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
        set_config('courses_fileuploadidentifier', 'shortname', 'tool_sync');

        set_config('courses_coursecategoryidentifier', 'idname', 'tool_sync');
        set_config('courses_newcategoriesvisible', 1, 'tool_sync');
        set_config('courses_protectcategory', 0, 'tool_sync');

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
        set_config('cohorts_useridentifier', 'idnumber', 'tool_sync');
        set_config('cohorts_cohortidentifier', 'idnumber', 'tool_sync');
        set_config('cohorts_roleidentifier', 'shortname', 'tool_sync');

        set_config('cohorts_coursebindingfilelocation', 'cohort_bind_courses_sample.csv', 'tool_sync');
        set_config('cohorts_courseidentifier', 'shortname', 'tool_sync');
        set_config('cohorts_autocreate', 1, 'tool_sync');
        set_config('cohorts_syncdelete', 1, 'tool_sync');

        // Configure enrol tool.
        set_config('enrols_filelocation', 'enrol_sample.csv', 'tool_sync');
        set_config('enrols_courseidentifier', 'shortname', 'tool_sync');
        set_config('enrols_useridentifier', 'username', 'tool_sync');
        set_config('enrols_protectgroups', 1, 'tool_sync');

        // Configure group tool.
        set_config('groups_filelocation', 'groups.csv', 'tool_sync');
        set_config('groupmembers_filelocation', 'group_members.csv', 'tool_sync');
        set_config('groups_courseidentifier', 'shortname', 'tool_sync');
        set_config('groups_groupidentifier', 'idnumber', 'tool_sync');
        set_config('groups_groupingidentifier', 'idnumber', 'tool_sync');
        set_config('groups_useridentifier', 'username', 'tool_sync');
        set_config('groups_autogrouping', 0, 'tool_sync');
        set_config('groups_mailadmins', 0, 'tool_sync');

        // Get updated config.
        $config = get_config('tool_sync');

        // Start tests.

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_CREATE);
        $coursemanager->cron($config);

        $this->assertNotEmpty($course1 = $DB->get_record('course', array('shortname' => 'TESTCOURSE1')));
        $this->assertNotEmpty($course2 = $DB->get_record('course', array('shortname' => 'TESTCOURSE2')));
        $this->assertNotEmpty($course3 = $DB->get_record('course', array('shortname' => 'TESTCOURSE3')));

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_METAS);
        $coursemanager->cron($config);

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_RESET);
        $coursemanager->cron($config);

        set_config('users_filelocation', 'user_create_sample.csv');
        $config->users_filelocation = 'user_create_sample.csv';
        $usersmanager = new \tool_sync\users_sync_manager();
        $usersmanager->cron($config);
        $this->assertNotEmpty($user1 = $DB->get_record('user', array('username' => 'john.doe')));
        $this->assertNotEmpty($user2 = $DB->get_record('user', array('username' => 'amelie.dupont')));
        $this->assertNotEmpty($user3 = $DB->get_record('user', array('username' => 'jack.duf')));
        $this->assertNotEmpty($user4 = $DB->get_record('user', array('username' => 'ggrass')));
        $this->assertNotEmpty($user5 = $DB->get_record('user', array('username' => 'yyang')));
        $this->assertNotEmpty($groupuser1 = $DB->get_record('user', array('username' => 'ugr1')));
        $this->assertNotEmpty($groupuser2 = $DB->get_record('user', array('username' => 'ugr2')));
        $this->assertNotEmpty($groupuser3 = $DB->get_record('user', array('username' => 'ugr3')));
        $this->assertNotEmpty($groupuser4 = $DB->get_record('user', array('username' => 'ugr4')));
        $this->assertNotEmpty($groupuser10 = $DB->get_record('user', array('username' => 'ugr10')));

        $this->assertNotEmpty($deleted1 = $DB->get_record('user', array('username' => 'todelete1')));
        $this->assertNotEmpty($deleted2 = $DB->get_record('user', array('username' => 'todelete2')));
        $this->assertNotEmpty($deleted3 = $DB->get_record('user', array('username' => 'todelete3')));

        $this->assertNotEmpty($suspended1 = $DB->get_record('user', array('username' => 'tosuspend1')));
        $this->assertNotEmpty($suspended2 = $DB->get_record('user', array('username' => 'tosuspend2')));
        $this->assertNotEmpty($suspended3 = $DB->get_record('user', array('username' => 'tosuspend3')));

        set_config('users_filelocation', 'user_update_sample.csv');
        $config->users_filelocation = 'user_update_sample.csv';
        $usersmanager->cron($config);
        $this->assertNotEmpty($uuser1 = $DB->get_record('user', array('idnumber' => 'U0001')));
        $this->assertNotEmpty($uuser2 = $DB->get_record('user', array('idnumber' => 'U0002')));
        $this->assertNotEmpty($uuser3 = $DB->get_record('user', array('idnumber' => 'U0003')));
        $this->assertNotEmpty($uuser4 = $DB->get_record('user', array('idnumber' => 'U0004')));
        $this->assertNotEmpty($uuser5 = $DB->get_record('user', array('idnumber' => 'U0005')));

        $this->assertEquals($user1->username, $uuser1->username);
        $this->assertEquals($user2->username, $uuser2->username);
        $this->assertEquals($user3->username, $uuser3->username);
        $this->assertEquals($user4->username, $uuser4->username);
        $this->assertEquals($user5->username, $uuser5->username);

        set_config('users_filelocation', 'user_suspend_sample.csv');
        $config->users_filelocation = 'user_suspend_sample.csv';
        $usersmanager->cron($config);

        $ususpended1 = $DB->get_record('user', array('username' => 'tosuspend1'));
        $ususpended2 = $DB->get_record('user', array('username' => 'tosuspend2'));
        $ususpended3 = $DB->get_record('user', array('username' => 'tosuspend3'));

        $this->assertTrue($ususpended1->suspended == 1);
        $this->assertTrue($ususpended2->suspended == 1);
        $this->assertTrue($ususpended3->suspended == 1);

        // Cohort operations.

        $cohortmanager = new \tool_sync\cohorts_sync_manager(SYNC_COHORT_CREATE_UPDATE);

        set_config('cohorts_filelocation', 'cohort_create_only.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        $this->assertTrue(is_object($DB->get_record('cohort', array('name' => 'COHORT1'))));
        $this->assertTrue(is_object($DB->get_record('cohort', array('name' => 'COHORT2'))));
        $this->assertTrue(is_object($DB->get_record('cohort', array('idnumber' => 'COH003'))));
        $this->assertTrue(is_object($DB->get_record('cohort', array('idnumber' => 'COH004'))));

        set_config('cohorts_filelocation', 'cohort_delete_only_by_name.csv', 'tool_sync');
        set_config('cohorts_cohortidentifier', 'name', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        $this->assertTrue($DB->count_records('cohort') == 0);

        set_config('cohorts_filelocation', 'cohort_create_only.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        set_config('cohorts_filelocation', 'cohort_delete_only_by_idnumber.csv', 'tool_sync');
        set_config('cohorts_cohortidentifier', 'idnumber', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        $this->assertTrue($DB->count_records('cohort') == 0);

        echo "\nTest unit : building cohorts\n";

        // Combined creation / feeding.
        set_config('cohorts_filelocation', 'cohort_create_sample.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        echo $cohortmanager->log;

        echo "\nTest unit : building cohorts Testing\n";

        $cohort1 = $DB->get_record('cohort', array('name' => 'COHORT1'));
        $cohort2 = $DB->get_record('cohort', array('name' => 'COHORT2'));
        $cohort3 = $DB->get_record('cohort', array('idnumber' => 'COH003'));
        $cohort4 = $DB->get_record('cohort', array('idnumber' => 'COH004'));

        $this->assertNotEmpty($cohort1);
        $this->assertNotEmpty($cohort2);
        $this->assertNotEmpty($cohort3);
        $this->assertNotEmpty($cohort4);

        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user1->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user2->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user3->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user4->id))));

        echo "\nTest unit : feeding cohorts\n";

        set_config('cohorts_filelocation', 'cohort_free_cohorts_by_idnumber.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        // Assert cohorts are empty.
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort1->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort1->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort2->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort2->id)), 0);

        set_config('cohorts_filelocation', 'cohort_add_members_by_idnumber.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user1->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user2->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user3->id))));
        $this->assertTrue(is_object($DB->get_record('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user4->id))));

        set_config('cohorts_filelocation', 'cohort_delete_members_by_idnumber.csv', 'tool_sync');
        $config = get_config('tool_sync');
        $cohortmanager->cron($config);

        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user1->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort1->id, 'userid' => $user2->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user3->id)), 0);
        $this->assertEquals($DB->count_records('cohort_members', array('cohortid' => $cohort2->id, 'userid' => $user4->id)), 0);

        // Binding courses.
        echo "\nTest unit : binding courses to cohorts\n";

        $cohortmanager = new \tool_sync\cohorts_sync_manager(SYNC_COHORT_BIND_COURSES);
        $cohortmanager->cron($config);

        // Enrolling users.
        echo "\nTest unit : enrolling users\n";

        // Enrolling.
        $enrolmanager = new \tool_sync\enrol_sync_manager();
        $enrolmanager->cron($config);

        // Group operations.
        echo "\nTest unit : Creating / syncing groups\n";

        $groupmanager = new \tool_sync\group_sync_manager(SYNC_COURSE_GROUPS);
        $groupmanager->cron($config);

        $this->assertTrue(is_object($groupingA = $DB->get_record('groupings', array('courseid' => $course1->id, 'name' => 'Grouping A'))));

        $this->assertTrue(is_object($group1 = $DB->get_record('groups', array('courseid' => $course1->id, 'idnumber' => 'TC1_GR1'))));
        $this->assertTrue(is_object($group2 = $DB->get_record('groups', array('courseid' => $course1->id, 'idnumber' => 'TC1_GR2'))));
        $this->assertTrue(is_object($group3 = $DB->get_record('groups', array('courseid' => $course1->id, 'idnumber' => 'TC1_GR3'))));
        $this->assertTrue(is_object($group4 = $DB->get_record('groups', array('courseid' => $course1->id, 'idnumber' => 'TC1_GR4'))));
        $this->assertTrue(is_object($DB->get_record('groups', array('courseid' => $course2->id, 'idnumber' => 'TC2_GR1'))));
        $this->assertTrue(is_object($DB->get_record('groups', array('courseid' => $course2->id, 'idnumber' => 'TC2_GR2'))));
        $this->assertTrue(is_object($DB->get_record('groups', array('courseid' => $course2->id, 'idnumber' => 'TC2_GR3'))));
        $this->assertTrue(is_object($DB->get_record('groups', array('courseid' => $course2->id, 'idnumber' => 'TC2_GR4'))));

        $this->assertTrue(is_object($DB->get_record('groupings_groups', array('groupid' => $group1->id, 'groupingid' => $groupingA->id))));

        echo "\nTest unit : Syncing group members\n";

        $groupmanager = new \tool_sync\group_sync_manager(SYNC_GROUP_MEMBERS);
        $groupmanager->cron($config);

        $this->assertTrue(is_object($DB->get_record('groups_members', array('groupid' => $group1->id, 'userid' => $groupuser1->id))));
        $this->assertTrue(is_object($DB->get_record('groups_members', array('groupid' => $group4->id, 'userid' => $groupuser4->id))));

        echo "\nTest unit : Shifting group members (changing group)\n";

        set_config('groupmembers_filelocation', 'shift_group_members.csv', 'tool_sync');
        $config = get_config('tool_sync'); // Reload config with changes.

        $groupmanager = new \tool_sync\group_sync_manager(SYNC_GROUP_MEMBERS);
        $config->groupmembers_filelocation = 'shift_group_members.csv'; // Change in memory config.
        $groupmanager->cron($config);

        $this->assertTrue(is_object($DB->get_record('groups_members', array('groupid' => $group2->id, 'userid' => $groupuser1->id))));
        $this->assertTrue(is_object($DB->get_record('groups_members', array('groupid' => $group1->id, 'userid' => $groupuser4->id))));

        // Updating groups (using default type)
        echo "\nTest unit : Updating group definition\n";

        set_config('groups_filelocation', 'groups_update.csv', 'tool_sync');
        $config = get_config('tool_sync'); // Reload config with changes.

        $groupmanager = new \tool_sync\group_sync_manager(SYNC_COURSE_GROUPS);
        $groupmanager->cron($config);

        $this->assertTrue(is_object($groupingA = $DB->get_record('groups', array('courseid' => $course1->id, 'name' => 'Group 1 Updated'))));

        // Updating groupings (explicit type)
        echo "\nTest unit : Updating grouping definition\n";
        set_config('groups_filelocation', 'groupings_update.csv', 'tool_sync');
        $config = get_config('tool_sync'); // Reload config with changes.

        $groupmanager = new \tool_sync\group_sync_manager(SYNC_COURSE_GROUPS);
        $groupmanager->cron($config);

        $this->assertTrue(is_object($groupingA = $DB->get_record('groupings', array('courseid' => $course1->id, 'name' => 'Grouping A Updated'))));

        // Users deletion.
        echo "\nTest unit : User deletion\n";

        set_config('users_filelocation', 'user_delete_sample.csv');
        $config->users_filelocation = 'user_delete_sample.csv';
        $usersmanager->cron($config);

        $deleted = $DB->get_records('user', array('deleted' => 1));
        echo "\nDeleted users\n";
        foreach ($deleted as $d) {
            echo "$d->username $d->deleted $d->email\n";
        }

        // We cannot rely on username as usernames are tagged on deletion.
        $udeleted1 = $DB->get_record_select('user', "username LIKE 'todelete1@foo.com%' ");
        $udeleted2 = $DB->get_record_select('user', "username LIKE 'todelete2@foo.com%' ");
        $udeleted3 = $DB->get_record_select('user', "username LIKE 'todelete3@foo.com%' ");

        $this->assertTrue($udeleted1->deleted == 1);
        $this->assertTrue($udeleted2->deleted == 1);
        $this->assertTrue($udeleted3->deleted == 1);

        $coursemanager = new \tool_sync\course_sync_manager(SYNC_COURSE_DELETE);
        $coursemanager->cron($config);

        $deletedcourse1 = $DB->get_record('course', array('shortname' => 'TESTCOURSE1'));
        $this->assertTrue(!is_object($deletedcourse1));
        $this->assertTrue(!is_object($DB->get_record('course', array('shortname' => 'TESTCOURSE2'))));
        $this->assertTrue(!is_object($DB->get_record('course', array('shortname' => 'TESTCOURSE3'))));
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
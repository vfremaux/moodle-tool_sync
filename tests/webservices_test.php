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

/**
 *  tests class for local_shop.
 */
class admin_tool_webservices_testcase extends advanced_testcase {

    /**
     * Given an initialised shop with a TEST product, will run the entire
     * purchase controller chain using test payment method.
     * This test assumes we have a shop,purchasereqs,users,customer,order,payment,bill sequence
     *
     */
    public function test_enrols() {
        global $DB;

        $this->resetAfterTest();

        // Setup moodle content environment.

        $category = $this->getDataGenerator()->create_category();
        $params = array('name' => 'Test course', 'shortname' => 'TESTENROLS', 'category' => $category->id, 'idnumber' => 'ENROLTESTIDNUM');
        $course = $this->getDataGenerator()->create_course($params);

        // Create a bunch of users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();
        $user1->idnumber = 'ALUMN1';
        $DB->set_field('user', 'idnumber', 'ALUMN1', array('id' => $user1->id));
        $user2->idnumber = 'ALUMN2';
        $DB->set_field('user', 'idnumber', 'ALUMN2', array('id' => $user2->id));
        $user3->idnumber = 'ALUMN3';
        $DB->set_field('user', 'idnumber', 'ALUMN3', array('id' => $user3->id));
        $user4->idnumber = 'ALUMN4';
        $DB->set_field('user', 'idnumber', 'ALUMN4', array('id' => $user4->id));

        $this->setAdminUser();

        // Get course enrollments at start trying all idsources.
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('shortname', $course->shortname, array());
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('idnumber', $course->idnumber, array());

        $this->assertTrue(empty($enrolled));

        // Enrol user 1 and unenrol
        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'id', $user1->id, 'id', $course->id);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $euser = (object) array_shift($enrolled);
        $this->assertEquals($euser->id, $user1->id);
        \tool_sync_core_ext_external::unenrol_user('id', $user1->id, 'id', $course->id);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $this->assertEquals(0, count($enrolled));

        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'username', $user1->username, 'shortname', $course->shortname);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $euser = (object) array_shift($enrolled);
        $this->assertEquals($euser->id, $user1->id);
        \tool_sync_core_ext_external::unenrol_user('id', $user1->id, 'shortname', $course->shortname);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $this->assertEquals(0, count($enrolled));

        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'idnumber', $user1->idnumber, 'idnumber', $course->idnumber);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $euser = (object) array_shift($enrolled);
        $this->assertEquals($euser->id, $user1->id);
        \tool_sync_core_ext_external::unenrol_user('idnumber', $user1->idnumber, 'idnumber', $course->idnumber);
        $enrolled = \tool_sync_core_ext_external::get_enrolled_users('id', $course->id, array());
        $this->assertEquals(0, count($enrolled));

        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'username', $user1->username, 'shortname', $course->shortname);
        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'username', $user2->username, 'shortname', $course->shortname);
        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'username', $user3->username, 'shortname', $course->shortname);
        \tool_sync_core_ext_external::enrol_user('shortname', 'student', 'username', $user4->username, 'shortname', $course->shortname);

        $fullenrolled = \tool_sync_core_ext_external::get_enrolled_full_users('id', $course->id, array());
        $this->assertEquals(4, count($fullenrolled));
    }
}
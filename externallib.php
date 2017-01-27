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
 * External Tool Sync control
 *
 * Tool Sync API allows external applications to program and change settings
 * in the tool sync engine to control its behaviour and resources that will be used
 * for synchronisation.
 *
 * @package    tool_sync
 * @category   external
 * @copyright  2016 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

/**
 * Tool Sync control functions
 *
 * @package    tool_sync
 * @category   external
 * @copyright  2016 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class tool_sync_external extends external_api {

    protected static function validate_config_parameters($configparamdefs, $inputs) {
        $status = self::validate_parameters($configparamdefs, $inputs);

        $validkeys = array(
            'course' => array('fileuploadlocation',
                'filedeletelocation',
                'filedeleteidentifier',
                'fileexistslocation',
                'existfileidentifier',
                'fileresetlocation',
                'fileresetidentifier'),
            'users' => array('filelocation',
                'primaryidentity'),
            'cohorts' => array('filelocation',
                'useridentifier',
                'cohortidentifier',
                'autocreate',
                'syncdelete'),
            'enrol' => array('filelocation',
                'courseidentifier',
                'useridentifier')
        );

        if (!in_array($inputs['configkey'], $validkeys[$inputs['service']])) {
            throw new invalid_parameter_exception('Service keys not in acceptable ranges.');
        }

        return $status;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function set_config_parameters() {
        return new external_function_parameters(
            array('service' => new external_value(PARAM_TEXT, 'The synchronisation service name'),
                  'confkey' => new external_value(PARAM_TEXT, 'a configuration key for the service'),
                  'confvalue' => new external_value(PARAM_TEXT, 'a configuration key for the service')
            )
        );
    }

    /**
     * Allows remotely programming the admin tool sync tool.
     *
     * @param string $service Service name, such as enrols, courses, users, cohorts...
     * @param string $confkey configuration key
     * @param string $confvalue Configuration value
     * @return array
     * @since Moodle 2.2
     */
    public static function set_config($service, $confkey, $confvalue) {

        // Validate parameters.
        $params = self::validate_config_parameters(self::set_config_parameters(),
                array('service' => $service, 'confkey' => $confkey, 'confvalue' => $confvalue));

        set_config($service.'_'.$confkey, $confvalue, 'tool_sync');

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function set_config_returns() {
        return new external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function process_parameters() {
        return new external_function_parameters(
            array(
                'service' => new external_value(PARAM_TEXT, 'Synchronisation service name'),
                'action' => new external_value(PARAM_TEXT, 'Synchronisation action')
            )
        );
    }

    protected static function validate_process_parameters($configparamdefs, $inputs) {
        $status = self::validate_parameters($configparamdefs, $inputs);

        $validactions = array(
            'course' => array('check' => SYNC_COURSE_CHECK,
                'delete' => SYNC_COURSE_DELETE,
                'create' => SYNC_COURSE_CREATE),
            'users' => null,
            'cohorts' => null,
            'enrol' => null
        );

        if (!empty($validactions[$inputs['service']])) {
            if (!in_array($inputs['action'], $validkeys[$inputs['service']])) {
                throw new invalid_parameter_exception('Service action not in acceptable ranges.');
            }
        }

        return $status;
    }

    /**
     * Get query result data as raw data in a single value.
     *
     * @param int $courseid course id
     * @param array $options These options are not used yet, might be used in later version
     * @return array
     * @since Moodle 2.2
     */
    public static function process($service, $action = '') {

        // Validate parameters.
        $params = self::validate_process_parameters(self::process_parameters(),
                        array('service' => $service, 'action' => $action));

        $syncconfig = get_config('tool_sync');

        switch ($service) {
            case 'course':
                $manager = new course_sync_manager($action);
                $manager->cron();
                break;
            case 'users':
                $manager = new users_sync_manager();
                $manager->cron();
                break;
            case 'cohorts':
                $manager = new cohorts_sync_manager();
                $manager->cron();
                break;
            case 'enrol' :
                $manager = new enrol_sync_manager();
                $manager->cron();
                break;
        }

        return $manager->log;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function process_returns() {
        return new external_value(PARAM_TEXT, 'CSV report');
    }

    // Create course from a template ------------------------------------------------.

    public static function deploy_course_parameters() {
        return new external_function_parameters(
            array(
                'categoryidsource' => new external_value(PARAM_TEXT, 'ID source for category, can be id or idnumber'),
                'categoryid' => new external_value(PARAM_TEXT, 'Category identifier'),
                'templateidsource' => new external_value(PARAM_TEXT, 'ID source to identifiy template course, can be id, idnumber, or shortname'),
                'templateid' => new external_value(PARAM_TEXT, 'Template ID'),
                'shortname' => new external_value(PARAM_TEXT, 'New course shortname'),
                'fullname' => new external_value(PARAM_TEXT, 'New course fullname'),
                'idnumber' => new external_value(PARAM_TEXT, 'New course idnumber')
            )
        );
    }

    /**
     * Get query result data as raw data in a single value.
     *
     * @param int $courseid course id
     * @param array $options These options are not used yet, might be used in later version
     * @return array
     * @since Moodle 2.2
     */
    public static function deploy_course($categorysourceid, $categoryid, $templatesourceid, $templateid, $shortname, $fullname, $idnumber) {

        // Validate parameters.
        $params = self::validate_process_parameters(self::process_parameters(),
                        array(
                            'categorysourceid' => $categorysourceid,
                            'categoryid' => $categoryid,
                            'templatesourceid' => $templatesourceid,
                            'templateid' => $templateid,
                            'shortname' => $shortname,
                            'fullname' => $fullname,
                            'idnumber' => $idnumber));

        $syncconfig = get_config('tool_sync');

        include_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');

        $manager = new \tool_sync\course_sync_manager();

        switch ($templatesourceid) {
            case 'shortname':
                $course['template'] = $templateid;
                if (!$DB->record_exists('course', array('shortname' => $templateid))) {
                    throw new moodle_exception('templatenotfound');
                }
                break;
            case 'idnumber':
                $shortname = $DB->get_field('course', 'shortname', array('idnumber' => $templateid));
                if (!$shortname) {
                    throw new moodle_exception('templatenotfound');
                }
                $course['template'] = $shortname;
                break;
            case 'id':
                $shortname = $DB->get_field('course', 'shortname', array('idnumber' => $templateid));
                if (!$shortname) {
                    throw new moodle_exception('templatenotfound');
                }
                $course['template'] = $shortname;
                break;
        }

        switch ($categorysourceid) {
            case 'idnumber':
                $catid = $DB->get_field('course_categories', 'id', array('idnumber' => $categoryid));
                if (!$catid) {
                    throw new moodle_exception('categorynotfound');
                }
                $course['category'] = $catid;
                break;
            case 'id':
                if (!$catid = $DB->record_exists('course_categories', array('id' => $categoryid))) {
                    throw new moodle_exception('categorynotfound');
                }
                $course['category'] = $categoryid;
                break;
        }

        $course['shortname'] = $shortname;
        $course['fullname'] = $fullname;
        $course['idnumber'] = $idnumber;
        $newcourseid = $manager->create_course_from_template($course, null);

        if ($newcourseid < 0) {
            throw new moodle_exception("course creation failure : $newcourseid ");
        }

        return $newcourseid;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function deploy_course_returns() {
        return new external_value(PARAM_INT, 'Course id');
    }
}
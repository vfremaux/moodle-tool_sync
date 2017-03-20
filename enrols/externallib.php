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

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/enrol/externallibb.php');

/**
 * Tool Sync control functions
 *
 * @package    tool_sync
 * @category   external
 * @copyright  2016 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class tool_sync_core_ext_external extends external_api {

    protected static function validate_enrol_parameters($configparamdefs, $inputs, $isunenrol = false) {
        global $DB, $CFG;

        $status = self::validate_parameters($configparamdefs, $inputs);

        $validkeys = array(
            'role' => array('id', 'shortname'),
            'course' => array('id', 'shortname', 'idnumber'),
            'user' => array('id', 'username', 'idnumber', 'email'),
        );

        if (!$isunenrol) {
            $status['roleid'] = self::validate_role_param($inputs, $validkeys['role']);
        }

        $status['courseid'] = self::validate_course_param($inputs, $validkeys['course']);
        $status['userid'] = self::validate_user_param($inputs, $validkeys['user']);

        return $status;

    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function enrol_user_parameters() {
        return new external_function_parameters(
            array('roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
                'roleid' => new external_value(PARAM_TEXT, 'The role id'),
                'useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
                'userid' => new external_value(PARAM_TEXT, 'The user id'),
                'courseidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
                'courseid' => new external_value(PARAM_TEXT, 'The course identifier'),
                'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'manual'),
                'timestart' => new external_value(PARAM_INT, 'Time start of the enrol period', VALUE_DEFAULT, 0),
                'timeend' => new external_value(PARAM_INT, 'Time end of the enrol period', VALUE_DEFAULT, 0),
                'suspend' => new external_value(PARAM_INT, 'Suspension', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Enrol a single user
     *
     * @return the user enrolment id
     */
    public static function enrol_user($roleidsource, $roleid, $useridsource, $userid, $courseidsource, $courseid,
                                      $method = 'manual', $timestart = 0, $timeend = 0, $suspend = 0) {
        global $CFG;

        // Validate parameters.
        $parameters = array('roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'useridsource' => $useridsource,
            'userid' => $userid,
            'courseidsource' => $courseidsource,
            'courseid' => $courseid,
            'method' => $method,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'suspend' => $suspend);
        $params = self::validate_enrol_parameters(self::enrol_user_parameters(), $parameters);

        $class = 'enrol_'.$params['method'].'_external';

        $enrollibfile = $CFG->dirroot.'/enrol/'.$params['method'].'/externallib.php';
        if (!file_exists($enrollibfile)) {
            throw new moodle_exception('This enrol method does not support web services');
        }
        include_once($enrollibfile);

        if (!class_exists($class)) {
            throw new moodle_exception('This enrol method does not support enrol_users()');
        }

        // Get all ids depending on sources.

        $enrolment = array(
            'roleid' => $params['roleid'],
            'courseid' => $params['courseid'],
            'userid' => $params['userid'],
            'timestart' => $params['timestart'],
            'timeend' => $params['timeend'],
            'suspend' => $params['suspend']
        );

        $class::enrol_users(array($enrolment));

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function enrol_user_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }

    // Commit an uploaded file.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unenrol_user_parameters() {
        return new external_function_parameters(
            array('useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
                'userid' => new external_value(PARAM_TEXT, 'The user id'),
                'courseidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
                'courseid' => new external_value(PARAM_TEXT, 'The course identifier'),
                'roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification', VALUE_DEFAULT, ''),
                'roleid' => new external_value(PARAM_TEXT, 'The role id', VALUE_DEFAULT, ''),
                'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'manual'),
            )
        );
    }

    /**
     * Unenrol a single user
     *
     * @return external_description
     */
    public static function unenrol_user($useridsource, $userid, $courseidsource, $courseid, $roleidsource = '', $roleid = '',
                                        $method = 'manual') {

        $parameters = array('useridsource' => $useridsource,
            'userid' => $userid,
            'courseidsource' => $courseidsource,
            'courseid' => $courseid,
            'roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'method' => $method);
        $params = self::validate_enrol_parameters(self::unenrol_user_parameters(), $parameters, true);

        $class = 'enrol_'.$param['method'].'_external';

        if (!class_exists($class)) {
            throw new moodle_exception('This enrol method does not support unenrol_users()');
        }

        // Get all ids depending on sources.

        $enrolment = array(
            'courseid' => $params['courseid'],
            'userid' => $params['userid']
        );
        if (!empty($params['roleidsource'])) {
            $enrolment['roleid'] = $params['roleid'];
        }

        $class::unenrol_users(array($enrolment));

        return true;

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unenrol_user_returns() {
        return new external_value(PARAM_BOOL, 'Success status');
    }

    // Role assigns related.

    public function validate_role_paramters($configparamdefs, $inputs) {
        global $DB, $CFG;

        $status = self::validate_parameters($configparamdefs, $inputs);

        $contexttypestxt = array(
            'system' => CONTEXT_SYSTEM,
            'coursecat' => CONTEXT_COURSECAT,
            'course' => CONTEXT_COURSE,
            'module' => CONTEXT_MODULE,
            'block' => CONTEXT_BLOCK,
            'user' => CONTEXT_USER);

        if (!is_numeric($params['contexttype'])) {
            $originaltype = $params['contextytype'];
            $params['contexttype'] = $contexttypestxt[strtolower($params['contextype'])];
        }

        $validkeys = array(
            'role' => array('id', 'shortname'),
            'contexttype' => array(CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE, CONTEXT_BLOCK, CONTEXT_USER),
            'user' => array('id', 'username', 'idnumber', 'email'),
            'instance' => array(
                CONTEXT_COURSECAT => array('id', 'idnumber'),
                CONTEXT_COURSE => array('id', 'idnumber', 'shortname'),
                CONTEXT_MODULE => array('id', 'idnumber', 'instanceref'), // Instance ref adds the modname§instanceid syntax.
                CONTEXT_BLOCK => array('id', 'idnumber'), // Handles format page special idnumber addition.
                CONTEXT_USER => array('id', 'idnumber', 'username', 'email')
            ),
        );

        if (!in_array($inputs['instanceidsource'], $validkeys['instance'][$params['contexttype']])) {
            throw new invalid_parameter_exception('Context source not in acceptable ranges for contexttype '.$originaltype);
        }

        $status['roleid'] = self::validate_role_param($inputs, $validkeys);
        $status['userid'] = self::validate_user_param($inputs, $validkeys);

        if ($params['contexttype'] == CONTEXT_SYSTEM) {
            $status['contextid'] = context_system::instance()->id;
        } else {
            switch ($inputs['instanceidsource']) {
                case 'id': {
                    switch ($params['contexttype']) {
                        case CONTEXT_COURSECAT: {
                            if (!$instance = $DB->get_record('course_categories', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_coursecat::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_COURSE: {
                            if (!$instance = $DB->get_record('course', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_course::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_MODULE: {
                            if (!$instance = $DB->get_record('course_modules', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_module::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_BLOCK: {
                            if (!$instance = $DB->get_record('format_page_items', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_block::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_USER: {
                            if (!$instance = $DB->get_record('user', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_user::instance($instance->id)->id;
                            break;
                        }
                    }
                }

                // Only in course context.
                case 'shortname': {
                    if (!$course = $DB->get_record('course', array('shortname' => $inputs['instanceid']))) {
                        throw new invalid_parameter_exception('Course not found by shortname: '.$inputs['instanceid']);
                    }
                    $status['contextid'] = context_course::instance($instance->id)->id;
                    break;
                }

                // Only in course module context.
                case 'instanceref': {
                    if (!preg_match('/^([a-z_]+)\:(\d+)$/', $inputs['instanceref'])) {
                        // We check the instanceref has modname§id syntax.
                        throw new invalid_parameter_exception('Malformed instance ref: '.$inputs['instanceid']);
                    }
                    list($modname, $instanceid) = explode('§', $params['instanceid']);
                    if (!$cm = get_coursemodule_from_instance($modname, $instanceid)) {
                        throw new invalid_parameter_exception('Course not found by shortname: '.$inputs['instanceid']);
                    }
                    $status['contextid'] = context_course::instance($instance->id)->id;
                    break;
                }

                case 'idnumber': {
                    switch ($params['contexttype']) {
                        case CONTEXT_COURSECAT: {
                            if (!$instance = $DB->get_record('course_categories', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_coursecat::instance($instance->id)->id;
                            break;
                        }
    
                        case CONTEXT_COURSE: {
                            if (!$instance = $DB->get_record('course', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_course::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_MODULE: {
                            if (!$instance = $DB->get_record('course_modules', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_module::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_BLOCK: {
                            if (!$instance = $DB->get_record('format_page_items', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_block::instance($instance->id)->id;
                            break;
                        }

                        case CONTEXT_USER: {
                            if (!$instance = $DB->get_record('user', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_user::instance($instance->id)->id;
                            break;
                        }
                    }
                }

                // User context specific.
                case 'username': {
                    if (preg_match('/^(.*)§(.*)$/', $inputs['userid'])) {
                        list($username, $hostroot) = explode('§', $inputs['userid']);
                        $hostid = $DB->get_field('mnet_host', 'id', array('wwwroot' => $hostroot));
                    } else {
                        $hostid = $CFG->mnet_localhost_id;
                    }
                    if (!$user = $DB->get_record('user', array('username' => $inputs['userid'], 'mnethostid' => $hostid))) {
                        throw new invalid_parameter_exception('User not found by username: '.$inputs['userid']);
                    }
                    $status['contextid'] = context_user::instance($user->id)->id;
                    break;
                }

                // User context specific.
                case 'email': {
                    if (!$user = $DB->get_record('user', array('email' => $inputs['userid']))) {
                        throw new invalid_parameter_exception('User not found by email: '.$inputs['userid']);
                    }
                    $status['contextid'] = context_user::instance($user->id)->id;
                    break;
                }
            }
        }

        return $status;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function assign_role_parameters() {
        return new external_function_parameters(
            array('roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
                'roleid' => new external_value(PARAM_TEXT, 'The role id'),
                'useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
                'userid' => new external_value(PARAM_TEXT, 'The user id'),
                'contexttype' => new external_value(PARAM_TEXT, 'The context type'),
                'instanceidsource' => new external_value(PARAM_TEXT, 'The source for the context attached instance'),
                'instanceid' => new external_value(PARAM_TEXT, 'The instance identifier'),

            )
        );
    }

    /**
     * assign a role to a user
     *
     * @return the user enrolment id
     */
    public static function assign_role($roleidsource, $roleid, $useridsource, $userid, $contexttype, $instanceidsource, $instanceid) {

        // Validate parameters.
        $parameters = array('roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'useridsource' => $useridsource,
            'userid' => $userid,
            'contexttype' => $contexttype,
            'instanceidsource' => $instanceidsource,
            'instanceid' => $instanceid);
        $params = self::validate_role_parameters(self::role_assign_parameters(), $parameters);

        return role_assign($params['roleid'], $params['userid'], $params['contextid']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function assign_role_returns() {
        return new external_value(PARAM_INT, 'Role assignment id');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unassign_role_parameters() {
        return new external_function_parameters(
            array('roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
                'roleid' => new external_value(PARAM_TEXT, 'The role id'),
                'useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
                'userid' => new external_value(PARAM_TEXT, 'The user id'),
                'contexttype' => new external_value(PARAM_TEXT, 'The context type'),
                'instanceidsource' => new external_value(PARAM_TEXT, 'The source for the context attached instance'),
                'instanceid' => new external_value(PARAM_TEXT, 'The instance identifier'),

            )
        );
    }

    /**
     * assign a role to a user
     *
     * @return the user enrolment id
     */
    public static function unassign_role($roleidsource, $roleid, $useridsource, $userid, $contexttype, $instanceidsource, $instanceid) {

        // Validate parameters.
        $parameters = array('roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'useridsource' => $useridsource,
            'userid' => $userid,
            'contexttype' => $contexttype,
            'instanceidsource' => $instanceidsource,
            'instanceid' => $instanceid);
        $params = self::validate_role_parameters(self::role_unassign_parameters(), $parameters);

        if ($params['roleid'] == '*') {
            $rparams = array('userid' => $params['userid'], 'contextid' => $params['contextid']);
            role_unassign_all($rparams, false, false);
        } else {
            role_unassign($params['roleid'], $params['userid'], $params['contextid']);
        }

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unassign_role_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }


    // 

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_full_users_parameters() {
        return new external_function_parameters(
            array('courseidsource' => new external_value(PARAM_TEXT, 'The source for course, can be '),
                'courseid' => new external_value(PARAM_TEXT, 'The course id'),
                'options'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'  => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'option value')
                        )
                    ), 'Option names:
                            * withcapability (string) return only users with this capability. This option requires \'moodle/role:review\' on the course context.
                            * groupid (integer) return only users in this group id. If the course has groups enabled and this param
                                                isn\'t defined, returns all the viewable users.
                                                This option requires \'moodle/site:accessallgroups\' on the course context if the
                                                user doesn\'t belong to the group.
                            * onlyactive (integer) return only users with active enrolments and matching time restrictions. This option requires \'moodle/course:enrolreview\' on the course context.
                            * userfields (\'string, string, ...\') return only the values of these user fields.
                            * limitfrom (integer) sql limit from.
                            * limitnumber (integer) maximum number of returned users.
                            * sortby (string) sort by id, firstname or lastname. For ordering like the site does, use siteorder.
                            * sortdirection (string) ASC or DESC',
                            VALUE_DEFAULT, array()),
            )
        );
    }

    public static function get_enrolled_full_users($courseidsource, $courseid, $options = array()) {

        // Validate parameters.
        $parameters = array('courseidsource' => $courseidsource,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_enrolled_users_parameters(), $parameters);

        $params['courseid'] = self::validate_course_param($params, array('idnumber', 'shortname', 'id'));

        return \core_enrol_external::get_enrolled_users($courseid, $options);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_enrolled_full_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'    => new external_value(PARAM_INT, 'ID of the user'),
                    'username'    => new external_value(PARAM_RAW, 'Username policy is defined in Moodle security config', VALUE_OPTIONAL),
                    'firstname'   => new external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL),
                    'lastname'    => new external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
                    'fullname'    => new external_value(PARAM_NOTAGS, 'The fullname of the user'),
                    'email'       => new external_value(PARAM_TEXT, 'An email address - allow email as root@localhost', VALUE_OPTIONAL),
                    'address'     => new external_value(PARAM_TEXT, 'Postal address', VALUE_OPTIONAL),
                    'phone1'      => new external_value(PARAM_NOTAGS, 'Phone 1', VALUE_OPTIONAL),
                    'phone2'      => new external_value(PARAM_NOTAGS, 'Phone 2', VALUE_OPTIONAL),
                    'icq'         => new external_value(PARAM_NOTAGS, 'icq number', VALUE_OPTIONAL),
                    'skype'       => new external_value(PARAM_NOTAGS, 'skype id', VALUE_OPTIONAL),
                    'yahoo'       => new external_value(PARAM_NOTAGS, 'yahoo id', VALUE_OPTIONAL),
                    'aim'         => new external_value(PARAM_NOTAGS, 'aim id', VALUE_OPTIONAL),
                    'msn'         => new external_value(PARAM_NOTAGS, 'msn number', VALUE_OPTIONAL),
                    'department'  => new external_value(PARAM_TEXT, 'department', VALUE_OPTIONAL),
                    'institution' => new external_value(PARAM_TEXT, 'institution', VALUE_OPTIONAL),
                    'idnumber'    => new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution', VALUE_OPTIONAL),
                    'interests'   => new external_value(PARAM_TEXT, 'user interests (separated by commas)', VALUE_OPTIONAL),
                    'firstaccess' => new external_value(PARAM_INT, 'first access to the site (0 if never)', VALUE_OPTIONAL),
                    'lastaccess'  => new external_value(PARAM_INT, 'last access to the site (0 if never)', VALUE_OPTIONAL),
                    'description' => new external_value(PARAM_RAW, 'User profile description', VALUE_OPTIONAL),
                    'descriptionformat' => new external_format_value('description', VALUE_OPTIONAL),
                    'city'        => new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                    'url'         => new external_value(PARAM_URL, 'URL of the user', VALUE_OPTIONAL),
                    'country'     => new external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
                    'profileimageurlsmall' => new external_value(PARAM_URL, 'User image profile URL - small version', VALUE_OPTIONAL),
                    'profileimageurl' => new external_value(PARAM_URL, 'User image profile URL - big version', VALUE_OPTIONAL),
                    'customfields' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'type'  => new external_value(PARAM_ALPHANUMEXT, 'The type of the custom field - text field, checkbox...'),
                                'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                                'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                                'shortname' => new external_value(PARAM_RAW, 'The shortname of the custom field - to be able to build the field class in the code'),
                            )
                        ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
                    'groups' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id'  => new external_value(PARAM_INT, 'group id'),
                                'name' => new external_value(PARAM_RAW, 'group name'),
                                'description' => new external_value(PARAM_RAW, 'group description'),
                                'descriptionformat' => new external_format_value('description'),
                            )
                        ), 'user groups', VALUE_OPTIONAL),
                    'roles' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'roleid'       => new external_value(PARAM_INT, 'role id'),
                                'name'         => new external_value(PARAM_RAW, 'role name'),
                                'shortname'    => new external_value(PARAM_ALPHANUMEXT, 'role shortname'),
                                'sortorder'    => new external_value(PARAM_INT, 'role sortorder')
                            )
                        ), 'user roles', VALUE_OPTIONAL),
                    'preferences' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preferences'),
                                'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                            )
                    ), 'User preferences', VALUE_OPTIONAL),
                    'enrolledcourses' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id'  => new external_value(PARAM_INT, 'Id of the course'),
                                'fullname' => new external_value(PARAM_RAW, 'Fullname of the course'),
                                'shortname' => new external_value(PARAM_RAW, 'Shortname of the course')
                            )
                    ), 'Courses where the user is enrolled - limited by which courses the user is able to see', VALUE_OPTIONAL)
                )
            )
        );
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_users_parameters() {
        return new external_function_parameters(
            array('courseidsource' => new external_value(PARAM_TEXT, 'The source for course, can be '),
                'courseid' => new external_value(PARAM_TEXT, 'The course id'),
                'options'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'  => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'option value')
                        )
                    ), 'Option names:
                            * withcapability (string) return only users with this capability. This option requires \'moodle/role:review\' on the course context.
                            * groupid (integer) return only users in this group id. If the course has groups enabled and this param
                                                isn\'t defined, returns all the viewable users.
                                                This option requires \'moodle/site:accessallgroups\' on the course context if the
                                                user doesn\'t belong to the group.
                            * onlyactive (integer) return only users with active enrolments and matching time restrictions. This option requires \'moodle/course:enrolreview\' on the course context.
                            * userfields (\'string, string, ...\') return only the values of these user fields.
                            * limitfrom (integer) sql limit from.
                            * limitnumber (integer) maximum number of returned users.
                            * sortby (string) sort by id, firstname or lastname. For ordering like the site does, use siteorder.
                            * sortdirection (string) ASC or DESC',
                            VALUE_DEFAULT, array()),
            )
        );
    }

    public static function get_enrolled_users($courseidsource, $courseid, $options = array()) {

        // Validate parameters.
        $parameters = array('courseidsource' => $courseidsource,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_enrolled_users_parameters(), $parameters);

        $validkeys = array('idnumber', 'shortname', 'id');
        $courseid = self::validate_course_param($params, $validkeys);

        return \core_enrol_external::get_enrolled_users($courseid, $options);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_enrolled_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'    => new external_value(PARAM_INT, 'ID of the user'),
                    'username'    => new external_value(PARAM_RAW, 'Username policy is defined in Moodle security config'),
                    'firstname'   => new external_value(PARAM_NOTAGS, 'The first name(s) of the user'),
                    'lastname'    => new external_value(PARAM_NOTAGS, 'The family name of the user'),
                    'idnumber'    => new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution'),
                )
            )
        );
    }

    protected static function validate_role_param(&$inputs, &$validkeys) {

        if (!in_array($inputs['roleidsource'], $validkeys)) {
            throw new invalid_parameter_exception('Role source not in acceptable ranges.');
        }

        switch ($inputs['roleidsource']) {
            case 'id': {
                return $inputs['roleid'];
                break;
            }

            case 'shortname': {
                if (!$role = $DB->get_record('role', array('shortname' => $inputs['roleid']))) {
                    throw new invalid_parameter_exception('Role not found by shortname: '.$inputs['roleid']);
                }
                return $role->id;
                break;
            }
        }
    }

    protected static function validate_user_param(&$inputs, &$validkeys) {
        global $DB, $CFG;

        if (!in_array($inputs['useridsource'], $validkeys)) {
            throw new invalid_parameter_exception('User source not in acceptable ranges.');
        }

        switch ($inputs['useridsource']) {
            case 'id': {
                break;
                return $inputs['userid'];
            }

            case 'username': {
                if (preg_match('/^(.*)§(.*)$/', $inputs['userid'])) {
                    list($username, $hostroot) = explode('§', $inputs['userid']);
                    $hostid = $DB->get_field('mnet_host', 'id', array('wwwroot' => $hostroot));
                } else {
                    $hostid = $CFG->mnet_localhost_id;
                }
                if (!$user = $DB->get_record('user', array('username' => $inputs['userid'], 'mnethostid' => $hostid))) {
                    throw new invalid_parameter_exception('User not found by username: '.$inputs['userid']);
                }
                return $user->id;
                break;
            }

            case 'idnumber': {
                if (!$user = $DB->get_record('user', array('idnumber' => $inputs['userid']))) {
                    throw new invalid_parameter_exception('User not found by idnumber: '.$inputs['userid']);
                }
                return $user->id;
                break;
            }

            case 'email': {
                if (!$user = $DB->get_record('user', array('email' => $inputs['userid']))) {
                    throw new invalid_parameter_exception('User not found by email: '.$inputs['userid']);
                }
                return $user->id;
                break;
            }
        }
    }

    protected static function validate_course_param(&$inputs, &$validkeys) {
        global $DB;

        if (!in_array($inputs['courseidsource'], $validkeys)) {
            throw new invalid_parameter_exception('Course id source not in acceptable ranges');
        }

        switch ($inputs['courseidsource']) {
            case 'id': {
                return $inputs['courseid'];
                break;
            }

            case 'shortname': {
                if (!$course = $DB->get_record('course', array('shortname' => $inputs['courseid']))) {
                    throw new invalid_parameter_exception('Course not found by shortname');
                }
                return $course->id;
                break;
            }

            case 'idnumber': {
                if (!$course = $DB->get_record('course', array('idnumber' => $inputs['courseid']))) {
                    throw new invalid_parameter_exception('Course not found by idnumber');
                }
                return $course->id;
                break;
            }
        }
    }
}

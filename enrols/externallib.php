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
require_once($CFG->dirroot.'/enrol/externallib.php');
require_once($CFG->dirroot.'/admin/tool/sync/externallib.php');

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

    protected static $enroluserset;

    protected static $roleassignset;

    protected static function init_user_set() {
        self::$enroluserset = array('roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
            'roleid' => new external_value(PARAM_TEXT, 'The role id'),
            'useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
            'userid' => new external_value(PARAM_TEXT, 'The user id'),
            'courseidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
            'courseid' => new external_value(PARAM_TEXT, 'The course identifier'),
            'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'manual'),
            'timestart' => new external_value(PARAM_INT, 'Time start of the enrol period', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'Time end of the enrol period', VALUE_DEFAULT, 0),
            'suspend' => new external_value(PARAM_INT, 'Suspension', VALUE_DEFAULT, 0),
        );
    }

    protected static function init_roleassign_set() {
        self::$roleassignset = array('roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
            'roleid' => new external_value(PARAM_TEXT, 'The role id'),
            'useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
            'userid' => new external_value(PARAM_TEXT, 'The user id'),
            'contexttype' => new external_value(PARAM_TEXT, 'The context type'),
            'instanceidsource' => new external_value(PARAM_TEXT, 'The source for the context attached instance'),
            'instanceid' => new external_value(PARAM_TEXT, 'The instance identifier'),
            'shiftrole' => new external_value(PARAM_BOOL, 'If true, will remove other roles', VALUE_DEFAULT, 0),
            'component' => new external_value(PARAM_TEXT, 'If set, allow forcing a targer component', VALUE_DEFAULT, ''),
        );
    }

    protected static function validate_enrol_parameters($configparamdefs, $inputs, $isunenrol = false) {

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
        self::init_user_set();
        return new external_function_parameters(self::$enroluserset);
    }

    /**
     * Enrol a single user
     *
     * @return the user enrolment id
     */
    public static function enrol_user($roleidsource, $roleid, $useridsource, $userid, $courseidsource, $courseid,
                                      $method = 'manual', $timestart = 0, $timeend = 0, $suspend = 0) {
        global $CFG, $DB;

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

        if ($method != 'sync') {
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
        } else {
            if (!file_exists($CFG->dirroot.'/enrol/sync/lib.php')) {
                throw new moodle_exception('Trying to use enrol/sync plugin but not installed here.');
            }

            include_once($CFG->dirroot.'/enrol/sync/lib.php');

            // Course id has been already checked.
            $course = $DB->get_record('course', array('id' => $courseid));
            $status = (!empty($params['suspend'])) ? ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;
            // May fire an exception if something goes wrong internally.
            \enrol_sync_plugin::static_enrol_user($course, $params['userid'], $params['roleid'],
                                                  $params['timestart'], $params['timeend'], $status);
        }

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

    // Unenrol a user.

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
        global $CFG, $DB;

        $parameters = array('useridsource' => $useridsource,
            'userid' => $userid,
            'courseidsource' => $courseidsource,
            'courseid' => $courseid,
            'roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'method' => $method);
        $params = self::validate_enrol_parameters(self::unenrol_user_parameters(), $parameters, true);

        if ($method != 'sync') {
            $class = 'enrol_'.$params['method'].'_external';

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
        } else {
            if (!file_exists($CFG->dirroot.'/enrol/sync/lib.php')) {
                throw new moodle_exception('Trying to use enrol/sync plugin but not installed here.');
            }
            include_once($CFG->dirroot.'/enrol/sync/lib.php');

            // Course id has been already checked.
            $course = $DB->get_record('course', array('id' => $courseid));
            \enrol_sync_plugin::static_unenrol_user($course, $params['userid']);
        }

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

    // Enrol a set of users.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function enrol_users_parameters() {
        self::init_user_set();
        return new external_function_parameters(
            array('enrols' => new external_multiple_structure(
                    new external_single_structure(self::$enroluserset)
                ),
            )
        );
    }

    /**
     * Enrol a set of users
     *
     * @return an array of operaton status
     */
    public static function enrol_users($enrols) {

        raise_memory_limit(MEMORY_HUGE);

        $results = array();
        if (!empty($enrols)) {
            foreach ($enrols as $enrol) {
                $result = new Stdclass;
                $result->userid = $enrol['userid'];
                $result->status = self::enrol_user($enrol['roleidsource'],
                                                        $enrol['roleid'],
                                                        $enrol['useridsource'],
                                                        $enrol['userid'],
                                                        $enrol['courseidsource'],
                                                        $enrol['courseid'],
                                                        $enrol['method'],
                                                        $enrol['timestart'],
                                                        $enrol['timeend'],
                                                        $enrol['suspend']);
                $results[] = $result;
            }
        }

        return $results;

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function enrol_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_TEXT, 'User identifier'),
                    'status' => new external_value(PARAM_BOOL, 'Success status')
                )
            )
        );
    }


    // Unenrol a set of users.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unenrol_users_parameters() {
        return new external_function_parameters(
            array('enrols' => new external_multiple_structure(
                    new external_single_structure(
                    array('useridsource' => new external_value(PARAM_TEXT, 'The source for user identification'),
                        'userid' => new external_value(PARAM_TEXT, 'The user id'),
                        'courseidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
                        'courseid' => new external_value(PARAM_TEXT, 'The course identifier'),
                        'roleidsource' => new external_value(PARAM_TEXT, 'The source for role identification', VALUE_DEFAULT, ''),
                        'roleid' => new external_value(PARAM_TEXT, 'The role id', VALUE_DEFAULT, ''),
                        'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'manual'),
                        )
                    )
                ),
            )
        );
    }

    /**
     * Enrol a set of users
     *
     * @return an array of operaton status
     */
    public static function unenrol_users($enrols) {

        raise_memory_limit(MEMORY_HUGE);

        $results = array();
        if (!empty($enrols)) {
            foreach ($enrols as $enrol) {
                $result = new Stdclass;
                $result->userid = $enrol['userid'];
                $result->status = self::unenrol_user($enrol['useridsource'],
                                                     $enrol['userid'],
                                                     $enrol['courseidsource'],
                                                     $enrol['courseid'],
                                                     $enrol['roleidsource'],
                                                     $enrol['roleid'],
                                                     $enrol['method']);
                $results[] = $result;
            }
        }

        return $results;

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unenrol_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_TEXT, 'User identifier'),
                    'status' => new external_value(PARAM_BOOL, 'Success status')
                )
            )
        );
    }

    // Data validators ------------------------------------------------.

    // Role assigns related.

    public function validate_role_parameters($configparamdefs, $inputs) {
        global $DB, $CFG;

        $status = self::validate_parameters($configparamdefs, $inputs);

        $contexttypestxt = array(
            'system' => CONTEXT_SYSTEM,
            'coursecat' => CONTEXT_COURSECAT,
            'course' => CONTEXT_COURSE,
            'module' => CONTEXT_MODULE,
            'block' => CONTEXT_BLOCK,
            'user' => CONTEXT_USER);

        if (!is_numeric($inputs['contexttype'])) {
            $originaltype = $inputs['contextytype'];
            $inputs['contexttype'] = $contexttypestxt[strtolower($inputs['contextype'])];
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

        if (!in_array($inputs['instanceidsource'], $validkeys['instance'][$inputs['contexttype']])) {
            throw new invalid_parameter_exception('Context source not in acceptable ranges for contexttype '.$originaltype);
        }

        $status['roleid'] = self::validate_role_param($inputs, $validkeys['role']);
        $status['userid'] = self::validate_user_param($inputs, $validkeys['user']);

        if ($params['contexttype'] == CONTEXT_SYSTEM) {
            $status['contextid'] = context_system::instance()->id;
        } else {
            switch ($inputs['instanceidsource']) {
                case 'id': {
                    switch ($inputs['contexttype']) {
                        case CONTEXT_COURSECAT: {
                            if (!$instance = $DB->get_record('course_categories', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_coursecat::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_COURSE: {
                            if (!$instance = $DB->get_record('course', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_course::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_MODULE: {
                            if (!$instance = $DB->get_record('course_modules', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_module::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_BLOCK: {
                            if (!$instance = $DB->get_record('format_page_items', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_block::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_USER: {
                            if (!$instance = $DB->get_record('user', array('id' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by id: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_user::instance($instance->id)->id;
                            break 2;
                        }
                    }
                }

                // Only in course context.
                case 'shortname': {
                    if (!$instance = $DB->get_record('course', array('shortname' => $inputs['instanceid']))) {
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
                    list($modname, $instanceid) = explode('§', $inputs['instanceid']);
                    if (!$instance = get_coursemodule_from_instance($modname, $instanceid)) {
                        throw new invalid_parameter_exception('Course not found by shortname: '.$inputs['instanceid']);
                    }
                    $status['contextid'] = context_course::instance($instance->courseid)->id;
                    break;
                }

                case 'idnumber': {
                    switch ($inputs['contexttype']) {
                        case CONTEXT_COURSECAT: {
                            if (!$instance = $DB->get_record('course_categories', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_coursecat::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_COURSE: {
                            if (!$instance = $DB->get_record('course', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_course::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_MODULE: {
                            if (!$instance = $DB->get_record('course_modules', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_module::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_BLOCK: {
                            if (!$instance = $DB->get_record('format_page_items', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_block::instance($instance->id)->id;
                            break 2;
                        }

                        case CONTEXT_USER: {
                            if (!$instance = $DB->get_record('user', array('idnumber' => $inputs['instanceid']))) {
                                throw new invalid_parameter_exception('Instance not found by idnumber: '.$inputs['instanceid']);
                            }
                            $status['contextid'] = context_user::instance($instance->id)->id;
                            break 2;
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
        self::init_roleassign_set();
        return new external_function_parameters(self::$roleassignset);
    }

    /**
     * assign a role to a user
     *
     * @return the user enrolment id.
     */
    public static function assign_role($roleidsource, $roleid, $useridsource, $userid, $contexttype, $instanceidsource,
                                       $instanceid, $shiftrole = false, $component = false) {
        global $DB;

        // Validate parameters.
        $parameters = array('roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'useridsource' => $useridsource,
            'userid' => $userid,
            'contexttype' => $contexttype,
            'instanceidsource' => $instanceidsource,
            'instanceid' => $instanceid,
            'shiftrole' => $shiftrole,
            'component' => $component,
        );
        $params = self::validate_role_parameters(self::assign_role_parameters(), $parameters);

        if (($component === false) &&
                file_exists($CFG->dirroot.'/enrol/sync') &&
                        enrol_is_enabled('sync')) {
            // True if no forcing by param and enrol_sync is installed and enabled.
            $component = 'enrol_sync';
        }
        if (($component === false)) {
            $component = '';
        }

        $return = role_assign($params['roleid'], $params['userid'], $params['contextid'], $component);

        if ($shiftrole) {
            // Get all previous roles and unassign them.
            $sql = "
                SELECT
                    r.*
                FROM
                    {role} r,
                    {role_assignments} ra
                WHERE
                    r.id = ra.roleid AND
                    ra.roleid != :roleid AND
                    ra.userid = :userid AND
                    ra.contextid = :contextid
            ";
            $otherroles = $DB->get_records_sql($sql, array('roleid' => $params['roleid'], 'userid' => $params['userid'], 'contextid' => $params['contextid']));
            if ($otherroles) {
                foreach ($otherroles as $r) {
                    role_unassign($r->id, $params['userid'], $params['contextid'], $component);
                }
            }
        }

        return $return;
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
    public static function assign_roles_parameters() {
        self::init_roleassign_set();
        return new external_function_parameters(
            array('roleassigns' => new external_multiple_structure(
                    new external_single_structure(self::$roleassignset)
                ),
            )
        );
    }

    /**
     * Operates a set of roleassigns
     *
     * @return an array of operation status
     */
    public static function assign_roles($roleassigns) {

        $results = array();
        if (!empty($roleassigns)) {
            foreach ($roleassigns as $assign) {
                $result = new Stdclass;
                $result->userid = $assign['userid'];
                $result->status = self::assign_role($assign['roleidsource'],
                                                        $assign['roleid'],
                                                        $assign['useridsource'],
                                                        $assign['userid'],
                                                        $assign['contexttype'],
                                                        $assign['instanceidsource'],
                                                        $assign['instanceid'],
                                                        $assign['shiftrole'],
                                                        $assign['component']);

                $results[] = $result;
            }
        }

        return $results;

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function assign_roles_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_TEXT, 'User identifier'),
                    'status' => new external_value(PARAM_BOOL, 'Success status')
                )
            )
        );
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
                'component' => new external_value(PARAM_TEXT, 'If set, forces the component scope of the assignation', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * assign a role to a user
     *
     * @return the user enrolment id
     */
    public static function unassign_role($roleidsource, $roleid, $useridsource, $userid, $contexttype, $instanceidsource, $instanceid, $component = false) {
        global $CFG, $DB;

        // Validate parameters.
        $parameters = array('roleidsource' => $roleidsource,
            'roleid' => $roleid,
            'useridsource' => $useridsource,
            'userid' => $userid,
            'contexttype' => $contexttype,
            'instanceidsource' => $instanceidsource,
            'instanceid' => $instanceid,
            'component' => $component,
            );
        $params = self::validate_role_parameters(self::unassign_role_parameters(), $parameters);

        if (($component === false) &&
                file_exists($CFG->dirroot.'/enrol/sync') &&
                        enrol_is_enabled('sync')) {
            // True if no forcing by param and enrol_sync is installed and enabled.
            $component = 'enrol_sync';
        }
        if ($component === false) {
            $component = '';
        }

        if ($params['roleid'] == '*') {
            $rparams = array('userid' => $params['userid'], 'contextid' => $params['contextid'], 'component' => $component);
            if (function_exists('debug_trace')) {
                debug_trace("Unassigning all roles in $component ".print_r($params, true));
            }
            $ras = $DB->get_records('role_assignments', $rparams);
            if ($ras) {
                foreach ($ras as $ra) {
                    $DB->delete_records('role_assignments', array('id' => $ra->id));
                    if ($context = context::instance_by_id($ra->contextid, IGNORE_MISSING)) {
                        // this is a bit expensive but necessary
                        $context->mark_dirty();
                        tool_sync_category_role_assignment_changed($ra->roleid, $context);
                    }
                }
                unset($ras);
            }
        } else {
            $rparams = array('roleid' => $params['roleid'], 'userid' => $params['userid'], 'contextid' => $params['contextid'], 'component' => $component);
            if (function_exists('debug_trace')) {
                debug_trace("Unassigning in $component ".print_r($rparams, true));
            }
            $ras = $DB->get_records('role_assignments', $rparams);
            if ($ras) {
                foreach ($ras as $ra) {
                    $DB->delete_records('role_assignments', array('id' => $ra->id));
                    if ($context = context::instance_by_id($ra->contextid, IGNORE_MISSING)) {
                        // this is a bit expensive but necessary
                        $context->mark_dirty();
                        tool_sync_category_role_assignment_changed($ra->roleid, $context);
                    }
                }
                unset($ras);
            }
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

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unassign_roles_parameters() {
        self::init_roleassign_set();
        return new external_function_parameters(
            array('roleunassigns' => new external_multiple_structure(
                    new external_single_structure(self::$roleassignset)
                ),
            )
        );
    }

    /**
     * Operates a set of role deassignations
     *
     * @return an array of operation status
     */
    public static function unassign_roles($roleunassigns) {

        $results = array();
        if (!empty($roleunassigns)) {
            foreach ($roleunassigns as $unassign) {
                $result = new Stdclass;
                $result->userid = $unassign['userid'];
                $result->status = self::unassign_role($unassign['roleidsource'],
                                                        $unassign['roleid'],
                                                        $unassign['useridsource'],
                                                        $unassign['userid'],
                                                        $unassign['contexttype'],
                                                        $unassign['instanceidsource'],
                                                        $unassign['instanceid'],
                                                        $unassign['component']);

                $results[] = $result;
            }
        }

        return $results;

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unassign_roles_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_TEXT, 'User identifier'),
                    'status' => new external_value(PARAM_BOOL, 'Success status')
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_full_users_parameters() {
        return new external_function_parameters(
            array('courseidsource' => new external_value(PARAM_TEXT, 'The source for course, can be id, shortname or idnumber'),
                'courseid' => new external_value(PARAM_TEXT, 'The course id'),
                'options'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'  => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'option value')
                        )
                    ), 'Option names:
                            * withcapability (string) return only users with this capability. This option requires
                                    \'moodle/role:review\' on the course context.
                            * groupid (integer) return only users in this group id. If the course has groups enabled and this
                                    param isn\'t defined, returns all the viewable users.
                                    This option requires \'moodle/site:accessallgroups\' on the course context if the
                                    user doesn\'t belong to the group.
                            * onlyactive (integer) return only users with active enrolments and matching time restrictions.
                            * This option requires \'moodle/course:enrolreview\' on the course context.
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
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/lib.php');

        // Validate parameters.
        $parameters = array('courseidsource' => $courseidsource,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_enrolled_users_parameters(), $parameters);

        $validkeys = array('idnumber', 'shortname', 'id');
        $params['courseid'] = self::validate_course_param($params, $validkeys);

        /* Copy all code of original here. change : avoid validating context as it creates a weird redirection. */

        self::options_check($options, $withcapability, $groupid, $onlyactive, $userfields, $limitfrom, $limitnumber,
                            $sortby, $sortparams, $sortdirection);

        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        $coursecontext = context_course::instance($params['courseid'], IGNORE_MISSING);
        if ($params['courseid'] == SITEID) {
            $context = context_system::instance();
        } else {
            $context = $coursecontext;
        }

        if ($params['courseid'] == SITEID) {
            require_capability('moodle/site:viewparticipants', $context);
        } else {
            require_capability('moodle/course:viewparticipants', $context);
        }
        // To overwrite this parameter, you need role:review capability.
        if ($withcapability) {
            require_capability('moodle/role:review', $coursecontext);
        }
        // Need accessallgroups capability if you want to overwrite this option.
        if (!empty($groupid) && !groups_is_member($groupid)) {
            require_capability('moodle/site:accessallgroups', $coursecontext);
        }
        // To overwrite this option, you need course:enrolereview permission.
        if ($onlyactive) {
            require_capability('moodle/course:enrolreview', $coursecontext);
        }

        list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext, $withcapability, $groupid, $onlyactive);
        $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
        $enrolledparams['contextlevel'] = CONTEXT_USER;

        $groupjoin = '';
        if (empty($groupid) && groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $coursecontext)) {
            // Filter by groups the user can view.
            $usergroups = groups_get_user_groups($course->id);
            if (!empty($usergroups['0'])) {
                list($groupsql, $groupparams) = $DB->get_in_or_equal($usergroups['0'], SQL_PARAMS_NAMED);
                $groupjoin = "JOIN {groups_members} gm ON (u.id = gm.userid AND gm.groupid $groupsql)";
                $enrolledparams = array_merge($enrolledparams, $groupparams);
            } else {
                // User doesn't belong to any group, so he can't see any user. Return an empty array.
                return array();
            }
        }
        $sql = "SELECT us.*
                  FROM {user} us
                  JOIN (
                      SELECT DISTINCT u.id $ctxselect
                        FROM {user} u $ctxjoin $groupjoin
                       WHERE u.id IN ($enrolledsql)
                  ) q ON q.id = us.id
                ORDER BY $sortby $sortdirection";
        $enrolledparams = array_merge($enrolledparams, $sortparams);
        $enrolledusers = $DB->get_recordset_sql($sql, $enrolledparams, $limitfrom, $limitnumber);
        $users = array();
        foreach ($enrolledusers as $user) {
            context_helper::preload_from_record($user);
            if ($userdetails = user_get_user_details($user, $course, $userfields)) {
                $users[] = $userdetails;
            }
        }
        $enrolledusers->close();

        return $users;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_enrolled_full_users_returns() {

        tool_sync_external::full_user_set_init();
        $fulluserset = tool_sync_external::$fullusersetbase;

        $fulluserset['groups'] = new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'  => new external_value(PARAM_INT, 'group id'),
                    'name' => new external_value(PARAM_RAW, 'group name'),
                    'description' => new external_value(PARAM_RAW, 'group description'),
                    'descriptionformat' => new external_format_value('description'),
                )
            ), 'user groups', VALUE_OPTIONAL);

        $fulluserset['roles'] = new external_multiple_structure(
            new external_single_structure(
                array(
                    'roleid'       => new external_value(PARAM_INT, 'role id'),
                    'name'         => new external_value(PARAM_RAW, 'role name'),
                    'shortname'    => new external_value(PARAM_ALPHANUMEXT, 'role shortname'),
                    'sortorder'    => new external_value(PARAM_INT, 'role sortorder')
                )
            ), 'user roles', VALUE_OPTIONAL);
        $fulluserset['preferences'] = new external_multiple_structure(
            new external_single_structure(
                array(
                    'name'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preferences'),
                    'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                )
            ), 'User preferences', VALUE_OPTIONAL);
        $fulluserset['enrolledcourses'] = new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'  => new external_value(PARAM_INT, 'Id of the course'),
                    'fullname' => new external_value(PARAM_RAW, 'Fullname of the course'),
                    'shortname' => new external_value(PARAM_RAW, 'Shortname of the course')
                )
            ), 'Courses where the user is enrolled - limited by which courses the user is able to see', VALUE_OPTIONAL);

        return new external_multiple_structure(new external_single_structure($fulluserset));
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_users_parameters() {
        return new external_function_parameters(
            array('courseidsource' => new external_value(PARAM_TEXT, 'The source for course, can be id, idnumber, shortname'),
                'courseid' => new external_value(PARAM_TEXT, 'The course id'),
                'options'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'  => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'option value')
                        )
                    ), 'Option names:
                            * withcapability (string) return only users with this capability. This option requires \'moodle/role:review\'
                            * on the course context.
                            * groupid (integer) return only users in this group id. If the course has groups enabled and this param
                                                isn\'t defined, returns all the viewable users.
                                                This option requires \'moodle/site:accessallgroups\' on the course context if the
                                                user doesn\'t belong to the group.
                            * onlyactive (integer) return only users with active enrolments and matching time restrictions. This option
                            * requires \'moodle/course:enrolreview\' on the course context.
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
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/lib.php');

        // Validate parameters.
        $parameters = array('courseidsource' => $courseidsource,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_enrolled_users_parameters(), $parameters);

        $validkeys = array('idnumber', 'shortname', 'id');
        $params['courseid'] = self::validate_course_param($params, $validkeys);

        /* Copy all code of original here. change : avoid validating context as it creates a weird redirection. */

        self::options_check($options, $withcapability, $groupid, $onlyactive, $userfields, $limitfrom, $limitnumber,
                            $sortby, $sortparams, $sortdirection);

        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        $coursecontext = context_course::instance($params['courseid'], IGNORE_MISSING);
        if ($course->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = $coursecontext;
        }

        if ($course->id == SITEID) {
            require_capability('moodle/site:viewparticipants', $context);
        } else {
            require_capability('moodle/course:viewparticipants', $coursecontext);
        }
        // To overwrite this parameter, you need role:review capability.
        if ($withcapability) {
            require_capability('moodle/role:review', $coursecontext);
        }
        // Need accessallgroups capability if you want to overwrite this option.
        if (!empty($groupid) && !groups_is_member($groupid)) {
            require_capability('moodle/site:accessallgroups', $coursecontext);
        }
        // To overwrite this option, you need course:enrolereview permission.
        if ($onlyactive) {
            require_capability('moodle/course:enrolreview', $coursecontext);
        }

        list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext, $withcapability, $groupid, $onlyactive);
        $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
        $enrolledparams['contextlevel'] = CONTEXT_USER;

        $groupjoin = '';
        if (empty($groupid) && groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $coursecontext)) {
            // Filter by groups the user can view.
            $usergroups = groups_get_user_groups($course->id);
            if (!empty($usergroups['0'])) {
                list($groupsql, $groupparams) = $DB->get_in_or_equal($usergroups['0'], SQL_PARAMS_NAMED);
                $groupjoin = "JOIN {groups_members} gm ON (u.id = gm.userid AND gm.groupid $groupsql)";
                $enrolledparams = array_merge($enrolledparams, $groupparams);
            } else {
                // User doesn't belong to any group, so he can't see any user. Return an empty array.
                return array();
            }
        }
        $sql = "SELECT us.*
                  FROM {user} us
                  JOIN (
                      SELECT DISTINCT u.id $ctxselect
                        FROM {user} u $ctxjoin $groupjoin
                       WHERE u.id IN ($enrolledsql)
                  ) q ON q.id = us.id
                ORDER BY $sortby $sortdirection";
        $enrolledparams = array_merge($enrolledparams, $sortparams);
        $enrolledusers = $DB->get_recordset_sql($sql, $enrolledparams, $limitfrom, $limitnumber);
        $users = array();
        foreach ($enrolledusers as $user) {
            context_helper::preload_from_record($user);
            if ($userdetails = user_get_user_details($user, $course, $userfields)) {
                $users[] = $userdetails;
            }
        }
        $enrolledusers->close();

        $lightusers = array();
        if ($users) {
            foreach ($users as $user) {
                $lightuser = new StdClass;
                $lightuser->id = $user['id'];
                $lightuser->username = $user['username'];
                $lightuser->firstname = $user['firstname'];
                $lightuser->lastname = $user['lastname'];
                $lightuser->idnumber = $user['idnumber'];
                $lightusers[] = $lightuser;
            }
        }

        return $lightusers;
    }

    protected static function options_check($options, &$withcapability, &$groupid, &$onlyactive, &$userfields, &$limitfrom,
                                            &$limitnumber, &$sortby, &$sortparams, &$sortdirection) {
        $withcapability = '';
        $groupid = 0;
        $onlyactive = false;
        $userfields = array();
        $limitfrom = 0;
        $limitnumber = 0;
        $sortby = 'us.id';
        $sortparams = array();
        $sortdirection = 'ASC';

        foreach ($options as $option) {

            switch ($option['name']) {
                case 'withcapability': {
                    $withcapability = $option['value'];
                    break;
                }

                case 'groupid': {
                    $groupid = (int)$option['value'];
                    break;
                }

                case 'onlyactive': {
                    $onlyactive = !empty($option['value']);
                    break;
                }

                case 'userfields': {
                    $thefields = explode(',', $option['value']);
                    foreach ($thefields as $f) {
                        $userfields[] = clean_param($f, PARAM_ALPHANUMEXT);
                    }
                    break;
                }

                case 'limitfrom': {
                    $limitfrom = clean_param($option['value'], PARAM_INT);
                    break;
                }

                case 'limitnumber': {
                    $limitnumber = clean_param($option['value'], PARAM_INT);
                    break;
                }

                case 'sortby': {
                    $sortallowedvalues = array('id', 'firstname', 'lastname', 'siteorder');
                    if (!in_array($option['value'], $sortallowedvalues)) {
                        throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $option['value'] . '),' .
                            'allowed values are: ' . implode(',', $sortallowedvalues));
                    }
                    if ($option['value'] == 'siteorder') {
                        list($sortby, $sortparams) = users_order_by_sql('us');
                    } else {
                        $sortby = 'us.' . $option['value'];
                    }
                    break;
                }

                case 'sortdirection': {
                    $sortdirection = strtoupper($option['value']);
                    $directionallowedvalues = array('ASC', 'DESC');
                    if (!in_array($sortdirection, $directionallowedvalues)) {
                        throw new invalid_parameter_exception('Invalid value for sortdirection parameter
                            (value: ' . $sortdirection . '),' . 'allowed values are: ' . implode(',', $directionallowedvalues));
                    }
                    break;
                }
            }
        }
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
        global $DB;

        if (!in_array($inputs['roleidsource'], $validkeys)) {
            throw new invalid_parameter_exception('Role source '.$inputs['roleidsource'].' not in acceptable ranges.');
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
                return $inputs['userid'];
                break;
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
                    throw new invalid_parameter_exception('Course not found by shortname for '.$inputs['courseid']);
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

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
 * Tool Sync cohort API allows external applications to bind cohorts to courses
 * with a cohort based enrol instance.
 * Compatible with some additional cohort related methods as delayedcohort or
 * cohortrestricted plugins.
 *
 * @package    tool_sync
 * @category   external
 * @copyright  2017 Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/admin/tool/sync/cohorts/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Tool Sync control functions
 *
 * @package    tool_sync
 * @category   external
 * @copyright  2017 Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_sync_cohort_ext_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
<<<<<<< HEAD
=======
     * @since Moodle 2.5
     */
    public static function get_cohorts_parameters() {
        return new external_function_parameters(
            array(
                'cohortidsource' => new external_value(PARAM_TEXT, 'Cohort identification source'),
                'cohortids' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Cohort ID')
                    , 'List of cohort id. A cohort id is an integer.', VALUE_DEFAULT, array()),
            )
        );
    }

    /**
     * Get cohorts definition specified by ids
     *
     * @param array $cohortids array of cohort ids
     * @return array of cohort objects (id, courseid, name)
     * @since Moodle 2.5
     */
    public static function get_cohorts($cohortidsource, $cohortids = array()) {
        global $DB;

        $params = self::validate_parameters(self::get_cohorts_parameters(), array('cohortidsource' => $cohortidsource, 'cohortids' => $cohortids));

        if (empty($cohortids)) {
            $cohorts = $DB->get_records('cohort');
        } else {
            if ($cohortidsource == 'id') {
                $cohorts = $DB->get_records_list('cohort', 'id', $params['cohortids']);
            } else if ($cohortidsource == 'idnumber') {
                $cohorts = $DB->get_records_list('cohort', 'idnumber', $params['cohortids']);
            } else {
                throw new invalid_parameter_exception('Cohort id source not in accepted range');
            }
        }

        $cohortsinfo = array();
        foreach ($cohorts as $cohort) {
            // Now security checks.
            $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
            if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
                throw new invalid_parameter_exception('Invalid context');
            }
            self::validate_context($context);
            if (!has_any_capability(array('moodle/cohort:manage', 'moodle/cohort:view'), $context)) {
                throw new required_capability_exception($context, 'moodle/cohort:view', 'nopermissions', '');
            }

            list($cohort->description, $cohort->descriptionformat) = external_format_text($cohort->description,
                                                                                          $cohort->descriptionformat,
                                                                                          $context->id,
                                                                                          'cohort',
                                                                                          'description',
                                                                                          $cohort->id);

            $cohortsinfo[] = (array) $cohort;
        }
        return $cohortsinfo;
    }


    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function get_cohorts_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'ID of the cohort'),
                    'name' => new external_value(PARAM_RAW, 'cohort name'),
                    'idnumber' => new external_value(PARAM_RAW, 'cohort idnumber'),
                    'description' => new external_value(PARAM_RAW, 'cohort description'),
                    'descriptionformat' => new external_format_value('description'),
                    'visible' => new external_value(PARAM_BOOL, 'cohort visible'),
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
>>>>>>> MOODLE_33_STABLE
     */
    public static function bind_cohort_parameters() {
        return new external_function_parameters(
            array('chidsource' => new external_value(PARAM_TEXT, 'The source for cohort identification'),
                'chid' => new external_value(PARAM_TEXT, 'The cohort id'),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
                'cid' => new external_value(PARAM_TEXT, 'The course identifier'),
                'ridsource' => new external_value(PARAM_TEXT, 'The source for role identification'),
                'rid' => new external_value(PARAM_TEXT, 'The role identifier'),
                'method' => new external_value(PARAM_TEXT, 'The enrol method (plugin)', VALUE_DEFAULT, 'manual'),
                'timestart' => new external_value(PARAM_INT, 'Time start of the enrol period', VALUE_DEFAULT, 0),
                'timeend' => new external_value(PARAM_INT, 'Time end of the enrol period', VALUE_DEFAULT, 0),
                'suspend' => new external_value(PARAM_INT, 'Suspension', VALUE_DEFAULT, 0),
                'makegroup' => new external_value(PARAM_INT, 'Make group', VALUE_DEFAULT, 0),
                'extparam1' => new external_value(PARAM_TEXT, 'Extra param 1', VALUE_DEFAULT, ''),
                'extparam2' => new external_value(PARAM_TEXT, 'Extra param 2', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Enrol a single user
     *
     * @return the user enrolment id
     */
    public static function bind_cohort($chidsource, $chid, $cidsource, $cid, $ridsource, $rid,
                                      $method = 'cohort', $timestart = 0, $timeend = 0, $suspend = 0,
                                      $makegroup = 0, $extraparam1 = '', $extraparam2 = '') {

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $chid);
        $cohort = self::validate_cohort_parameters($parameters);

        // Validate parameters.
        $parameters = array('ridsource' => $ridsource,
            'rid' => $rid);
        $role = self::validate_role_parameters($parameters);

        self::validate_method_parameter($method);

        tool_sync_execute_bind('add', $method, $course->id, $cohort->id, $role->id, $timestart, $timeend,
                               $makegroup, $extraparam1, $extraparam2);

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function bind_cohort_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }

    // Commit an uploaded file.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unbind_cohort_parameters() {
        return new external_function_parameters(
            array('chidsource' => new external_value(PARAM_TEXT, 'The source for cohort identification'),
                'chid' => new external_value(PARAM_TEXT, 'The cohort id'),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for course identification'),
                'cid' => new external_value(PARAM_TEXT, 'The course identifier'),
<<<<<<< HEAD
                'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'cohort'),
=======
                'method' => new external_value(PARAM_TEXT, 'The enrol method (needs bing a cohort related method)', VALUE_DEFAULT, 'cohort'),
>>>>>>> MOODLE_33_STABLE
            )
        );
    }

    /**
     * Unenrol a single user
     *
     * @return external_description
     */
    public static function unbind_cohort($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $chid);
        $cohort = self::validate_cohort_parameters($parameters);

        self::validate_method_parameter($method);

        tool_sync_execute_bind('fulldel', $method, $course->id, $cohort->id, '*');

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unbind_cohort_returns() {
        return new external_value(PARAM_BOOL, 'Success status');
    }

    // Role assigns related.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function suspend_enrol_parameters() {
        return new external_function_parameters(
            array('chidsource' => new external_value(PARAM_TEXT, 'The source for cohort identification'),
                'chid' => new external_value(PARAM_TEXT, 'The cohort id'),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for course identification', VALUE_DEFAULT, 'shortname'),
                'cid' => new external_value(PARAM_TEXT, 'The course id', VALUE_DEFAULT, ''),
                'method' => new external_value(PARAM_TEXT, 'The instance identifier', VALUE_DEFAULT, 'cohort'),
            )
        );
    }

    /**
     * suspends a single cohort enrol or all cohort enrols of a cohort. This will NOT
     * unenrol students, but freeze their access to courses.
     *
     * @return a boolean status
     */
    public static function suspend_enrol($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $chid);
        $cohort = self::validate_cohort_parameters($parameters);

        tool_sync_execute_bind('del', $method, $course->id, $cohort->id, '*');

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function suspend_enrol_returns() {
        return new external_value(PARAM_BOOL, 'A return status');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function restore_enrol_parameters() {
        return new external_function_parameters(
            array('chidsource' => new external_value(PARAM_TEXT, 'The source for cohort identification'),
                'chid' => new external_value(PARAM_TEXT, 'The cohort id'),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for course identification', VALUE_DEFAULT, 'shortname'),
                'cid' => new external_value(PARAM_TEXT, 'The course id', VALUE_DEFAULT, ''),
                'method' => new external_value(PARAM_TEXT, 'The method (plugin)', VALUE_DEFAULT, 'cohort'),
            )
        );
    }

    /**
     * enables an existing cohort enrol binding or all bindings of a cohort.
     *
     * @return the user enrolment id
     */
    public static function restore_enrol($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $chid);
        $cohort = self::validate_cohort_parameters($parameters);

        tool_sync_execute_bind('restore', $method, $course->id, $cohort->id, $role->id);

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function restore_enrol_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_users_parameters() {
        return new external_function_parameters(
            array('chidsource' => new external_value(PARAM_TEXT, 'The source for cohort, can be '),
                'chid' => new external_value(PARAM_TEXT, 'The cohort id'),
                'options'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'  => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'option value')
                        )
                    ), 'Option names:
                            * userfields (\'string, string, ...\') return only the values of these user fields.
                            * limitfrom (integer) sql limit from.
                            * limitnumber (integer) maximum number of returned users.
                            * sortby (string) sort by id, firstname or lastname. For ordering like the site does, use siteorder.
                            * sortdirection (string) ASC or DESC',
                            VALUE_DEFAULT, array()),
            )
        );
    }

    public static function get_users($chidsource, $chid, $options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/user/lib.php");

        $parameters = array(
            'chidsource' => $chidsource,
            'chid' => $chid,
        );

        $cohort = self::validate_cohort_parameters($parameters);

        /* Copy all code of original here. change : avoid validating context as it creates a weird redirection. */

        $userfields     = array();
        $limitfrom = 0;
        $limitnumber = 0;
        $sortby = 'u.id';
        $sortparams = array();
        $sortdirection = 'ASC';
        foreach ($options as $option) {
            switch ($option['name']) {
                case 'userfields':
                    $thefields = explode(',', $option['value']);
                    foreach ($thefields as $f) {
                        $userfields[] = clean_param($f, PARAM_ALPHANUMEXT);
                    }
                    break;
                case 'limitfrom':
                    $limitfrom = clean_param($option['value'], PARAM_INT);
                    break;
                case 'limitnumber':
                    $limitnumber = clean_param($option['value'], PARAM_INT);
                    break;
                case 'sortby':
                    $sortallowedvalues = array('id', 'firstname', 'lastname', 'siteorder');
                    if (!in_array($option['value'], $sortallowedvalues)) {
                        throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $option['value'] . '),' .
                            'allowed values are: ' . implode(',', $sortallowedvalues));
                    }
                    if ($option['value'] == 'siteorder') {
                        list($sortby, $sortparams) = users_order_by_sql('u');
                    } else {
                        $sortby = 'u.' . $option['value'];
                    }
                    break;
                case 'sortdirection':
                    $sortdirection = strtoupper($option['value']);
                    $directionallowedvalues = array('ASC', 'DESC');
                    if (!in_array($sortdirection, $directionallowedvalues)) {
                        throw new invalid_parameter_exception('Invalid value for sortdirection parameter
                            (value: ' . $sortdirection . '),' . 'allowed values are: ' . implode(',', $directionallowedvalues));
                    }
                    break;
            }
        }

        $sql = "
            SELECT
                u.*
            FROM
                {user} u,
                {cohort_members} cm
            WHERE
                cm.userid = u.id AND
                cm.cohortid = ?
            ORDER BY
                $sortby $sortdirection
        ";
        $cohortusers = $DB->get_recordset_sql($sql, array($cohort->id), $limitfrom, $limitnumber);
        $users = array();
        foreach ($cohortusers as $user) {
            context_helper::preload_from_record($user);
            if ($userdetails = user_get_user_details($user, null, $userfields)) {
                $users[] = $userdetails;
            }
        }
        $cohortusers->close();

        return $users;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_users_returns() {

        tool_sync_external::full_user_set_init();
        $fulluserset = tool_sync_external::$fullusersetbase;

        $fulluserset['preferences'] = new external_multiple_structure(
            new external_single_structure(
                array(
                    'name'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preferences'),
                    'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                )
            ), 'User preferences', VALUE_OPTIONAL);

        return new external_multiple_structure(new external_single_structure($fulluserset));
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters(
            array(
                'chidsource' => new external_value(PARAM_TEXT, 'The source for cohort id, can be \'id\' or \'idnumber\''),
                'chid' => new external_value(PARAM_TEXT, 'The cohort identifier'),
            )
        );
    }

    /**
     * Identical to the standard cohort delete call, but with extended
     * identification input
     */
    public static function delete($chidsource, $chid) {

        $parameters = array(
            'chidsource' => $chidsource,
            'chid' => $chid
        );

        // Non blocking call to validate params.
        if ($cohort = self::validate_cohort_parameters($parameters, false)) {
            cohort_delete_cohort($cohort);
            return true;
        }

        return false;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function delete_returns() {
        return new external_value(PARAM_BOOL, 'Operation status. If false, cohort was not found');
    }

<<<<<<< HEAD
=======
    /* ---------------------------------------- Wrappers for core_cohort functions -------------------------------.

    /**
     * Completes the input possibilities of the core core_add_cohort_members
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function add_cohort_members_parameters() {
        return new external_function_parameters (
            array(
                'members' => new external_multiple_structure (
                    new external_single_structure (
                        array (
                            'cohorttype' => new external_single_structure (
                                array(
                                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The name of the field: id
                                        (numeric value of cohortid) or idnumber (alphanumeric value of idnumber) '),
                                    'value' => new external_value(PARAM_RAW, 'The value of the cohort identifier')
                                )
                            ),
                            'usertype' => new external_single_structure (
                                array(
                                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The name of the field: numeric id
                                         or username or idnumber '),
                                    'value' => new external_value(PARAM_RAW, 'The value of the user identifier')
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Add cohort members
     *
     * @param array $members of arrays with keys userid, cohortid
     * @since Moodle 2.5
     */
    public static function add_cohort_members($members) {
        global $CFG;

        require_once($CFG->dirroot."/cohort/externallib.php");

        // Transforms the members input into a core acceptable structure.
        $coremembers = array();

        $i = 0;
        foreach ($members as $member) {

            if (empty($member['usertype']['value'])) {
                throw new invalid_parameter_exception('Null User idnumber at record '.$i);
            }

            $inputs = array(
                'uidsource' => $member['usertype']['type'],
                'uid' => $member['usertype']['value']
            );
            $user = self::validate_user_parameters($inputs);

            $member['usertype']['type'] = 'id';
            $member['usertype']['value'] = $user->id;
            $coremembers[] = $member;
            $i++;
        }

        return core_cohort_external::add_cohort_members($coremembers);
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.5
     */
    public static function add_cohort_members_returns() {
        return new external_single_structure(
            array(
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function delete_cohort_members_parameters() {
        return new external_function_parameters(
            array(
                'members' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'cohorttype' => new external_single_structure(
                                array(
                                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The name of the field: id or idnumber '),
                                    'value' => new external_value(PARAM_RAW, 'The value of the cohort identifier')
                                )
                            ),
                            'usertype' => new external_single_structure(
                                array(
                                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The name of the field: numeric id
                                         or username or idnumber '),
                                    'value' => new external_value(PARAM_RAW, 'The value of the user identifier')
                                )
                            )
                        )
                    )
                ),
                'blocking' => new external_value(PARAM_BOOL, 'If set tp 0, will let continue on input errors', VALUE_DEFAULT, 1)
            )
        );
    }

    /**
     * Delete cohort members
     *
     * @param array $members of arrays with keys userid, cohortid
     * @since Moodle 2.5
     */
    public static function delete_cohort_members($members, $blocking = true) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/cohort/externallib.php");

        $coremembers = array();
        foreach ($members as $member) {
            $inputs = array(
                'chidsource' => $member['cohorttype']['type'],
                'chid' => $member['cohorttype']['value']
            );
            $cohort = self::validate_cohort_parameters($inputs, $blocking);

            $inputs = array(
                'uidsource' => $member['usertype']['type'],
                'uid' => $member['usertype']['value']
            );
            $user = self::validate_user_parameters($inputs, $blocking);

            $coremember = array(
                'cohortid' => $cohort->id,
                'userid' => $user->id
            );
            $coremembers[] = $coremember;
        }

        return core_cohort_external::delete_cohort_members($coremembers);
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.5
     */
    public static function delete_cohort_members_returns() {
        return null;
    }

    /* ---------------------------------------- Object validators ---------------------------------------.

>>>>>>> MOODLE_33_STABLE
    /**
     * @param array $inputs
     * @param bool $blocking if false, may return null or false
     */
    protected static function validate_cohort_parameters(&$inputs, $blocking = true) {
        global $DB;

        $validkeys = array('id', 'idnumber');
        if (!in_array($inputs['chidsource'], $validkeys)) {
            if ($blocking) {
                throw new invalid_parameter_exception('Cohort source not in acceptable ranges.');
            }
        }

        switch ($inputs['chidsource']) {
            case 'id': {
                if (!$cohort = $DB->get_record('cohort', array('id' => $inputs['chid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Cohort not found by id : '.$inputs['chid']);
                    }
                }
                return $cohort;
                break;
            }

            case 'idnumber': {
                if (!$cohort = $DB->get_record('cohort', array('idnumber' => $inputs['chid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Cohort not found by idnumber : '.$inputs['chid']);
                    }
                }
                return $cohort;
                break;
            }
        }
    }

    /**
     * @param array $inputs
     * @param bool $blocking if false, may return null or false
     */
    protected static function validate_role_parameters(&$inputs, $blocking = true) {
        global $DB;

        $validkeys = array('id', 'shortname');
        if (!in_array($inputs['ridsource'], $validkeys)) {
            if ($blocking) {
                throw new invalid_parameter_exception('Role source not in acceptable ranges.');
            }
        }

        switch ($inputs['ridsource']) {
            case 'id': {
                if (!$role = $DB->get_record('role', array('id' => $inputs['rid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Role not found by id : '.$inputs['rid']);
                    }
                }
                return $role;
                break;
            }

            case 'shortname': {
                if (!$role = $DB->get_record('role', array('shortname' => $inputs['rid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Role not found by shortname : '.$inputs['rid']);
                    }
                }
                return $role;
                break;
            }
        }
    }

    protected static function validate_course_parameters(&$inputs) {
        global $DB;

        $validkeys = array('id', 'idnumber', 'shortname');
        if (!in_array($inputs['cidsource'], $validkeys)) {
            throw new invalid_parameter_exception('Course id source not in acceptable ranges');
        }

        switch ($inputs['cidsource']) {
            case 'id': {
                if (!$course = $DB->get_record('course', array('id' => $inputs['cid']))) {
                    throw new invalid_parameter_exception('Course not found by id for '.$inputs['cid']);
                }
                return $course;
            }

            case 'shortname': {
                if (!$course = $DB->get_record('course', array('shortname' => $inputs['cid']))) {
                    throw new invalid_parameter_exception('Course not found by shortname for '.$inputs['cid']);
                }
                return $course;
            }

            case 'idnumber': {
                if (!$course = $DB->get_record('course', array('idnumber' => $inputs['cid']))) {
                    throw new invalid_parameter_exception('Course not found by idnumber for '.$inputs['cid']);
                }
                return $course;
            }
        }
    }

<<<<<<< HEAD
=======
    protected static function validate_user_parameters(&$inputs, $blocking = true) {
        global $DB;

        $validkeys = array('id', 'idnumber', 'username', 'email');
        if (!in_array($inputs['uidsource'], $validkeys)) {
            throw new invalid_parameter_exception('User id source not in acceptable ranges');
        }

        switch ($inputs['uidsource']) {
            case 'id': {
                if (!$user = $DB->get_record('user', array('id' => $inputs['uid']))) {
                    throw new invalid_parameter_exception('User not found by id for '.$inputs['uid']);
                }
                return $user;
            }

            case 'username': {
                if (!$user = $DB->get_record('user', array('username' => $inputs['uid']))) {
                    throw new invalid_parameter_exception('User not found by username for '.$inputs['uid']);
                }
                return $user;
            }

            case 'idnumber': {
                if (!$user = $DB->get_record('user', array('idnumber' => $inputs['uid']))) {
                    throw new invalid_parameter_exception('User not found by idnumber for '.$inputs['uid']);
                }
                return $user;
            }

            case 'email': {
                if (!$user = $DB->get_record('user', array('email' => $inputs['uid']))) {
                    throw new invalid_parameter_exception('User not found by email for '.$inputs['uid']);
                }
                return $user;
            }
        }
    }

>>>>>>> MOODLE_33_STABLE
    protected static function validate_method_parameter($method) {

        $supportedmethods = array('cohort', 'delayedcohort', 'cohortrestricted');

        if (!in_array($method, $supportedmethods)) {
            throw new invalid_parameter_exception('Bad enrol method '.$method);
        }
        return true;
    }
}

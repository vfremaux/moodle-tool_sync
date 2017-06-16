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
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
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
        global $CFG;

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $roleid);
        $cohort = self::validate_cohort_parameters($parameters);

        // Validate parameters.
        $parameters = array('ridsource' => $chidsource,
            'rid' => $roleid);
        $role = self::validate_role_parameters($parameters);

        $params = array('courseid' => $course->id, 'enrol' => $method, 'customint1' => $cohort->id);
        if ($DB->record_exists('enrol', $params)) {
            // ensure it is enabled.
            $DB->set_field('enrol', 'status', 0, $params);
            return true;
        }

        self::validate_method_parameter($method);

        $lastorder = $DB->get_field('enrol', 'MAX(sortorder)', array('courseid' => $courseid));
        if ($lastorder === false) {
            $lastorder = 0;
        } else {
            $lastorder++;
        }

        $enrol = new StdClass;
        $enrol->courseid = $course->id;
        $enrol->status = 0;
        $enrol->enrol = $method;
        $enrol->sortorder = $lastorder;
        $enrol->roleid = $role->id;
        $enrol->enrolstartdate = $timestart;
        $enrol->enrolenddate = $timeend;
        $enrol->customint1 = $cohort->id;
        $enrol->customint2 = $makegroup;

        $DB->insert_record('enrol');

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
                'method' => new external_value(PARAM_TEXT, 'The enrol method', VALUE_DEFAULT, 'cohort'),
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
            'chid' => $roleid);
        $cohort = self::validate_cohort_parameters($parameters);

        tool_sync_cohort_execute_bind('fulldel', $method, $course->id, $cohort->id, $role->id);

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
    public static function suspend_enrol($chidsource, $chid, $cidsource, $cid, $method) {

        // Validate parameters.
        $parameters = array('cidsource' => $cidsource,
            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Validate parameters.
        $parameters = array('chidsource' => $chidsource,
            'chid' => $roleid);
        $cohort = self::validate_cohort_parameters($parameters);

        tool_sync_cohort_execute_bind('del', $method, $course->id, $cohort->id, $role->id);

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
        global $CFG, $USER, $DB;
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
        $sortby = 'us.id';
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
                        list($sortby, $sortparams) = users_order_by_sql('us');
                    } else {
                        $sortby = 'us.' . $option['value'];
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
                {cohort_memebers} cm
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
            if ($userdetails = user_get_user_details($user, $course, $userfields)) {
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

        $desc = 'The shortname of the custom field - to be able to build the field class in the code';

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
                                'shortname' => new external_value(PARAM_RAW, $desc),
                            )
                        ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
                    'preferences' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preferences'),
                                'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                            )
                    ), 'User preferences', VALUE_OPTIONAL),
                )
            )
        );
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
        if ($cohort = self::validate_cohort_parameters($paramseters, false)) {
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
                if (!$cohort = $DB->get_record('role', array('id' => $inputs['chid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Cohort not found by id : '.$inputs['chid']);
                    }
                }
                return $cohort;
                break;
            }

            case 'idnumber': {
                if (!$cohort = $DB->get_record('role', array('idnumber' => $inputs['chid']))) {
                    if ($blocking) {
                        throw new invalid_parameter_exception('Cohort not found by shortname : '.$inputs['chid']);
                    }
                }
                return $cohort;
                break;
            }
        }
    }

    protected static function validate_course_parameters(&$inputs) {
        global $DB;

        $validkeys = array('id', 'idnumber', 'shortname');
        if (!in_array($inputs['courseidsource'], $validkeys)) {
            throw new invalid_parameter_exception('Course id source not in acceptable ranges');
        }

        switch ($inputs['courseidsource']) {
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
}

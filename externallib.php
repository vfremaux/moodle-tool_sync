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

    public static $fullusersetbase;

    public static function full_user_set_init() {

        $desc = 'The shortname of the custom field - to be able to build the field class in the code';

        if (is_null(self::$fullusersetbase)) {
            self::$fullusersetbase = array(
                'id'          => new external_value(PARAM_INT, 'ID of the user'),
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
            );
        }
    }

    protected static function validate_config_parameters($configparamdefs, $inputs) {
        $status = self::validate_parameters($configparamdefs, $inputs);

        $validkeys = array(
            'courses' => array('fileuploadlocation',
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
            'enrols' => array('filelocation',
                'courseidentifier',
                'useridentifier')
        );

        if (!in_array($inputs['confkey'], $validkeys[$inputs['service']])) {
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
        self::validate_config_parameters(self::set_config_parameters(),
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

    // Commit an uploaded file .

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function commit_file_parameters() {
        return new external_function_parameters(
            array(
                'draftitemid' => new external_value(PARAM_INT, 'Awaiting draftitem'),
            )
        );
    }

    /**
     * Commits the version that has ben previously uploaded using the webservice/upload.php facility.
     *
     * @param string $vridsource the source field for the resource identifier.
     * @param string $vrid the versionnedresource id
     * @param int $draftitemid the temporary draft id of the uploaded file. This has been given by the upload return.
     *
     * @return external_description
     */
    public static function commit_file($draftitemid) {
        global $CFG;

        $parameters = array(
            'draftitemid' => $draftitemid,
        );
        self::validate_parameters(self::commit_file_parameters(), $parameters);

        if (tool_sync_supports_feature('api/commit')) {
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
            tool_sync_commit($draftitemid);
            return true;
        } else {
            throw new moodle_exception('proreleasefeature');
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function commit_file_returns() {
        return new external_value(PARAM_BOOL, 'Success status');
    }

    // Process a synchronisation task .

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
            'courses' => array('check' => SYNC_COURSE_CHECK,
                'delete' => SYNC_COURSE_DELETE,
                'create' => SYNC_COURSE_CREATE,
                'reset' => SYNC_COURSE_RESET,
                'bindmetas' => SYNC_COURSE_METAS),
            'users' => array('sync' => true),
            'cohorts' => array('sync' => true),
            'enrols' => array('sync' => true)
        );

        if (!array_key_exists($inputs['service'], $validactions)) {
            throw new invalid_parameter_exception('Service not in acceptable ranges.');
        }
        if (!array_key_exists($inputs['action'], $validactions[$inputs['service']])) {
            throw new invalid_parameter_exception('Service action not in acceptable ranges.');
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
        global $CFG;

        // Validate parameters.
        self::validate_process_parameters(self::process_parameters(),
                        array('service' => $service, 'action' => $action));

        if (tool_sync_supports_feature('api/process')) {
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
            tool_sync_process($service, $action);
            return true;
        } else {
            throw new moodle_exception('proreleasefeature');
        }

    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function process_returns() {
        return new external_value(PARAM_TEXT, 'CSV report');
    }

    // Check a courxe exists.

    public static function check_course_parameters() {
        return new external_function_parameters(array(
                'courseidsource' => new external_value(PARAM_TEXT, 'ID source to check, can be id, idnumber, or shortname'),
                'courseid' => new external_value(PARAM_TEXT, 'Template ID'),
            )
        );
    }

    protected static function validate_check_parameters($configparamdefs, $inputs) {
        $status = self::validate_parameters($configparamdefs, $inputs);

        $validsources = array('id', 'idnumber', 'shortname');

        if (!in_array($inputs['courseidsource'], $validsources)) {
            throw new invalid_parameter_exception('ID source not in acceptable range.');
        }

        return $status;
    }

    /**
     * Get query result data as raw data in a single value.
     *
     * @param int $courseid course id
     * @param array $options These options are not used yet, might be used in later version
     * @return array
     */
    public static function check_course($courseidsource, $courseid) {
        global $DB;

        // Validate parameters.
        self::validate_check_parameters(self::check_course_parameters(), array(
            'courseidsource' => $courseidsource,
            'courseid' => $courseid,
            )
        );

        return $DB->record_exists('course', array($courseidsource => $courseid));
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function check_course_returns() {
        return new external_value(PARAM_BOOL, 'Course existance status');
    }

    // Create course from a template.

    public static function deploy_course_parameters() {
        return new external_function_parameters(array(
                'categoryidsource' => new external_value(PARAM_TEXT, 'ID source for category, can be id or idnumber'),
                'categoryid' => new external_value(PARAM_TEXT, 'Category identifier'),
                'templateidsource' => new external_value(PARAM_TEXT, 'ID source to identifiy template course, can be id, idnumber, or shortname'),
                'templateid' => new external_value(PARAM_TEXT, 'Template ID'),
                'shortname' => new external_value(PARAM_TEXT, 'New course shortname'),
                'fullname' => new external_value(PARAM_TEXT, 'New course fullname'),
                'idnumber' => new external_value(PARAM_TEXT, 'New course idnumber'),
                'replacements' => new external_value(PARAM_TEXT, 'Replacement JSON structure', VALUE_DEFAULT, '', true)
            )
        );
    }

    protected static function validate_deploy_parameters($configparamdefs, $inputs) {
        global $DB;

        $status = self::validate_parameters($configparamdefs, $inputs);

        $validsources = array('id', 'idnumber');

        if (!in_array($inputs['categoryidsource'], $validsources)) {
            throw new invalid_parameter_exception('Category ID source not in acceptable range.');
        }

        if (!$DB->record_exists('course_categories', array($inputs['categoryidsource'] => $inputs['categoryid']))) {
            throw new invalid_parameter_exception('Category does not exist.');
        }

        $validsources = array('id', 'idnumber', 'shortname');

        if (!in_array($inputs['templateidsource'], $validsources)) {
            throw new invalid_parameter_exception('Template ID source not in acceptable range.');
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
    public static function deploy_course($categoryidsource, $categoryid, $templateidsource, $templateid,
                                         $shortname, $fullname, $idnumber, $replacements = '') {
        global $CFG;

        // Validate parameters.
        self::validate_deploy_parameters(self::deploy_course_parameters(), array(
            'categoryidsource' => $categoryidsource,
            'categoryid' => $categoryid,
            'templateidsource' => $templateidsource,
            'templateid' => $templateid,
            'shortname' => $shortname,
            'fullname' => $fullname,
            'idnumber' => $idnumber,
            'replacements' => $replacements));

        if (tool_sync_supports_feature('api/deploy')) {
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
            $result = tool_sync_deploy($categoryidsource, $categoryid, $templateidsource, $templateid, $shortname,
                                       $fullname, $idnumber, $replacements);
            return $result;
        } else {
            throw new moodle_exception('proreleasefeature');
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function deploy_course_returns() {
        return new external_value(PARAM_INT, 'Course ID');
    }
}

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

    // Commit an uploaded file ----------------------------------------------.

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
        $params = self::validate_parameters(self::commit_file_parameters(), $parameters);

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

    // Process a synchronisation task -----------------------------------------.

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
                'reset' => SYNC_COURSE_RESET),
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
        $params = self::validate_process_parameters(self::process_parameters(),
                        array('service' => $service, 'action' => $action));

        if (tool_sync_supports_feature('api/process')) {
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
            tool_sync_process($draftitemid);
            return true;
        } else {
            throw new moodle_exception('proreleasefeature');
        }

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
        return new external_function_parameters(array(
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
    public static function deploy_course($categorysourceid, $categoryid, $templatesourceid, $templateid,
                                         $shortname, $fullname, $idnumber) {

        // Validate parameters.
        $params = self::validate_process_parameters(self::process_parameters(), array(
            'categorysourceid' => $categorysourceid,
            'categoryid' => $categoryid,
            'templatesourceid' => $templatesourceid,
            'templateid' => $templateid,
            'shortname' => $shortname,
            'fullname' => $fullname,
            'idnumber' => $idnumber));

        if (tool_sync_supports_feature('api/deploy')) {
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
            $result = tool_sync_deploy($categorysourceid, $categoryid, $templatesourceid, $templateid, $shortname,
                                       $fullname, $idnumber);
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
        return new external_value(PARAM_INT, 'Course id');
    }
}

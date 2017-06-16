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
 * Tool sync external functions and service definitions.
 *
 * @package    tool_sync
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    'tool_sync_set_config' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'set_config',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'Set configuration of the tool sync tool',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),

    'tool_sync_commit_file' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'commit_file',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'Commits a file that has been preloaded in moodle draftarea',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),

    'tool_sync_process' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'process',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'Launches a synchronisation tool',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),

    'tool_sync_check_course' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'check_course',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'checks a course exists based on one of identifier source',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),

    'tool_sync_deploy_course' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'deploy_course',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'Deployes a course using a local template or backup',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),

    'tool_sync_enrol_user_enrol' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'enrol_user',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'enrol user',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_enrol_user_unenrol' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'unenrol_user',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'Unenrol user',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_enrol_role_assign' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'assign_role',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'Add a role to user in some context',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_enrol_role_unassign' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'unassign_role',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'Remove role assignation(s) of user in some context',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_get_enrolled_users' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'get_enrolled_users',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'get list of enrolled users identities from a course got by any identifier source',
        'type' => 'read',
        'capabilities' => ''
    ),

    'tool_sync_get_enrolled_full_users' => array(
        'classname' => 'tool_sync_core_ext_external',
        'methodname' => 'get_enrolled_full_users',
        'classpath' => 'admin/tool/sync/enrols/externallib.php',
        'description' => 'get list of full filled enrolled users from a course got by any identifier source',
        'type' => 'read',
        'capabilities' => ''
    ),

    'tool_sync_cohort_bind' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'bind_cohort',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Binds a cohort to a course with an enrol instance',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_cohort_unbind' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'unbind_cohort',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Unbinds a cohort from a course with an enrol instance',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_cohort_suspend_enrol' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'suspend_enrol',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Suspends a cohort enrol instance',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_cohort_restore_enrol' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'restore_enrol',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Enables a cohort enrol instance that was suspended',
        'type' => 'write',
        'capabilities' => ''
    ),

    'tool_sync_cohort_get_users' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'get_users',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Get full info about members',
        'type' => 'read',
        'capabilities' => ''
    ),

    'tool_sync_cohort_delete' => array(
        'classname' => 'tool_sync_cohort_ext_external',
        'methodname' => 'delete',
        'classpath' => 'admin/tool/sync/cohorts/externallib.php',
        'description' => 'Deletes a cohort if exists',
        'type' => 'write',
        'capabilities' => ''
    ),
);

$services = array(
    'Moodle Admin Sync Tool API'  => array(
        'functions' => array (
            'tool_sync_set_config',
            'tool_sync_commit_file',
            'tool_sync_process',
            'tool_sync_check_course',
            'tool_sync_deploy_course',
        ),
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'tool_sync',
        'downloadfiles' => 1,
        'uploadfiles' => 1
    ),
    'Moodle Core Extension API'  => array(
        'functions' => array (
            'tool_sync_enrol_user_enrol',
            'tool_sync_enrol_user_unenrol',
            'tool_sync_get_enrolled_users',
            'tool_sync_get_enrolled_full_users',
        ),
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'tool_sync_core_ext',
        'downloadfiles' => 0,
        'uploadfiles' => 0
    ),
);

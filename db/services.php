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

    'tool_sync_deploy_course' => array(
        'classname' => 'tool_sync_external',
        'methodname' => 'deploy_course',
        'classpath' => 'admin/tool/sync/externallib.php',
        'description' => 'Deployes a course using a local template or backup',
        'type' => 'read',
        'capabilities' => 'moodle/site:config'
    ),
);

$services = array(
    'Moodle Admin Sync Tool API'  => array(
        'functions' => array (
            'tool_sync_set_config',
            'tool_sync_commit_file',
            'tool_sync_process',
            'tool_sync_deploy_course',
        ),
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'tool_sync',
        'downloadfiles' => 1,
        'uploadfiles' => 1
    ),
);

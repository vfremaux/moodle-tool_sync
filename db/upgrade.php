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
 * @package   tool_sync
 * @category  tool
 * @author Funck Thibaut
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_tool_sync_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2015112600) {
        require_once($CFG->dirroot.'/admin/tool/sync/db/install.php');
        xmldb_tool_sync_install();

        // Tool Sync savepoint reached.
        upgrade_plugin_savepoint(true, 2015112600, 'tool', 'sync');
    }

    if ($oldversion < 2017071701) {
        // Tool Sync savepoint reached.

        // Fix version loss in some 3.1 and older instances.
        if ($DB->record_exists('config_plugins', array('plugin' => 'tool_sync', 'name' => 'encoding'))) {
            if (!$DB->record_exists('config_plugins', array('plugin' => 'tool_sync', 'name' => 'version'))) {
                $versionrec = new StdClass;
                $versionrec->plugin = 'tool_sync';
                $versionrec->name = 'version';
                $versionrec->value = 2017071701;
                $DB->insert_record('config_plugins', $versionrec);
            }
        }

        upgrade_plugin_savepoint(true, 2017071701, 'tool', 'sync');
    }

    return true;
}
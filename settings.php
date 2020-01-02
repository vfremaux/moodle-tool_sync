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
 * Flatfile enrolments plugin settings and presets.
 *
 * @package    tool_sync
 * @copyright  2010 Valery Feemaux
 * @author     Valery Fremaux - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$systemcontext = context_system::instance();
if ($hassiteconfig || (!empty($hasconfig) && has_capability('tool/sync:configure', $systemcontext))) {
    if (!$ADMIN->locate('automation')) {
        $ADMIN->add('root', new admin_category('automation', new lang_string('automation', 'tool_sync')));
    }

    // General settings.
    $syncurl = new moodle_url('/admin/tool/sync/index.php');
    $label = get_string('pluginname', 'tool_sync');
    $ADMIN->add('automation', new admin_externalpage('toolsync', $label, $syncurl, 'tool/sync:configure'));
    if ($hassiteconfig) {
        // Needs having plugin/tools/.
        $ADMIN->add('tools', new admin_externalpage('toolsynctools', $label, $syncurl, 'tool/sync:configure'));
    }
}

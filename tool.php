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
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sync;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/users/users.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/enrol/enrols.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');

class tool_plugin_sync {

    function form_elements(&$frm) {
        $frm->addElement('header', 'globalconf1', get_string('configuration', 'tool_sync'));
        
        $encodings = array('UTF-8' => 'UTF-8', 'ISO-8859-1' => 'ISO-8859-1');
        $frm->addElement('select', 'tool_sync/encoding', get_string('encoding', 'tool_sync'), $encodings);
        $separators = array(',' => ', (coma)', ';' => '; (semicolon)');
        $frm->addElement('select', 'tool_sync/csvseparator', get_string('csvseparator', 'tool_sync'), $separators);

        $frm->addElement('header', 'globalconf3', get_string('final_action', 'tool_sync'));

        $frm->addElement('checkbox', 'tool_sync/enrolcleanup', get_string('group_clean', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/storereport', get_string('storereport', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filearchive', get_string('filearchive', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filefailed', get_string('failedfile', 'tool_sync'));
        $frm->addElement('checkbox', 'tool_sync/filecleanup', get_string('filecleanup', 'tool_sync'));
    }
}

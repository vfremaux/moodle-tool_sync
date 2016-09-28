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

function xmldb_tool_sync_install() {
    global $DB;

    // Will add a custom user info field to stroe avatar checksum.

    if (!$DB->record_exists('user_info_field', array('shortname' => 'userpicturehash'))) {
        $rec = new StdClass();
        $rec->shortname = 'userpicturehash';
        $rec->name = get_string('userpicturehash', 'tool_sync');
        $rec->datatype = 'textarea';
        $rec->description = '';
        $rec->descriptionformat = 0;
        $rec->required = 0;
        $rec->locked = 1;
        $rec->visible = 0;
        $rec->categoryid = 0;
        $rec->sortorder = 999;
        $DB->insert_record('user_info_field', $rec);
    }

    return true;
}

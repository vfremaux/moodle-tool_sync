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
 * Cross versions compatibility functions.
 *
 * @copyright 2008 valery.fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prior 3.6
require_once($CFG->dirroot.'/lib/coursecatlib.php');

function tool_sync_category_role_assignment_changed($roleid, $context) {
    return \coursecat::role_assignment_changed($roleid, $context);
}

function tool_sync_get_category($catid) {
    return \coursecat::get($catid);
}


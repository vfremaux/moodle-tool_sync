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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

class clean_categories_form extends moodleform {

    public function definition() {
        global $DB;

        $form = $this->_form;

        $categoryoptions = $DB->get_records_menu('course_categories', array(), 'parent,sortorder', 'id, name');
        $categoryoptions[0] = get_string('rootcategory', 'tool_sync');
        ksort($categoryoptions);
        $form->addElement('select', 'startcategory', get_string('startcategory', 'tool_sync'), $categoryoptions);

        $form->addElement('advcheckbox', 'ignoresubcategories', get_string('ignoresubcats', 'tool_sync'));

        $group = array();
        $group[] = $form->createElement('submit', 'go', get_string('update'));
        $group[] = $form->createElement('submit', 'confirm', get_string('confirmcleancats', 'tool_sync'));
        $form->addGroup($group, 'action', '', false, false);
    }
}
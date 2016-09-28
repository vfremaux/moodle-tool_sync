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

class InputFileLoadForm extends moodleform {

    public function definition() {

        $form = $this->_form;

        $fileoptions = array('maxfiles' => 1);

        $form->addElement('hidden', 'action');
        $form->setType('action', PARAM_TEXT);

        if (!empty($this->_customdata['localfile'])) {
            $label = get_string('uselocal', 'tool_sync', $this->_customdata['localfile']);
            $form->addElement('submit', 'uselocal', $label);
        }
        $form->setType('uselocal', PARAM_BOOL);

        if (!empty($this->_customdata['runlocalfiles'])) {
            $form->addElement('submit', 'runlocalfiles', get_string('runlocalfiles', 'tool_sync'));
        }
        $form->setType('uselocal', PARAM_BOOL);

        $form->addElement('filepicker', 'inputfile', get_string('filetoprocess', 'tool_sync'), $fileoptions);

        $form->addElement('checkbox', 'simulate', get_string('simulate', 'tool_sync'));

        $this->add_action_buttons();
    }
}
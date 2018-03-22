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
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');

class InputFileLoadForm extends moodleform {

    public function definition() {
        global $CFG, $OUTPUT;

        $form = $this->_form;

        $fileoptions = array('maxfiles' => 1);

        $form->addElement('hidden', 'action');
        $form->setType('action', PARAM_TEXT);

        // Process localfile name.
        $allfilerecs = array();
        $wildcard = false;
        if (tool_sync_supports_feature('fileloading/wildcard') &&
                preg_match('#\\*#', $this->_customdata['localfile'])) {

            $wildcard = true;
            include_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');

            $localdir = dirname($this->_customdata['localfile']);
            $localbase = basename($this->_customdata['localfile']);

            $filerec = new StdClass;
            $filerec->contextid = context_system::instance()->id;
            $filerec->component = 'tool_sync';
            $filerec->filearea = 'syncfiles';
            $filerec->itemid = 0;
            $filerec->filepath = ($localdir == '' || $localdir == '.') ? '/' : $localdir;
            $filerec->filename = $localbase;

            if ($allfilerecs = tool_sync_get_first_available_file($filerec, true)) {
                $localfilerec = array_shift($allfilerecs);
                $localfile = $localdir.'/'.$localfilerec->filename;
                $localfile = preg_replace('#^/#', '', $localfile); // Normalise.
            }
        } else {
            $localfile = $this->_customdata['localfile'];
        }

        if (!empty($localfile)) {
            $label = get_string('uselocal', 'tool_sync', $localfile);
            $form->addElement('submit', 'uselocal', $label);
            $form->setType('uselocal', PARAM_BOOL);
        } else {
            // No wildcard match.
            if ($wildcard) {
                $label = get_string('uselocal', 'tool_sync', '');
                $form->addElement('static', 'uselocal', $label, $OUTPUT->notification(get_string('nomatch', 'tool_sync')));
            }
        }

        if (!empty($allfilerecs)) {
            $html = '<ul>';
            foreach ($allfilerecs as $frec) {
                $html .= '<li>'.$frec->filepath.$frec->filename.'</li>';
            }
            $html .= '</ul>';
            $form->addElement('static', 'candidates', get_string('othermatchs', 'tool_sync'), $html);
        }

        if (!empty($this->_customdata['runlocalfiles'])) {
            $form->addElement('submit', 'runlocalfiles', get_string('runlocalfiles', 'tool_sync'));
        }
        $form->setType('uselocal', PARAM_BOOL);

        $form->addElement('filepicker', 'inputfile', get_string('filetoprocess', 'tool_sync'), $fileoptions);

        $form->addElement('checkbox', 'simulate', get_string('simulate', 'tool_sync'));

        $this->add_action_buttons(true, get_string('runnow', 'tool_sync'));
    }
}
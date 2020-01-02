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
 * @package     tool_sync
 * @category    tool
 * @author      Valery Fremaux
 * @copyright   2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sync;

use \StdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/classes/sync_manager.class.php');

/**
 * The Enrol Plugin Manager manages role assignations and enrollements from a CSV input file.
 */
class group_sync_manager extends sync_manager {

    public $execute;

    public function __construct($execute = SYNC_COURSE_GROUPS, $manualfilerec = null) {
        $this->execute = $execute;
        parent::__construct($manualfilerec);
    }

    public function form_elements(&$frm) {

        $key = 'tool_sync/groups_filelocation';
        $label = get_string('groupfilelocation', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/groups_filelocation', PARAM_TEXT);

        $key = 'tool_sync/groupmembers_filelocation';
        $label = get_string('groupmembersfilelocation', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/groupmembers_filelocation', PARAM_TEXT);

        $key = 'tool_sync/groups_courseidentifier';
        $label = get_string('groupcourseidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_coursefields());

        $key = 'tool_sync/groups_useridentifier';
        $label = get_string('groupuseridentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_userfields());

        $key = 'tool_sync/groups_groupidentifier';
        $label = get_string('groupidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_groupfields());

        $key = 'tool_sync/groups_groupingidentifier';
        $label = get_string('groupingidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->get_groupingfields());

        $key = 'tool_sync/groups_autogrouping';
        $label = get_string('groupautogrouping', 'tool_sync');
        $frm->addElement('advcheckbox', $key, $label, '', array('group' => 1), array(0, 1));

        $key = 'tool_sync/groups_purgeemptygroups';
        $label = get_string('grouppurgeempty', 'tool_sync');
        $opts = array(0 => get_string('purgenone', 'tool_sync'),
                      1 => get_string('purgegroups', 'tool_sync'),
                      2 => get_string('purgeall', 'tool_sync'));
        $frm->addElement('select', $key, $label, $opts, 0);

        $key = 'tool_sync/groups_mailadmins';
        $label = get_string('groupemailcourseadmins', 'tool_sync');
        $frm->addElement('advcheckbox', $key, $label, '', array('group' => 2), array(0, 1));

        $frm->addElement('static', 'groupsst1', '<hr>');

        $cronurl = new \moodle_url('/admin/tool/sync/groups/execcron.php', array('action' => SYNC_COURSE_GROUPS));
        $attribs = array('onclick' => 'document.location.href= \''.$cronurl.'\'');
        $frm->addElement('button', 'manualgroups', get_string('manualgrouprun', 'tool_sync'), $attribs);

        $cronurl = new \moodle_url('/admin/tool/sync/groups/execcron.php', array('action' => SYNC_GROUP_MEMBERS));
        $attribs = array('onclick' => 'document.location.href= \''.$cronurl.'\'');
        $frm->addElement('button', 'manualgroups', get_string('manualgroupmembersrun', 'tool_sync'), $attribs);

    }

    public function get_userfields() {
        return array('id' => 'id',
                     'idnumber' => get_string('idnumber'),
                     'username' => get_string('username'),
                     'email' => get_string('email'));
    }

    public function get_coursefields() {
        return array('id' => 'id',
                     'idnumber' => get_string('idnumber'),
                     'shortname' => get_string('shortname'));
    }

    public function get_groupfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'name' => get_string('name'));
    }

    public function get_groupingfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'name' => get_string('name'));
    }

    public function cron($syncconfig) {
        global $CFG, $DB;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            echo "Starting group cron ";
        }

        raise_memory_limit(MEMORY_HUGE);

        $component = 'tool_sync';

        $csvencode = '/\&\#44/';
        if (isset($syncconfig->csvseparator)) {
            $csvdelimiter = '\\' . $syncconfig->csvseparator;
            $csvdelimiter2 = $syncconfig->csvseparator;

            if (isset($CFG->CSV_ENCODE)) {
                $csvencode = '/\&\#' . $CFG->CSV_ENCODE . '/';
            }
        } else {
            $csvdelimiter = "\;";
            $csvdelimiter2 = ";";
        }

        if ($this->execute == SYNC_COURSE_GROUPS) {

            if ($CFG->debug == DEBUG_DEVELOPER) {
                echo "Starting course group processing ";
            }

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->groups_filelocation, 'groups.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            // We have no file to process. Probably because never setup.
            if (!($filereader = $this->open_input_file($filerec, 'group'))) {
                set_config('lastrunning_groups', null, 'tool_sync');
                return;
            }

            $required = array(
                    'courseid' => 1,
                    'name' => 1);
            $optional = array(
                    'type' => 1,
                    'idnumber' => 1,
                    'updateid' => 1,
                    'selfgrouping' => 1,
                    'grouping' => 1,
                    'description' => 1);
            $optionaldefaults = array(
                    'type' => 'group',
                    'idnumber' => '',
                    'selfgrouping' => 0,
                    'grouping' => false,
                    'description' => '');

            // Jump any empty or comment line.
            $text = tool_sync_read($filereader, 1024, $syncconfig);

            $i = 0;

            while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                $text = fgets($filereader, 1024);
                $i++;
            }

            $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
            $headers = explode($csvdelimiter2, $text);

            array_walk($headers, 'trim_array_values');

            foreach ($headers as $h) {
                $h = trim($h); // Remove whitespace.
                if (!(isset($required[$h]) or isset($optional[$h]))) {
                    $this->report(get_string('errorinvalidcolumnname', 'tool_sync', $h));
                    set_config('lastrunning_group', null, 'tool_sync');
                    return;
                }
                if (isset($required[$h])) {
                    $required[$h] = 0;
                }
            }

            foreach ($required as $key => $value) {
                if ($value) { // Required field missing.
                    $this->report(get_string('errorrequiredcolumn', 'tool_sync', $key));
                    set_config('lastrunning_group', null, 'tool_sync');
                    return;
                }
            }

            // Header is validated.
            $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

            $updatedgroups = array();
            $createdgroups = array();

            // Starting processing lines.
            $i = 2;
            while (!feof ($filereader)) {

                $this->report("# -- $i");

                $record = array();

                $text = tool_sync_read($filereader, 1024, $syncconfig);
                $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
                if (tool_sync_is_empty_line_or_format($text, false)) {
                    $i++;
                    continue;
                }
                $line = explode($csvdelimiter2, $text);

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo ">> Decoding \n";
                }
                foreach ($line as $key => $value) {
                    // Decode encoded commas.
                    $record[$headers[$key]] = trim($value);
                }

                // Set defaults.
                foreach ($optionaldefaults as $defaulkey => $defaultvalue) {
                    if (!array_key_exists($defaulkey, $record)) {
                        $record[$defaulkey] = $defaultvalue;
                    }
                }

                $e = new StdClass;
                $e->i = $i;

                $cidentifiername = @$syncconfig->groups_courseidentifier;

                $e->courseby = $cidentifiername;
                $e->mycourse = $record['courseid']; // Course identifier.

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo ">> Check course \n";
                }
                if (empty($record['courseid'])) {
                    $this->report(get_string('errornullcourseidentifier', 'tool_sync', $i));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                if (!$course = $DB->get_record('course', array($cidentifiername => $record['courseid'])) ) {
                    $this->report(get_string('errornocourse', 'tool_sync', $e));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo "Grouping...\n";
                }

                if (in_array('updateid', $headers)) {
                    // UPDATES.
                    $this->report('With explicit group or grouping id for Updates.');

                    if ($record['type'] == 'group') {
                        $gidentifiername = @$syncconfig->groups_groupidentifier;
                        $params = array($gidentifiername => $record['updateid']);
                        if ($oldrec = $DB->get_record('groups', $params)) {

                            if ($oldrec->courseid != $course->id) {
                                $this->report('course '.$course->id.' do not match with existing group for '.$record['name']);
                                continue;
                            }
                            $this->report("Updating group ");
                            $oldrec->name = $record['name'];
                            $oldrec->description = @$record['description'];
                            $oldrec->idnumber = @$record['idnumber'];
                            if (empty($syncconfig->simulate)) {
                                $DB->update_record('groups', $oldrec);
                                $this->report("group updated in DB ".print_r($oldrec, true));
                                $updatedgroups[$oldrec->courseid][] = $oldrec->name;
                                $group = $oldrec;
                                $this->report('Group "'.$group->name.'" updated in course '.$oldrec->courseid);
                            } else {
                                $this->report('SIMULATE: Group "'.$group->name.'" updated in course '.$oldrec->courseid);
                            }
                        } else {
                            $group = new StdClass;
                            $group->name = @$record['name'];
                            $group->courseid = @$record['courseid'];
                            $group->description = @$record['description'];
                            $group->idnumber = @$record['idnumber'];

                            if (!empty($record['grouping'])) {
                                $groupingid = $DB->get_field('groupings', 'id', array('idnumber' => $record['grouping']));
                                if ($groupingid) {
                                    $group->grouping = $groupingid;
                                }
                            }

                            if (empty($syncconfig->simulate)) {
                                $group->id = $DB->insert_record('groups', $group);
                                $createdgroups[$group->courseid][] = $group->name;
                                $this->report('Group "'.$group->name.'" created in course '.$group->courseid);
                            } else {
                                $this->report('SIMULATION: Group "'.$group->name.'" created in course '.$group->courseid);
                            }
                        }
                    } else {
                        // Groupings.
                        $gpidentifiername = @$syncconfig->groups_groupingidentifier;
                        $params = array($gpidentifiername => $record['updateid']);
                        if ($oldrec = $DB->get_record('groupings', $params)) {

                            if ($oldrec->courseid != $course->id) {
                                $this->report('Course '.$course->id.' do not match with existing grouping for '.$record['name']);
                                continue;
                            }
                            $this->report('Updating grouping');
                            if ($gpidentifiername != 'name') {
                                $oldrec->name = $record['name'];
                            }
                            $oldrec->description = @$record['description'];

                            if ($gpidentifiername != 'idnumber') {
                                $oldrec->idnumber = @$record['idnumber'];
                            }

                            if (empty($syncconfig->simulate)) {
                                $this->report('Updating grouping in DB');
                                $DB->update_record('groupings', $oldrec);
                                $this->report('Grouping "'.$oldrec->idnumber.'" updated in course '.$oldrec->courseid);
                                $updatedgroupings[$oldrec->courseid][] = $oldrec->name;
                                $grouping = $oldrec;
                            } else {
                                $this->report('SIMULATION: Grouping "'.$oldrec->idnumber.'" updated in course '.$oldrec->courseid);
                            }
                        } else {
                            $grouping = new StdClass;
                            $grouping->name = $record['name'];
                            $grouping->courseid = @$record['courseid'];
                            $grouping->description = @$record['description'];

                            if (empty($record['idnumber'])) {
                                $this->report('Grouping '.$grouping->name.' has no idnumber and will not be addressable in further group lines.');
                            }
                            $grouping->idnumber = @$record['idnumber'];

                            if (empty($syncconfig->simulate)) {
                                $grouping->id = $DB->insert_record('groupings', $grouping);
                                $createdgroupings[$course->id][] = $grouping->name;
                                $this->report('Grouping "'.$grouping->name.'" created in course '.$course->id);
                            } else {
                                $this->report('SIMULATION: Grouping "'.$grouping->name.'" created in course '.$course->id);
                            }
                        }
                        // Groupings terminate here.
                        continue;
                    }
                } else {
                    $this->report('without explicit groupid (addonly)');

                    if ($record['type'] == 'group') {
                        $this->report('Group type');
                        $group = new StdClass;
                        $group->name = @$record['name'];
                        $group->description = @$record['description'];
                        $group->descriptionformat = 0;
                        $group->idnumber = @$record['idnumber'];
                        $group->courseid = $course->id;
                        if (!empty($record['idnumber'])) {
                            $params = array('courseid' => $course->id, 'idnumber' => $record['idnumber']);
                        } else if (!empty($record['name'])) {
                            $params = array('courseid' => $course->id, 'name' => $record['name']);
                        }
                        $oldgroup = $DB->get_record('groups', $params);
                        if ($oldgroup) {
                            // Group exists, take old group.
                            $group = $oldgroup;
                        }

                        if (empty($group->id)) {
                            // Thus not old group.
                            if (empty($syncconfig->simulate)) {
                                $group->id = $DB->insert_record('groups', $group);
                                $createdgroups[$course->id][] = $group->name;
                                $this->report('Group '.$group->name.' created in course '.$group->courseid);
                            } else {
                                $this->report('SIMULATION: Group '.$group->name.' created in course '.$group->courseid);
                            }
                        }
                    } else {
                        $this->report('Grouping type');
                        $params = array('name' => $record['name'], 'courseid' => $course->id);
                        if (!$oldgrouping = $DB->get_record('groupings', $params)) {
                            $this->report('Making new');
                            $grouping = new StdClass;
                            $grouping->courseid = $course->id;
                            $grouping->name = $record['name'];
                            $grouping->idnumber = $record['idnumber'];
                            $grouping->timecreated = time();
                            $grouping->timemodified = time();
                            $grouping->description = $record['description'];
                            $grouping->descriptionformat = FORMAT_HTML;
                            $this->report('Saving');
                            if (empty($syncconfig->simulate)) {
                                $grouping->id = $DB->insert_record('groupings', $grouping);
                                $this->report('making new grouping '.$grouping->name.' in course '.$course->id);
                                $createdgroupings[$course->id][] = $grouping->name;
                            } else {
                                $this->report('SIMULATION: making new grouping '.$grouping->name.' in course '.$course->id);
                            }
                        }
                        // Groupings terminate here.
                        $this->report('Groupings done.');
                        continue;
                    }
                }

                // Process self grouping.

                if ($record['type'] == 'group') {

                    /*
                     * Process self grouping if required. Explicit grouping superseeds.
                     */

                    if (!empty($record['selfgrouping'])) {
                        $this->report('selfgrouping');
                        $params = array('name' => $group->name, 'courseid' => $group->courseid);
                        if (!$grouping = $DB->get_record('groupings', $params)) {
                            $grouping = new \StdClass;
                            $grouping->courseid = $course->id;
                            $grouping->name = $group->name;
                            $grouping->idnumber = $group->idnumber;
                            $grouping->timecreated = time();
                            $grouping->timemodified = time();
                            $grouping->description = $group->description;
                            $grouping->descriptionformat = $group->descriptionformat;
                            if (empty($syncconfig->simulate)) {
                                $grouping->id = $DB->insert_record('groupings', $grouping);
                                $this->report('Self-grouping group '.$group->name);
                            } else {
                                $this->report('SIMULATION: Self-grouping group '.$group->name);
                            }
                        }
                    }

                    // Check and bind group to grouping.
                    if (!empty($record['grouping'])) {
                        $grouping = $DB->get_record('groupings', array('idnumber' => $record['grouping'], 'courseid' => $course->id));
                        if ($grouping) {
                            $params = array('groupid' => $group->id, 'groupingid' => $grouping->id);
                            if (!$oldbinding = $DB->get_record('groupings_groups', $params)) {
                                $binding = new \StdClass;
                                $binding->groupingid = $grouping->id;
                                $binding->groupid = $group->id;
                                $binding->timeadded = time();
                                if (empty($syncconfig->simulate)) {
                                    $this->report('Binding group '.$group->name.' to grouping '.$grouping->id);
                                    $DB->insert_record('groupings_groups', $binding);
                                } else {
                                    $this->report('SIMULATION: Binding group '.$group->name.' to grouping '.$grouping->id);
                                }
                            }
                        } else {
                            $this->report('ERROR: Unkown grouping IDNumber '.$record['grouping']);
                        }
                    }
                }

                $i++;
            }
            // Invalidate the grouping cache for the course.
            \cache_helper::invalidate_by_definition('core', 'groupdata', array(), array());
            fclose($filereader);
        }

        if ($this->execute == SYNC_GROUP_MEMBERS) {

            if ($CFG->debug == DEBUG_DEVELOPER) {
                echo "Starting group members processing ";
            }

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->groupmembers_filelocation, 'groupmembers.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            // We have no file to process. Probably because never setup.
            if (!($filereader = $this->open_input_file($filerec, 'group'))) {
                set_config('lastrunning_groupmembers', null, 'tool_sync');
                return;
            }

            $required = array(
                    'groupid' => 1,
                    'userid' => 1);
            $optional = array(
                    'courseid' => 1,
                    'cmd' => 1);

            // Jump any empty or comment line.
            $text = tool_sync_read($filereader, 1024, $syncconfig);

            $i = 0;

            while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                $text = fgets($filereader, 1024);
                $i++;
            }

            $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
            $headers = explode($csvdelimiter2, $text);

            array_walk($headers, 'trim_array_values');

            foreach ($headers as $h) {
                if (!(isset($required[$h]) or isset($optional[$h]))) {
                    $this->report(get_string('errorinvalidcolumnname', 'tool_sync', $h));
                    set_config('lastrunning_group', null, 'tool_sync');
                    return;
                }
                if (isset($required[$h])) {
                    $required[$h] = 0;
                }
            }

            foreach ($required as $key => $value) {
                if ($value) { // Required field missing.
                    $this->report(get_string('errorrequiredcolumn', 'tool_sync', $key));
                    set_config('lastrunning_groupmembers', null, 'tool_sync');
                    return;
                }
            }

            // Header is validated.
            $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

            // Starting processing lines.
            $i = 2;
            while (!feof ($filereader)) {

                $this->report("# -- $i");

                $record = array();

                $text = tool_sync_read($filereader, 1024, $syncconfig);
                $text = preg_replace('/\n?\r?$/', '', $text); // Remove a trailing end line.
                if (tool_sync_is_empty_line_or_format($text, false)) {
                    $i++;
                    continue;
                }
                $line = explode($csvdelimiter2, $text);

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo ">> Decoding \n";
                }
                foreach ($line as $key => $value) {
                    // Decode encoded commas.
                    $record[$headers[$key]] = trim($value);
                }

                $e = new \StdClass;
                $e->i = $i;

                $cidentifiername = @$syncconfig->groups_courseidentifier;

                $e->courseby = $cidentifiername;
                $e->mycourse = $record['courseid']; // Course identifier.

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo ">> Check course \n";
                }

                if (empty($record['courseid'])) {
                    $this->report(get_string('errornullcourseidentifier', 'tool_sync', $i));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                if (!$course = $DB->get_record('course', array($cidentifiername => $record['courseid'])) ) {
                    $this->report(get_string('errornocourse', 'tool_sync', $e));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                $context = \context_course::instance($course->id);

                // Check group.

                $gidentifiername = @$syncconfig->groups_groupidentifier;

                $e->groupby = $gidentifiername;
                $e->mygroup = $record['groupid']; // Group identifier.

                if (empty($record['groupid'])) {
                    $this->report(get_string('errornullgroupidentifier', 'tool_sync', $i));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                $params = array($gidentifiername => $record['groupid']);
                if ($gidentifiername == 'name') {
                    // Ensure group identifier is fully qualified by course.
                    $params['courseid'] = $course->id;
                }
                if (!$group = $DB->get_record('groups', $params) ) {
                    $this->report(get_string('errornogroup', 'tool_sync', $e));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                // Check user.

                $uidentifiername = @$syncconfig->groups_useridentifier;

                $e->userby = $uidentifiername;
                $e->myuser = $record['userid']; // Group identifier.

                if (empty($record['userid'])) {
                    $this->report(get_string('errornulluseridentifier', 'tool_sync', $i));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                $params = array($uidentifiername => $record['userid']);
                if (!$user = $DB->get_record('user', $params) ) {
                    $this->report(get_string('errornouser', 'tool_sync', $e));
                    $i++;
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($text);
                    }
                    continue;
                }

                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo "Grouping people...\n";
                }

                if (empty($record['cmd'])) {
                    $record['cmd'] = 'add';
                }

                if ($record['cmd'] == 'shift') {
                    try {
                        if (empty($syncconfig->simulate)) {
                            tool_sync_delete_group_members($course->id, $user->id, $component);
                            $this->report(get_string('groupassigndeleted', 'tool_sync', $e));
                        } else {
                            $this->report('SIMULATION : '.get_string('groupassigndeleted', 'tool_sync', $e));
                        }
                    } catch (\Exception $e) {
                        $this->report("Global group shift exception on line $i");
                    }
                    $record['cmd'] = 'add';
                }

                if ($record['cmd'] == 'add') {
                    try {
                        if (count(get_user_roles($context, $user->id))) {
                            // Only people with roles in context can be grouped.
                            if (empty($syncconfig->simulate)) {
                                if (groups_add_member($group->id, $user->id, $component)) {
                                    $this->report(get_string('addedtogroup', 'tool_sync', $e));
                                } else {
                                    $this->report(get_string('addedtogroupnot', 'tool_sync', $e));
                                }
                            } else {
                                $this->report('SIMULATION : '.get_string('addedtogroup', 'tool_sync', $e));
                            }
                        } else {
                            $this->report(get_string('addedtogroupnotenrolled', '', $record['groupid']));
                        }
                    } catch (\Exception $e) {
                        $this->report("Global group gadd exception on line $i");
                    }
                }

                if ($record['cmd'] == 'del') {
                    // TODO : Remove membership.
                    if (empty($syncconfig->simulate)) {
                        $e = new \StdClass();
                        $e->group = $group->id;
                        $e->myuser = $user->username.' ('.$user->id.')';
                        $params = array('courseid' => $course->id, $gidentifiername => $record['groupid']);
                        if ($e->gid = $DB->get_field('groups', 'id', $params)) {
                            try {
                                tool_sync_group_remove_member($gid, $user->id, $component);
                                $this->report(get_string('removedfromgroup', 'tool_sync', $e));
                            } catch (\Exception $e) {
                                $this->report(get_string('removedfromgroupnot', 'tool_sync', $e));
                            }
                        } else {
                            $this->report(get_string('removedfromgroupnot', 'tool_sync', $e));
                        }
                    } else {
                        $e = new \StdClass();
                        $e->group = $record['groupid'];
                        $params = array('courseid' => $course->id, $gidentifiername => $record['groupid']);
                        if ($e->gid = $DB->get_field('groups', 'id', $params)) {
                            $this->report('SIMULATION: '.get_string('removedfromgroup', 'tool_sync', $e));
                        } else {
                            $this->report('SIMULATION: '.get_string('removedfromgroupnot', 'tool_sync', $e));
                        }
                    }
                }
                $i++;
            }

            if (!empty($syncconfig->groups_purgeemptygroups)) {
                // Process global empty groups and grouping purge.

                if ($syncconfig->groups_purgeemptygroups > 0) {
                    $sql = "
                        SELECT
                            g.id,
                            g.name
                        FROM
                            {groups} g
                        LEFT JOIN
                            {groups_members} gm
                        ON
                            gm.groupid = g.id
                        WHERE
                            gm.groupid IS NULL
                    ";
                    if ($emptygroups = $DB->get_records_sql($sql)) {

                        foreach ($emptygroups as $eg) {
                            if (empty($syncconfig->simulate)) {
                                groups_delete_group($eg->id);
                                $this->report(get_string('removeemptygroup', 'tool_sync', $eg));
                            } else {
                                $this->report('SIMULATION: '.get_string('removeemptygroup', 'tool_sync', $eg));
                            }
                        }
                    }
                }

                if ($syncconfig->groups_purgeemptygroups > 1) {
                    // Also process groupings.
                    $sql = "
                        SELECT
                            gr.id,
                            gr.name
                        FROM
                            {groupings} gr
                        LEFT JOIN
                            {grouping_groups} grb
                        ON
                            grb.groupingid = gr.id
                        WHERE
                            grb.groupid IS NULL
                    ";
                    if ($emptygroupings = $DB->get_records_sql($sql)) {

                        foreach ($emptygroupings as $egr) {
                            if (empty($syncconfig->simulate)) {
                                $this->report(get_string('removeemptygrouping', 'tool_sync', $eg));
                                groups_delete_grouping($egr->id);
                            } else {
                                $this->report('SIMULATION: '.get_string('removeemptygrouping', 'tool_sync', $eg));
                            }
                        }
                    }
                }
            }

            // Invalidate the grouping cache for the course.
            \cache_helper::invalidate_by_definition('core', 'groupdata', array(), array());
            fclose($filereader);
        }

        if ($CFG->debug == DEBUG_DEVELOPER) {
            mtrace("Finalization");
        }

        if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'storereport'))) {
            $this->store_report_file($filerec);
        }

        if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filefailed'))) {
            $this->write_tryback(clone($filerec));
        }

        if (empty($syncconfig->simulate)) {
            if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filearchive'))) {
                $this->archive_input_file(clone($filerec));
            }

            if ($DB->get_field('config_plugins', 'value', array('plugin' => 'tool_sync', 'name' => 'filecleanup'))) {
                $this->cleanup_input_file(clone($filerec));
            }
        }

        set_config('lastrunning_group', null, 'tool_sync');

        $this->report("\n".get_string('endofreport', 'tool_sync'));

        return true;
    }
}

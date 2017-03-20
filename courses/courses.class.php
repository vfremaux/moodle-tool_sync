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

namespace tool_sync;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/classes/sync_manager.class.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class course_sync_manager extends sync_manager {

    private $manualfilerec;

    private $identifieroptions;

    public $execute;

    public function __construct($execute = SYNC_COURSE_CREATE_DELETE, $manualfilerec = null) {
        $this->manualfilerec = $manualfilerec;
        $this->execute = $execute;
        $this->identifieroptions = array('idnumber' => 'idnumber', 'shortname' => 'shortname', 'id' => 'id');
    }

    public function form_elements(&$frm) {

        $key = 'tool_sync/courses_fileuploadlocation';
        $label = get_string('uploadcoursecreationfile', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/courses_fileuploadlocation', PARAM_TEXT);

        $key = 'tool_sync/courses_filedeletelocation';
        $label = get_string('coursedeletefile', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/courses_filedeletelocation', PARAM_TEXT);

        $key = 'tool_sync/courses_filedeleteidentifier';
        $label = get_string('deletefileidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->identifieroptions);

        $key = 'tool_sync/courses_fileexistlocation';
        $label = get_string('existcoursesfile', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/courses_fileexistlocation', PARAM_TEXT);

        $key = 'tool_sync/courses_existfileidentifier';
        $label = get_string('existfileidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->identifieroptions);

        $key = 'tool_sync/courses_fileresetlocation';
        $label = get_string('resetfile', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/courses_fileresetlocation', PARAM_TEXT);

        $key = 'tool_sync/courses_fileresetidentifier';
        $label = get_string('resetfileidentifier', 'tool_sync');
        $frm->addElement('select', $key, $label, $this->identifieroptions);

        $rarr = array();
        $rarr[] = $frm->createElement('radio', 'tool_sync/courses_forceupdate', '', get_string('yes'), 1);
        $rarr[] = $frm->createElement('radio', 'tool_sync/courses_forceupdate', '', get_string('no'), 0);
        $label = get_string('syncforcecourseupdate', 'tool_sync');
        $frm->addGroup($rarr, 'courses_updategroup', $label, array('&nbsp;&nbsp;&nbsp;&nbsp;'), false);

        $frm->addElement('static', 'coursesst1', '<hr>');

        $barr = array();
        $deletecreatorurl = new \moodle_url('/admin/tool/sync/courses/deletecourses_creator.php');
        $attribs = array('onclick' => 'document.location.href= \''.$deletecreatorurl.'\'');
        $barr[] = $frm->createElement('button', 'manualdeletecourses', get_string('makedeletefile', 'tool_sync'), $attribs);

        $resetcreatorurl = new \moodle_url('/admin/tool/sync/courses/resetcourses_creator.php');
        $attribs = array('onclick' => 'document.location.href= \''.$resetcreatorurl.'\'');
        $barr[] = $frm->createElement('button', 'manualcreatecourses', get_string('makeresetfile', 'tool_sync'), $attribs);

        $existurl = new \moodle_url('/admin/tool/sync/courses/checkcourses.php');
        $attribs = array('onclick' => 'document.location.href= \''.$existurl.'\'');
        $barr[] = $frm->createElement('button', 'manualcheckcourses', get_string('testcourseexist', 'tool_sync'), $attribs);

        $cleancaturl = new \moodle_url('/admin/tool/sync/courses/cleancategories.php');
        $attribs = array('onclick' => 'document.location.href= \''.$cleancaturl.'\'');
        $barr[] = $frm->createElement('button', 'manualcleancats', get_string('cleancategories', 'tool_sync'), $attribs);

        $frm->addGroup($barr, 'utilities', get_string('utilities', 'tool_sync'), array('&nbsp;&nbsp;'), false);

        $frm->addElement('static', 'coursesst2', '<hr>');

        $barr = array();
        $reseturl = new \moodle_url('/admin/tool/sync/courses/execcron.php', array('action' => SYNC_COURSE_RESET));
        $attribs = array('onclick' => 'document.location.href= \''.$reseturl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('reinitialisation', 'tool_sync'), $attribs);
        $createurl = new \moodle_url('/admin/tool/sync/courses/execcron.php', array('action' => SYNC_COURSE_CREATE));
        $attribs = array('onclick' => 'document.location.href= \''.$createurl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('manualuploadrun', 'tool_sync'), $attribs);
        $deleteurl = new \moodle_url('/admin/tool/sync/courses/execcron.php', array('action' => SYNC_COURSE_DELETE));
        $attribs = array('onclick' => 'document.location.href= \''.$deleteurl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('manualdeleterun', 'tool_sync'), $attribs);
        $clearcaturl = new \moodle_url('/admin/tool/sync/courses/clearemptycategories.php');
        $attribs = array('onclick' => 'document.location.href= \''.$clearcaturl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('manualcleancategories', 'tool_sync'), $attribs);
        $courseurl = new \moodle_url('/admin/tool/sync/courses/execcron.php');
        $attribs = array('onclick' => 'document.location.href= \''.$courseurl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('executecoursecronmanually', 'tool_sync'), $attribs);

        $frm->addGroup($barr, 'manualcourses', get_string('manualhandling', 'tool_sync'), array('&nbsp;&nbsp;'), false);
    }

    public function cron($syncconfig) {
        global $CFG, $DB;

        define('TOPIC_FIELD', '/^(topic)([0-9]|[1-4][0-9]|5[0-2])$/');
        define('TEACHER_FIELD', '/^(teacher)([1-9]+\d*)(_account|_role)$/');

        // Process files.

        $this->report('Starting...'. $this->execute);

        /* ****** Launching reset Files tool ****** */

        if ($this->execute & SYNC_COURSE_RESET) {

            $this->report(get_string('startingreset', 'tool_sync'));

            $text = '';

            // Get file rec to process depending on something has been provided for immediate processing.
            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->courses_fileresetlocation, 'resetcourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            $identifiername = $syncconfig->courses_fileresetidentifier;

            if ($filereader = $this->open_input_file($filerec)) {
                $required = array(
                        $identifiername => 1,
                        'events' => 1,
                        'logs' => 1,
                        'notes' => 1,
                        'grades' => 1,
                        'roles' => 1,
                        'local_roles' => 1,
                        'groups' => 1,
                        'groupings' => 1,
                        'modules' => 1);
                $optional = array(
                        'blog_associations' => 1,
                        'comments' => 1,
                        'completion' => 1,
                        'forum_all' => 1,
                        'forum_subscriptions' => 1,
                        'glossary_all' => 1, /*glossary*/
                        'chat' => 1, /*chat*/
                        'data' => 1, /*database*/
                        'slots' => 1, /*scheduler*/
                        'apointments' => 1,
                        'assignment_submissions' => 1, /*assignment*/
                        'assign_submissions' => 1, /*2.4 assignment*/
                        'survey_answers' => 1, /*survey*/
                        'lesson' => 1, /*lesson*/
                        'choice' => 1,
                        'scorm' => 1,
                        'quiz_attempts' => 1);

                if ($allmods = $DB->get_records('modules') ) {
                    foreach ($allmods as $mod) {
                        $modname = $mod->name;
                        $modfile = $CFG->dirroot."/mod/{$modname}/resetlib.php";
                        $modresetcourseformdefinition = $modname.'_reset_course_form_definition';
                        if (file_exists($modfile)) {
                            include_once($modfile);
                            if (function_exists($modresetcourseformdefinition)) {
                                $vars = $modresetcourseformdefinition();
                                foreach ($vars as $var) {
                                    $optional[$var] = 1;
                                }
                            }
                        }
                    }
                }

                $text = tool_sync_read($filereader, 1024, $syncconfig);

                $i = 0;

                // Skip comments and empty lines.
                while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                    $text = tool_sync_read($filereader, 1024, $syncconfig);
                    $i++;
                    continue;
                }
                $headers = explode($syncconfig->csvseparator, $text);

                foreach ($headers as $h) {
                    if (!isset($required[$h]) and !isset($optional[$h])) {
                        $this->report(get_string('invalidfieldname', 'error', $h));
                        return;
                    }
                    if (isset($required[$h])) {
                        $required[$h] = 0;
                    }
                }

                foreach ($required as $key => $value) {
                    if ($value) {
                        // Required field missing.
                        $this->report(get_string('fieldrequired', 'error', $key));
                        return;
                    }
                }

                while (!feof ($filereader)) {
                    $data = array();
                    $text = tool_sync_read($filereader, 1024, $syncconfig);

                    if (tool_sync_is_empty_line_or_format($text, false)) {
                        continue;
                    }

                    $values = explode($syncconfig->csvseparator, $text);
                    $record = array_combine($headers, $values);
                    $data['reset_start_date'] = 0;

                    // Adaptative identifier.
                    $identifiername = @$syncconfig->course_resetfileidentifier;
                    if (empty($identifiername)) {
                        echo "Setting default ";
                        $identifiername = 'shortname';
                    }

                    if (!array_key_exists($identifiername, $record)) {
                        $this->report(get_string('missingidentifier', 'tool_sync', $identifiername));
                        return;
                    }

                    if ($identifiername == 'idnumber' &&
                                $DB->count_records('course', array('idnumber' => $record['idnumber']))) {
                        $this->report(get_string('nonuniqueidentifierexception', 'tool_sync', $i));
                        continue;
                    }

                    if ($course = $DB->get_record('course', array($identifiername => $record[$identifiername])) ) {
                        $data['id'] = $course->id;
                        $data['reset_start_date_old'] = $course->startdate;
                    } else {
                        $this->report(get_string('unkownshortname', 'tool_sync', $i));
                        continue;
                    }
                    $this->report(get_string('resettingcourse', 'tool_sync').$course->fullname.' ('.$course->shortname.')');

                    // Processing events.
                    if ($record['events'] == 'yes') {
                        $data['reset_events'] = 1;
                    } else {
                        $this->report(get_string('noeventstoprocess', 'tool_sync', $i), false);
                    }

                    // Processing blog associations.
                    if (!array_key_exists('blog_associations', $record) || ($record['blog_associations'] == 'yes')) {
                        $data['delete_blog_associations'] = 1;
                    } else {
                        $this->report(get_string('noblogstoprocess', 'tool_sync', $i), false);
                    }

                    // Processing logs.
                    if ($record['logs'] == 'yes') {
                        $data['reset_logs'] = 1;
                    } else {
                        $this->report(get_string('nologstoprocess', 'tool_sync', $i), false);
                    }

                    // Processing notes.
                    if ($record['notes'] == 'yes') {
                        $data['reset_notes'] = 1;
                    } else {
                        $this->report(get_string('nonotestoprocess', 'tool_sync', $i), false);
                    }

                    // Processing comments.
                    if (!array_key_exists('comments', $record) || ($record['comments'] == 'yes')) {
                        $data['reset_comments'] = 1;
                    } else {
                        $this->report(get_string('nocommentstoprocess', 'tool_sync', $i), false);
                    }

                    // Processing local role assigns and overrides.
                    if ($record['local_roles'] == 'all') {
                        $data['reset_roles_local'] = 1;
                        $data['reset_roles_overrides'] = 1;
                    } else if ($record['local_roles'] == 'roles') {
                        $data['reset_roles_local'] = 1;
                    } else if ($record['local_roles'] == 'overrides') {
                        $data['reset_roles_overrides'] = 1;
                    } else {
                        $this->report(get_string('nolocalroletoprocess', 'tool_sync', $i), false);
                    }

                    // Processing grades.
                    if ($record['grades'] == 'all') {
                        $data['reset_gradebook_items'] = 1;
                        $data['reset_gradebook_grades'] = 1;
                    } else if ($record['grades'] == 'items') {
                        $data['reset_gradebook_items'] = 1;
                    } else if ($record['grades'] == 'grades') {
                        $data['reset_gradebook_grades'] = 1;
                    } else {
                        $this->report(get_string('nogradestoprocess', 'tool_sync', $i));
                    }

                    // Processing role assignations.
                    if ($record['roles'] == 'all') {
                        $roles = $DB->get_records('role');
                        foreach ($roles as $role) {
                            $data['unenrol_users'][] = $role->id;
                        }
                    } else {
                        $roles = explode(' ', $record['roles']);
                        $nbrole = 0;
                        foreach ($roles as $rolename) {
                            if ($role = $DB->get_record('role', array('shortname' => $rolename))) {
                                $data['unenrol_users'][$nbrole] = $role->id;
                                $nbrole++;
                            } else {
                                $this->report("[Error] role $rolename unkown.\n");
                            }
                        }
                    }

                    // Processing groups.
                    if ($record['groups'] == 'all') {
                        $data['reset_groups_remove'] = 1;
                        $data['reset_groups_members'] = 1;
                    } else if ($record['groups'] == 'groups') {
                        $data['reset_groups_remove'] = 1;
                    } else if ($record['groups'] == 'members') {
                        $data['reset_groups_members'] = 1;
                    } else {
                        $this->report(get_string('nogrouptoprocess', 'tool_sync', $i), false);
                    }

                    // Processing groupings.
                    if ($record['groupings'] == 'all') {
                        $data['reset_groupings_remove'] = 1;
                        $data['reset_groupings_members'] = 1;
                    } else if ($record['groupings'] == 'groups') {
                        $data['reset_groupings_remove'] = 1;
                    } else if ($record['groupings'] == 'members') {
                        $data['reset_groupings_members'] = 1;
                    } else {
                        $this->report(get_string('nogroupingtoprocess', 'tool_sync', $i), false);
                    }

                    echo '<br/>';

                    // Processing course modules.
                    if ($allmods = $DB->get_records('modules') ) {
                        $modmap = array();
                        $modlist = array();
                        $allmodsname = array();
                        foreach ($allmods as $mod) {
                            $modname = $mod->name;
                            $allmodsname[$modname] = 1;
                            if (!$DB->count_records($modname, array('course' => $data['id']))) {
                                // Skip mods with no instances.
                                continue;
                            }
                            $modlist[$modname] = 1;
                            $modfile = $CFG->dirroot."/mod/$modname/lib.php";
                            $modresetcourseformdefinition = $modname.'_reset_course_form_defaults';
                            $modresetuserdata = $modname.'_reset_userdata';
                            if (file_exists($modfile)) {
                                include_once($modfile);
                                if (function_exists($modresetcourseformdefinition)) {
                                    $modmap[$modname] = $modresetcourseformdefinition($data['id']);
                                }
                            } else {
                                debugging('Missing lib.php in '.$modname.' module');
                            }
                        }
                    }
                    $availablemods = array();
                    foreach ($modmap as $modname => $mod) {
                        foreach ($mod as $key => $value) {
                            $availablemods[$modname][$key] = $value;
                        }
                    }
                    if (count($headers) > 8) {
                        if ((isset($record['forum_all']))&&($record['forum_all'] == 1)) {
                            $data['reset_forum_all'] = 1;
                        }
                        if ((isset($record['forum_subscriptions'])) && ($record['forum_subscriptions'] == 1)) {
                            $data['reset_forum_subscriptions'] = 1;
                        }
                        if ((isset($record['glossary_all'])) && ($record['glossary_all'] == 1)) {
                            $data['reset_glossary_all'] = 1;
                        }
                        if ((isset($record['chat'])) && ($record['chat'] == 1)) {
                            $data['reset_chat'] = 1;
                        }
                        if ((isset($record['data'])) && ($record['data'] == 1)) {
                            $data['reset_data'] = 1;
                        }
                        if ((isset($record['slots']))&&($record['slots'] == 1)) {
                            $data['reset_slots'] = 1;
                        }
                        if ((isset($record['apointments'])) && ($record['apointments'] == 1)) {
                            $data['reset_apointments'] = 1;
                        }
                        if ((isset($record['assignment_submissions'])) && ($record['assignment_submissions'] == 1)) {
                            $data['reset_assignment_submissions'] = 1;
                        }
                        if ((isset($record['assign_submissions'])) && ($record['assign_submissions'] == 1)) {
                            $data['reset_assign_submissions'] = 1;
                        }
                        if ((isset($record['survey_answers'])) && ($record['survey_answers'] == 1)) {
                            $data['reset_survey_answers'] = 1;
                        }
                        if ((isset($record['lesson']))&&($record['lesson'] == 1)) {
                            $data['reset_lesson'] = 1;
                        }
                        if ((isset($record['choice']))&&($record['choice'] == 1)) {
                            $data['reset_choice'] = 1;
                        }
                        if ((isset($record['scorm'])) && ($record['scorm'] == 1)) {
                            $data['reset_scorm'] = 1;
                        }
                        if ((isset($record['quiz_attempts'])) && ($record['quiz_attempts'] == 1)) {
                            $data['reset_quiz_attempts'] = 1;
                        }
                    } else {
                        $mods = explode(' ', $record['modules']);
                        $modlist = array();
                        foreach ($mods as $mod) {
                            $modlist[$mod] = 1;
                        }
                        if (isset($modlist['all']) && ($modlist['all'] == 1)) {
                            foreach ($availablemods as $modname => $fcts) {
                                foreach ($fcts as $fct => $value) {
                                    $data[$fct] = $value;
                                }
                            }
                        } else {
                            if (!isset($modlist[''])) {
                                $neg = 0;
                                $negmods = array();
                                foreach ($modlist as $mymod => $value) {
                                    if ($mymod[0] == '-') {
                                        $neg = 1;
                                        $modnam = substr($mymod, 1);
                                        $negmods[$modnam] = 1;
                                    }
                                }
                                if ($neg == 1) {
                                    foreach ($availablemods as $modname => $fcts) {
                                        foreach ($fcts as $fct => $value) {
                                            $data[$fct] = $value;
                                        }
                                    }
                                    foreach (array_keys($negmods) as $k) {
                                        foreach ($availablemods as $mod => $fcts) {
                                            if ($k == $mod) {
                                                foreach ($fcts as $fct => $value) {
                                                    unset($data[$fct]);
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($neg == 0) {
                                    foreach ($availablemods as $modname => $fcts) {
                                        if (isset($modlist[$modname]) && ($modlist[$modname] == 1)) {
                                            foreach ($fcts as $fct => $value) {
                                                $data[$fct] = $value;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $data = (object)$data;

                    if (empty($syncconfig->simulate)) {
                        $status = reset_course_userdata($data);
                        $this->report("Summary:", false);
                    } else {
                        $this->report("SIMULATION Summary:", false);
                    }

                    foreach ($status as $line) {

                        $str = $line['component'] ." : ";
                        if (!empty($line['item'])) {
                            $str .= $line['item'] ." : ";
                        }
                        if (empty($line['error'])) {
                            $str .= 'OK';
                        } else {
                            $str .= $line['error'];
                        }
                        $this->report($str);
                    }
                }
            }
        }

        /* ****** Launching check Files tool ****** */

        if ($this->execute & SYNC_COURSE_CHECK) {

            $this->report(get_string('startingcheck', 'tool_sync'));

            $text = '';

            // Get file rec to process depending on somethoing has been provided for immediate processing.
            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->courses_fileexistlocation, 'courses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;

                $identifiername = @$syncconfig->courses_existfileidentifier;
                if (empty($identifiername)) {
                    $identifiername = 'shortname';
                }

                while (!feof($filereader)) {

                    $text = tool_sync_read($filereader, 1024, $syncconfig);

                    // Skip comments and empty lines.
                    if (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                        continue;
                    }

                    $valueset = explode($syncconfig->csvseparator, $text);

                    $size = count($valueset);

                    $c = new \StdClass();
                    $c->$identifiername = $valueset[0];
                    if ($size == 2) {
                        $c->description = $valueset[1];
                    }
                    $course = $DB->get_record('course', array($identifiername => $c->$identifiername));

                    // Give report on missing courses.
                    if (!$course) {
                        $this->report(get_string('coursenotfound2', 'tool_sync', $course));
                    } else {
                        $this->report(get_string('coursefoundas', 'tool_sync', $course));
                    }
                    $i++;
                }
                fclose($filereader);
            }
        }

        /* ******************** delete (clean) courses ********************** */

        if ($this->execute & SYNC_COURSE_DELETE) {

            $this->report(get_string('startingdelete', 'tool_sync'));

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->courses_filedeletelocation, 'deletecourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;

                while (!feof($filereader)) {
                    $text = tool_sync_read($filereader, 1024, $syncconfig);
                    // Skip comments and empty lines.
                    if (tool_sync_is_empty_line_or_format($text, $i)) {
                        continue;
                    }
                    $identifiers[] = $text;
                }

                // Fill this with a list of comma seperated id numbers to delete courses.
                $deleted = 0;
                $identifiername = @$syncconfig->courses_filedeleteidentifier;
                if (empty($identifiername)) {
                    $identifiername = 'shortname';
                }

                foreach ($identifiers as $cid) {
                    if (!($c = $DB->get_record('course', array($identifiername => $cid)))) {
                        $this->report(get_string('coursenotfound', 'tool_sync', $cid));
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($text);
                        }
                        $i++;
                        continue;
                    }

                    if (empty($syncconfig->simulate)) {
                        if (delete_course($c->id, false)) {
                            $deleted++;
                            $this->report(get_string('coursedeleted', 'tool_sync', $cid));
                        }
                    } else {
                        $this->report('SIMULATION : '.get_string('coursedeleted', 'tool_sync', $cid));
                    }
                }
                if ($deleted && empty($syncconfig->simulate)) {
                    fix_course_sortorder();
                }
                fclose($filereader);

                if (!empty($syncconfig->filefailed)) {
                    $this->write_tryback($filerec);
                }
                if (empty($syncconfig->simulate)) {
                    if (!empty($syncconfig->filearchive)) {
                        $this->archive_input_file($filerec);
                    }
                    if (!empty($syncconfig->filecleanup)) {
                        $this->cleanup_input_file($filerec);
                    }
                }
            }
        }

        /* *************** update/create courses *********************** */

        if ($this->execute & SYNC_COURSE_CREATE) {

            $this->report(get_string('startingcreate', 'tool_sync'));

            // Make arrays of fields for error checking.
            $defaultcategory = $this->get_default_category();
            $defaultmtime = time();

            $required = array(  'fullname' => false, // Mandatory fields.
                                'shortname' => false);

            $optional = array(  'category' => $defaultcategory, // Default values for optional fields.
                                'sortorder' => 0,
                                'summary' => get_string('coursedefaultsummary', 'tool_sync'),
                                'format' => 'topics',
                                'idnumber' => '',
                                'showgrades' => 1,
                                'newsitems' => 5,
                                'startdate' => $defaultmtime,
                                'marker' => 0,
                                'maxbytes' => 2097152,
                                'legacyfiles' => 0,
                                'showreports' => 0,
                                'visible' => 1,
                                'visibleold' => 0,
                                'groupmode' => 0,
                                'groupmodeforce' => 0,
                                'defaultgroupingid' => 0,
                                'lang' => '',
                                'theme' => '',
                                'timecreated' => $defaultmtime,
                                'timemodified' => $defaultmtime,
                                'self' => 0, // Special processing adding a self enrollment plugin instance.
                                'guest' => 0, // Special processing adding a guest enrollment plugin instance.
                                'template' => '');

            $manager = \core_plugin_manager::instance();
            $formats = $manager->get_plugins_of_type('format');
            $fnames = array();
            if (!empty($formats)) {
                foreach ($formats as $format) {
                    $fnames[] = $format->name;
                }
            }
            $installedformats = implode(',', $fnames);

            // TODO : change default format from weeks to course default options.
            global $validate;
            $validate = array(  'fullname' => array(1, 254, 1), // Validation information - see validate_as function.
                                'shortname' => array(1, 15, 1),
                                'category' => array(5),
                                'sortorder' => array(2, 4294967295, 0),
                                'summary' => array(1, 0, 0),
                                'format' => array(4, $installedformats),
                                'showgrades' => array(4, '0,1'),
                                'newsitems' => array(2, 10, 0),
                                'legacyfiles' => array(4, '0,1'),
                                'marker' => array(3),
                                'startdate' => array(3),
                                'maxbytes' => array(2, $CFG->maxbytes, 0),
                                'visible' => array(4, '0,1'),
                                'visibleold' => array(4, '0,1'),
                                'groupmode' => array(4, NOGROUPS.','.SEPARATEGROUPS.','.VISIBLEGROUPS),
                                'timecreated' => array(3),
                                'timemodified' => array(3),
                                'idnumber' => array(1, 100, 0),
                                'groupmodeforce' => array(4, '0,1'),
                                'lang' => array(1, 50, 0),
                                'theme' => array(1, 50, 0),
                                'showreports' => array(4, '0,1'),
                                'guest' => array(4, '0,1'),
                                'self' => array(4, '0,1'),
                                'template' => array(1, 0, 0),
                                'topic' => array(1, 0, 0),
                                'teacher_account' => array(6, 0),
                                'teacher_role' => array(1, 40, 0));

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file(@$syncconfig->courses_fileuploadlocation, 'uploadcourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;

                while (!feof($filereader)) {
                    $text = tool_sync_read($filereader, 1024, $syncconfig);
                    if (!tool_sync_is_empty_line_or_format($text, $i == 0)) {
                        break;
                    }
                    $i++;
                }

                $headers = explode($syncconfig->csvseparator, $text);

                // Check for valid field names.

                array_walk($headers, 'trim_array_values');

                foreach ($headers as $h) {

                    if (empty($h)) {
                        $this->report(get_string('errornullcsvheader', 'tool_sync'));
                        return;
                    }

                    if (!preg_match(TOPIC_FIELD, $h) && !preg_match(TEACHER_FIELD, $h)) {
                        if (!(isset($required[$h]) || isset($optional[$h]))) {
                            $this->report(get_string('errorinvalidfieldname', 'tool_sync', $h));
                            return;
                        }

                        if (isset($required[$h])) {
                            $required[$h] = true;
                        }
                    }
                }

                // Check for required fields.
                foreach ($required as $key => $value) {
                    if ($value != true) {
                        $this->report(get_string('fieldrequired', 'error', $key));
                        return;
                    }
                }

                // Header is validated.
                $this->init_tryback(implode($syncconfig->csvseparator, $headers));

                $fieldcount = count($headers);

                unset($bulkcourses);
                $courseteachers = array();

                // Start processing lines.

                while (!feof($filereader)) {
                    $text = tool_sync_read($filereader, 1024, $syncconfig);

                    if (tool_sync_is_empty_line_or_format($text)) {
                        $i++;
                        continue;
                    }

                    $valueset = explode($syncconfig->csvseparator, $text);

                    if (count($valueset) != $fieldcount) {
                        $e = new \StdClass();
                        $e->i = $i;
                        $e->count = count($valueset);
                        $e->expected = $fieldcount;
                        $this->report(get_string('errorbadcount', 'tool_sync', $e));
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($text);
                        }
                        $i++;
                        continue;
                    }

                    unset($coursetocreate);
                    unset($coursetopics);
                    unset($courseteachers);

                    // Set course array to defaults.
                    foreach ($optional as $key => $value) {
                        $coursetocreate[$key] = $value;
                    }

                    // prepare category
                    if (!is_numeric($coursetocreate['category'])) {
                        $coursetocreate['category'] = explode('/', $coursetocreate['category']);
                    }

                    $coursetopics = array();

                    // Validate incoming values.
                    foreach ($valueset as $key => $value) {
                        $cf = $headers[$key];

                        if (preg_match(TOPIC_FIELD, $cf, $matches)) {
                            // Register a topic definition.
                            $coursetopics[$matches[2]] = $this->validate_as($value, $matches[1], $i, $cf);
                        } else if (preg_match(TEACHER_FIELD, $cf, $matches)) {
                            // Register a teacher account request.
                            $tmp = $this->validate_as(trim($value), $matches[1].$matches[3], $i, $cf);
                            (isset($tmp) && ($tmp != '')) and ($courseteachers[$matches[2]][$matches[3]] = $tmp);
                        } else {
                            $coursetocreate[$cf] = $this->validate_as($value, $cf, $i); // Accept value if it passed validation.
                        }
                    }
                    $coursetocreate['topics'] = $coursetopics;

                    if (isset($courseteachers)) {
                        // Process teacher requests.
                        foreach ($courseteachers as $key => $value) { // Deep validate course teacher info on second pass.
                            if (isset($value) && (count($value) > 0)) {
                                if (!(isset($value['_account']) && $this->check_is_in($value['_account']))) {
                                    $e = new \StdClass();
                                    $e->i = $i;
                                    $e->key = $key;
                                    $this->report(get_string('errornoteacheraccountkey', 'tool_sync', $e));
                                    continue;
                                }
                                /*
                                 * Hardcoded default values (that are as close to moodle's UI as possible)
                                 * and we can't assume PHP5 so no pointers!
                                 */
                                if (!isset($value['_role'])) {
                                    $courseteachers[$key]['_role'] = '';
                                }
                            }
                        }
                    } else {
                        $courseteachers = array();
                    }
                    $coursetocreate['teachers_enrol'] = $courseteachers;
                    $bulkcourses["$i"] = $coursetocreate; // Merge into array.
                    $sourcetext["$i"] = $text; // Save text line for further reference.
                    $i++;
                }

                fclose($filereader);
            }

            if (empty($bulkcourses)) {
                $this->report(get_string('errornocourses', 'tool_sync'));
                return;
            }

            // All validation is over. Starting the course creation process.

            // Running Status Totals.

            $t = 0; // Read courses.
            $s = 0; // Skipped courses.
            $n = 0; // Created courses.
            $p = 0; // Broken courses (failed halfway through.

            $caterrors = 0; // Errored categories.
            $catcreated = 0; // Created categories.

            foreach ($bulkcourses as $i => $bulkcourse) {
                $a = new \StdClass;
                $a->shortname = $bulkcourse['shortname'];
                $a->fullname = $bulkcourse['fullname'];

                // Try to create the course.
                if (!$oldcourse = $DB->get_record('course', array('shortname' => $bulkcourse['shortname']))) {

                    $coursetocategory = 0; // Category ID.

                    if (is_array($bulkcourse['category'])) {
                        // Course Category creation routine as a category path was given.

                        $results = $this->make_category($bulkcourse['category'], $syncconfig, $sourcetext, $i,
                                                                 $catcreated, $caterrors);
                        // Last category created will contain the actual course.
                        $coursetocategory = $results[0];
                    } else {
                        // It's just a straight category ID.
                        $coursetocategory = (!empty($bulkcourse['category'])) ? $bulkcourse['category'] : -1;
                    }

                    if ($coursetocategory == -1) {
                        $e = new \StdClass;
                        $e->i = $i;
                        $e->coursename = $bulkcourse['shortname'];
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($sourcetext[$i]);
                        }
                        $this->report(get_string('errorcategoryparenterror', 'tool_sync', $e));
                        continue;
                    } else {
                        $result = $this->fast_create_course_ex($coursetocategory, $bulkcourse, $headers, $syncconfig);
                        $e = new \StdClass;
                        $e->coursename = $bulkcourse['shortname'];
                        $e->shortname = $bulkcourse['shortname'];
                        $e->fullname = $bulkcourse['fullname'];
                        $e->i = $i;
                        $e->error = $result;
                        switch ($result) {
                            case 1:
                                $this->report(get_string('coursecreated', 'tool_sync', $e));
                                $n++; // Succeeded.
                            break;
                            case -1:
                                $this->report(get_string('errorinputconditions', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -20:
                            case -21:
                            case -22:
                            case -23:
                            case -24:
                                $this->report(get_string('errorbackupfile', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -3:
                                $this->report(get_string('errorsectioncreate', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -4:
                                $this->report(get_string('errorteacherenrolincourse', 'tool_sync', $e));
                                if (!empty($CFG->sync_filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -5:
                                $this->report(get_string('errorteacherrolemissing', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -6:
                                $this->report(get_string('errorcoursemisconfiguration', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -7:
                                $e->template = $bulkcourse['template'];
                                $this->report(get_string('errortemplatenotfound', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -8:
                                $this->report(get_string('errorrestoringtemplatesql', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            default:
                                $this->report(get_string('errorrestoringtemplate', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                            break;
                        }
                    }
                } else {
                    if (!empty($syncconfig->courses_forceupdate)) {

                        $coursetocategory = 0;

                        if (is_array($bulkcourse['category'])) {
                            // Course Category creation routine as a category path was given.

                            $coursetocategory = $this->make_category($bulkcourse['category'], $syncconfig, $sourcetext,
                                                                     $i, $catcreated, $caterrors);
                            // Last category created will contain the actual course.
                        } else {
                            // It's just a straight category ID.
                            $coursetocategory = (!empty($bulkcourse['category'])) ? $bulkcourse['category'] : -1;
                        }

                        if ($coursetocategory == -1) {
                            $e = new \StdClass;
                            $e->i = $i;
                            $e->coursename = $oldcourse->shortname;
                            if (!empty($syncconfig->filefailed)) {
                                $this->feed_tryback($sourcetext[$i]);
                            }
                            $this->report(get_string('errorcategoryparenterror', 'tool_sync', $e));
                            continue;
                        } else {
                            $oldcourse->category = $coursetocategory;
                        }

                        foreach ($bulkcourse as $key => $value) {
                            if (isset($oldcourse->$key) && !in_array($key, array('id', 'category', 'self', 'guest'))) {
                                $oldcourse->$key = $value;
                            }
                        }
                        if ($DB->update_record('course', $oldcourse)) {
                            $e = new \StdClass;
                            $e->i = $i;
                            $e->shortname = $oldcourse->shortname;
                            $this->report(get_string('courseupdated', 'tool_sync', $e));
                        } else {
                            $e = new \StdClass;
                            $e->i = $i;
                            $e->shortname = $oldcourse->shortname;
                            $this->report(get_string('errorcourseupdated', 'tool_sync', $e));
                        }

                        $this->update_enrols($oldcourse, $bulkcourse['self'], $bulkcourse['guest']);
                    } else {
                        $this->report(get_string('courseexists', 'tool_sync', $a));
                          // Skip course, already exists.
                    }

                    $s++;
                }
                $t++;
            }

            fix_course_sortorder(); // Re-sort courses.

            if (!empty($syncconfig->storereport)) {
                $this->store_report_file($filerec);
            }

            if (!empty($syncconfig->filefailed)) {
                $this->write_tryback($filerec);
            }

            if (!empty($syncconfig->filearchive)) {
                $this->archive_input_file($filerec);
            }

            if (!empty($syncconfig->filecleanup)) {
                $this->cleanup_input_file($filerec);
            }
        }

        // F.e. for course reset operation.
        if (isset($satus)) {
            return $status;
        }

        return true;
    }

    protected function make_category($categories, $syncconfig, $sourcetext, $line, &$catcreated, &$caterrors) {
        $curparent = 0;
        $curstatus = 0;

        foreach ($categories as $catindex => $catname) {

            $curparent = $this->fast_get_category_ex($catname, $curstatus, $curparent, $syncconfig);

            switch ($curstatus) {
                case 1:
                    // Skipped the category, already exists.
                    break;
                case 2:
                    // Created a category.
                    $catcreated++;
                    break;
                default:
                    $caterrors += 1;
                    $coursetocategory = -1;
                    $e = new \StdClass;
                    $e->catname = $catname;
                    $e->failed = $caterrors;
                    $e->i = $line;
                    $this->report(get_string('errorcategorycreate', 'tool_sync', $e));
                    if (!empty($syncconfig->filefailed)) {
                        $this->feed_tryback($sourcetext[$line]);
                    }
                    break 2;
            }
        }
        (@$coursetocategory == -1) || $coursetocategory = $curparent;
        return array($coursetocategory, $catcreated, $caterrors);
    }

    /**
     *
     */
    public function get_default_category() {
        global $DB;

        if (!$mincat = $DB->get_field('course_categories', 'MIN(id)', array())) {
            return 1; // SHOULD be the Misc category?
        }
        return $mincat;
    }

    /**
     *
     */
    protected function check_is_in($supposedint) {
        return ((string)intval($supposedint) == $supposedint) ? true : false;
    }

    /**
     *
     */
    protected function check_is_string($supposedstring) {
        $supposedstring = trim($supposedstring); // Is it just spaces?
        return (strlen($supposedstring) == 0) ? false : true;
    }

    /**
     * Validates each field based on information in the $validate array
     */
    protected function validate_as($value, $validatename, $lineno, $fieldname = '') {
        global $CFG, $DB;
        global $validate;

        if (!isset($validate)) {
            // Default validation
            $validate = array(  'fullname' => array(1, 254, 1), // Validation information - see validate_as function.
                                'shortname' => array(1, 100, 1),
                                'category' => array(5),
                                'sortorder' => array(2, 4294967295, 0),
                                'summary' => array(1, 0, 0),
                                'format' => array(4, 'social,topics,weeks'),
                                'showgrades' => array(4, '0,1'),
                                'newsitems' => array(2, 10, 0),
                                'teacher' => array(1, 100, 1),
                                'teachers' => array(1, 100, 1),
                                'student' => array(1, 100, 1),
                                'students' => array(1, 100, 1),
                                'startdate' => array(3),
                                'numsections' => array(2, 52, 0),
                                'maxbytes' => array(2, $CFG->maxbytes, 0),
                                'visible' => array(4, '0,1'),
                                'groupmode' => array(4, NOGROUPS.','.SEPARATEGROUPS.','.VISIBLEGROUPS),
                                'timecreated' => array(3),
                                'timemodified' => array(3),
                                'idnumber' => array(1, 100, 0),
                                'password' => array(1, 50, 0),
                                'enrolperiod' => array(2, 4294967295, 0),
                                'groupmodeforce' => array(4, '0,1'),
                                'metacourse' => array(4, '0,1'),
                                'lang' => array(1, 50, 0),
                                'theme' => array(1, 50, 0),
                                'cost' => array(1, 10, 0),
                                'showreports' => array(4, '0,1'),
                                'guest' => array(4, '0,1'),
                                'self' => array(4, '0,1'),
                                'enrollable' => array(4, '0,1'),
                                'enrolstartdate' => array(3),
                                'enrolenddate' => array(3),
                                'notifystudents' => array(4, '0,1'),
                                'template' => array(1, 0, 0),
                                'expirynotify' => array(4, '0,1'),
                                'expirythreshold' => array(2, 30, 1), // Following ones cater for [something]N.
                                'topic' => array(1, 0, 0),
                                'teacher_account' => array(6, 0),
                                'teacher_role' => array(1, 40, 0));
        }

        if ($fieldname == '') {
            $fieldname = $validatename;
        }

        if (!isset($validate[$validatename])) {
            // We dont translate this > developper issue.
            $errormessage = 'Coding Error: Unvalidated field type: "'.$validatename.'"';
            $this->report($errormessage);
            return;
        }

        $format = $validate[$validatename];

        switch($format[0]) {
            case 1:
                // String.
                if (($format[1]) != 0) {
                    // Max length?
                    if (strlen($value) > $format[1]) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->length = $format[1];
                        $this->report(get_string('errorvalidationstringlength', 'tool_sync', $e));
                        return;
                    }
                }

                if ($format[2] == 1) {
                    // Not null?
                    if (!$this->check_is_string($value)) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationempty', 'tool_sync', $e));
                        return;
                    }
                }
                break;

            case 2:
                // Integer.
                if (!$this->check_is_in($value)) {
                    $e = new \StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationintegercheck', 'tool_sync', $e));
                    return;
                }

                if (($max = $format[1]) != 0) {
                    // Max value?
                    if ($value > $max) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->max = $max;
                        $this->report(get_string('errorvalidationintegerabove', 'tool_sync', $e));
                        return;
                    }
                }

                if (isset($format[2]) && !is_null($format[2])) {
                    // Min value.
                    $min = $format[2];
                    if ($value < $min) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->min = $min;
                        $this->report(get_string('errorvalidationintegerbeneath', 'tool_sync', $e));
                        return;
                    }
                }
                break;

            case 3:
                // Timestamp - validates and converts to Unix Time.
                $value = strtotime($value);
                if ($value == -1) {
                    // Failure.
                    $e = new \StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationtimecheck', 'tool_sync', $e));
                    return;
                }
                break;

            case 4:
                // Domain.
                $validvalues = explode(',', $format[1]);
                if (array_search($value, $validvalues) === false) {
                    $e = new \StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $e->set = $format[1];
                    $this->report(get_string('errorvalidationvalueset', 'tool_sync', $e));
                    return;
                }
                break;

            case 5:
                // Category.
                if ($this->check_is_in($value)) {
                    // It's a Category ID Number.
                    if (!$DB->record_exists('course_categories', array('id' => $value))) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->category = $value;
                        $this->report(get_string('errorvalidationcategoryid', 'tool_sync', $e));
                        return;
                    }
                } else if ($this->check_is_string($value)) {
                    // It's a Category Path string.
                    $value = trim(str_replace('\\', '/', $value), " \t\n\r\0\x0B/");
                    // Clean path, ensuring all slashes are forward ones.
                    if (strlen($value) <= 0) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationcategoryunpathed', 'tool_sync', $e));
                        return;
                    }

                    unset ($cats);
                    $cats = explode('/', $value); // Break up path into array.

                    if (count($cats) <= 0) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->path = $value;
                        $this->report(get_string('errorvalidationcategorybadpath', 'tool_sync', $e));
                        return;
                    }

                    foreach ($cats as $n => $item) {
                        // Validate the path.
                        $item = trim($item); // Remove outside whitespace.

                        if (strlen($item) > 100) {
                            $e = new \StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->item = $item;
                            $this->report(get_string('errorvalidationcategorylength', 'tool_sync', $e));
                            return;
                        }
                        if (!$this->check_is_string($item)) {
                            $e = new \StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->value = $value;
                            $e->pos = $n + 1;
                            $this->report(get_string('errorvalidationcategorytype', 'tool_sync', $e));
                            return;
                        }
                    }

                    $value = $cats; // Return the array.
                    unset ($cats);
                } else {
                    $e = new \StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationbadtype', 'tool_sync', $e));
                    return;
                }
                break;

            case 6:
                // User ID or Name (Search String).
                $value = trim($value);
                if ($this->check_is_in($value)) { // User ID.
                    if (!$DB->record_exists('user', array('id' => $value))) {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->value = $value;
                        $this->report(get_string('errorvalidationbaduserid', 'tool_sync', $e));
                        return;
                    }
                } else if ($this->check_is_string($value)) {
                    // User Search String.
                    // Only PHP5 supports named arguments.
                    $usersearch = get_users_listing('lastaccess', 'ASC', 0, 99999, $value, '', '');
                    if (isset($usersearch) &&
                                ($usersearch !== false) &&
                                        is_array($usersearch) &&
                                                (($ucountc = count($usersearch)) > 0)) {
                        if ($ucountc > 1) {
                            $e = new \StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->ucount = $ucountc;
                            $this->report(get_string('errorvalidationmultipleresults', 'tool_sync', $e));
                            return;
                        }

                        reset($usersearch);

                        $uid = key($usersearch);

                        if (!$this->check_is_in($uid) || !$DB->record_exists('user', array('id' => $uid))) {
                            $e = new \StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $this->report(get_string('errorvalidationsearchmisses', 'tool_sync', $e));
                            return;
                        }

                        $value = $uid; // Return found user id.

                    } else {
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationsearchfails', 'tool_sync', $e));
                        return;
                    }
                } else {
                    if ($format[1] == 1) {
                        // Not null?
                        $e = new \StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationempty', 'tool_sync', $e));
                        return;
                    }
                }
                break;

            default:
                // Not translated.
                $errormessage = 'Coding Error: Bad field validation type: "'.$fieldname.'"';
                $this->report($errormessage);
                return;
                break;
        }

        return $value;
    }

    protected function microtime_float() {
        // In case we don't have php5.
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Find category with the given name and parentID, or create it, in both cases returning a category ID.
     *
     *  Output status:
     *  -1 : Failed to create category
     *   1 : Existing category found
     *   2 : Created new category successfully
     */
    protected function fast_get_category_ex($hname, &$hstatus, $parentid = 0, $syncconfig = null) {
        global $DB;

        // Check if a category with the same name and parent ID already exists.
        if ($cat = $DB->get_field_select('course_categories', 'id', " name = ? AND parent = ? ", array($hname, $parentid))) {
            $hstatus = 1;
            return $cat;
        } else {
            if (!$parent = $DB->get_record('course_categories', array('id' => $parentid))) {
                $parent = new \StdClass;
                $parent->path = '';
                $parent->depth = 0;
                $parentid = 0;
            }

            $cat = new \StdClass;
            $cat->name = $hname;
            $cat->description = '';
            $cat->parent = $parentid;
            $cat->sortorder = 999;
            $cat->coursecount = 0;
            $cat->visible = 1;
            $cat->depth = $parent->depth + 1;
            $cat->timemodified = time();
            if (empty($syncconfig->simulate)) {
                if ($cat->id = $DB->insert_record('course_categories', $cat)) {
                    $hstatus = 2;

                    // Must post update.
                    $cat->path = $parent->path.'/'.$cat->id;
                    $DB->update_record('course_categories', $cat);

                    // We must make category context.
                    \context_helper::create_instances(CONTEXT_COURSECAT);
                } else {
                    return -1;
                }
            } else {
                return 999999; // Simulate a category id.
            }
            return $cat->id;
        }
    }

    /**
     * create a course.
     */
    protected function fast_create_course_ex($hcategoryid, $course, $headers, $syncconfig) {
        global $CFG, $DB, $USER;

        if (!is_array($course) || !is_array($headers)) {
            return -1;
        }

        // Trap when template not found.
        if (!empty($course['template'])) {
            if (!($tempcourse = $DB->get_record('course', array('shortname' => $course['template'])))) {
                return -7;
            }
        }

        $guest = $course['guest'];
        $self = $course['self'];
        unset($course['guest']);
        unset($course['self']);

        /*
         * Dynamically Create Query Based on number of headings excluding Teacher[1,2,...] and Topic[1,2,...]
         * Added for increased functionality with newer versions of moodle
         * Author: Ashley Gooding & Cole Spicer
         */

        $courserec = (object)$course;
        $courserec->category = $hcategoryid;
        unset($courserec->template);

        foreach ($headers as $i => $col) {
            $col = strtolower($col);
            if (preg_match(TOPIC_FIELD, $col) ||
                    preg_match(TEACHER_FIELD, $col) ||
                            $col == 'category' ||
                                    $col == 'guest' ||
                                            $col == 'self') {
                continue;
            }
            if ($col == 'expirythreshold') {
                $courserec->$col = $course[$col] * 86400;
            } else {
                $courserec->$col = $course[$col];
            }
        }

        if (!empty($course['template'])) {

            $result = $this->tool_sync_create_course_from_template($course, $syncconfig);
            if ($result < 0) {
                return $result;
            }

        } else {
            // Create default course.
            if (empty($syncconfig->simulate)) {
                $newcourse = create_course($courserec);

                $format = (!isset($course['format'])) ? 'topics' : $course['format']; // May be useless.
                if (isset($course['topics'])) {
                    // Any topic headings specified ?
                    $maxfilledtopics = 1;
                    foreach ($course['topics'] as $dtopicno => $dtopicname) {
                        if (!empty($dtopicname)) {
                            // We guess the max declared topic.
                            $maxfilledtopics = $dtopicno;
                        }
                        if (strstr($dtopicname, '|') === false) {
                            $sectionname = $dtopicname;
                            $sectionsummary = '';
                        } else {
                            list($sectionname, $sectionsummary) = explode('|', $dtopicname);
                        }

                        $params = array('section' => $dtopicno, 'course' => $newcourse->id);
                        if (!$sectiondata = $DB->get_record('course_sections', $params)) {
                            // Avoid overflowing topic headings.
                            $csection = new \StdClass;
                            $csection->course = $newcourse->id;
                            $csection->section = $dtopicno;
                            $csection->name = $sectionname;
                            $csection->summary = $sectionsummary;
                            $csection->sequence = '';
                            $csection->visible = 1;
                            $DB->insert_record('course_sections', $csection);
                        } else {
                            $sectiondata->summary = $sectionname;
                            $sectiondata->name = $sectionsummary;
                            $DB->update_record('course_sections', $sectiondata);
                        }
                    }
                    if (!isset($course['topics'][0])) {
                        if (!$DB->get_record('course_sections', array('section' => 0, 'course' => $newcourse->id))) {
                            $csection = new \StdClass;
                            $csection->course = $newcourse->id;
                            $csection->section = 0;
                            $csection->name = '';
                            $csection->summary = '';
                            $csection->sequence = '';
                            $csection->visible = 1;
                            if (!$DB->insert_record('course_sections', $csection)) {
                                return -3;
                            }
                        }
                    }

                    // Finally we can bind the course to have $maxfilledtopics topics.
                    $new = 0;
                    $params = array('courseid' => $newcourse->id, 'name' => 'numsections', 'format' => $format);
                    if (!$formatoptions = $DB->get_record('course_format_options', $params)) {
                        $formatoptions = new \StdClass();
                        $new = 1;
                    }
                    $formatoptions->courseid = $newcourse->id;
                    $formatoptions->format = $format;
                    $formatoptions->name = 'numsections';
                    $formatoptions->section = 0;
                    $formatoptions->value = $maxfilledtopics;
                    if ($new) {
                        $DB->insert_record('course_format_options', $formatoptions);
                    } else {
                        $DB->update_record('course_format_options', $formatoptions);
                    }
                } else {
                    $numsections = get_config('numsections', 'moodlecourse');
                    for ($i = 1; $i < $numsections; $i++) {
                        // Use course default to reshape the course creation.
                        $csection = new \StdClass;
                        $csection->course = $newcourse->id;
                        $csection->section = $i;
                        $csection->name = '';
                        $csection->summary = '';
                        $csection->sequence = '';
                        $csection->visible = 1;
                        $DB->insert_record('course_sections', $csection);
                    }
                }
                rebuild_course_cache($newcourse->id, true);

                if (!$context = \context_course::instance($newcourse->id)) {
                    return -6;
                }

                $this->update_enrols($newcourse, $self, $guest);

            } else {
                $newcourse = new \StdClass;
                $newcourse->shortname = 'SIMUL';
                $newcourse->fullname = 'Simulation';
                $newcourse->id = 999999;
                $this->report('SIMULATION : '.get_string('coursecreated', 'tool_sync'));
            }
        }

        if (isset($course['teachers_enrol']) && (count($course['teachers_enrol']) > 0)) {
            // Any teachers specified?
            foreach (array_values($course['teachers_enrol']) as $dteacherdata) {
                if (isset($dteacherdata['_account'])) {
                    $roleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
                    $roleassignrec = new \StdClass;
                    $roleassignrec->roleid = $roleid;
                    $roleassignrec->contextid = $context->id;
                    $roleassignrec->userid = $dteacherdata['_account'];
                    $roleassignrec->timemodified = $course['timecreated'];
                    $roleassignrec->modifierid = 0;
                    $roleassignrec->enrol = 'manual';
                    if (empty($syncconfig->simulate)) {
                        if (!$DB->insert_record('role_assignments', $roleassignrec)) {
                            return -4;
                        }
                        $e = new StdClass;
                        $e->rolename = 'Teacher';
                        $e->contextid = $context->id;
                        $this->report(get_string('roleassigned', 'tool_sync', $e));
                    }
                }
            }
        }
        return 1;
    }

    public function clear_empty_categories($ids) {
        global $DB;

        $count = 0;

        list($usec, $sec) = explode(' ', microtime());
        $timestart = ((float)$usec + (float)$sec);

        $idarr = explode(',', $ids);
        foreach ($idarr as $id) {
            $deletedcat = $DB->get_record('course_categories', array('id' => $id));
            if ($DB->delete_records('course_categories', array('id' => $id))) {
                if (delete_context(CONTEXT_COURSECAT, $id)) {
                    $this->report(get_string('categoryremoved', 'tool_sync', $deletedcat->name));
                    $count++;
                } else {
                    $this->report(get_string('errorcategorycontextdeletion', 'tool_sync', $id));
                }
            } else {
                $this->report(get_string('errorcategorydeletion', 'tool_sync', $id));
            }
        }

        $this->report(get_string('ncategoriesdeleted', 'tool_sync', $count));
        // Show execute time.
        list($usec, $sec) = explode(' ', microtime());
        $timeend = ((float)$usec + (float)$sec);
        $this->report(get_string('totaltime', 'tool_sync').' '.round(($timeend - $timestart), 2).' s');
    }

    /**
     * Creates a default reinitialisation file with standard options
     * File is generated in UTF8 only
     * @param array $selection array of course IDs from selection form
     * @param object $syncconfig
     */
    public function create_course_reinitialisation_file($selection, $syncconfig) {
        global $DB;

        $fs = get_file_storage();

        $filename = 'resetcourses.csv';

        $identifieroptions = array('idnumber', 'shortname', 'id');
        $identifiername = $identifieroptions[0 + @$syncconfig->course_resetfileidentifier];

        $rows = array();
        $cols = array('shortname', 'roles', 'local_roles', 'completion', 'grades', 'groups', 'groupings',
                      'blog_associations', 'events', 'logs', 'notes', 'comments', 'modules');
        if (!in_array($identifiername, $cols)) {
            $cols[] = $identifiername;
        }
        $rows[] = implode($syncconfig->csvseparator, $cols);

        for ($i = 0; $i < count($selection); $i++) {

            if (@$syncconfig->course_resetfileidentifier == 0 &&
                        ($DB->count_records('course', array('idnumber' => $selection[$i])) > 1)) {
                $this->report(get_string('nonuniqueidentifierexception', 'tool_sync', $i));
                continue;
            }

            $c = $DB->get_record('course', array($identifiername => $selection[$i]));
            $values = array();
            $values[] = $c->shortname;
            $values[] = 'student teacher guest';
            $values[] = 'all'; // All, roles, overrides.
            $values[] = 'yes';
            $values[] = 'all'; // All, grades, items.
            $values[] = 'all'; // All, members, remove.
            $values[] = 'all'; // All, members, remove.
            $values[] = 'yes'; // Blog associations.
            $values[] = 'yes'; // Events.
            $values[] = 'yes'; // Logs.
            $values[] = 'yes'; // Notes.
            $values[] = 'yes'; // Comments.
            $values[] = 'all';
            $rows[] = implode($syncconfig->csvseparator, $values);
        }
        $content = implode("\n", $rows); // We have at least one.

        $filerec = new \StdClass();
        $filerec->contextid = \context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = $filename;

        // Ensure no collisions.
        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                     $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        $fs->create_file_from_string($filerec, $content);
    }

    public function update_enrols($course, $self, $guest) {
        global $DB;

        if (!empty($guest)) {
            mtrace('Adding guest access to course '.$course->id."\n");
            if (!$enrolrec = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'guest'))) {
                $instance = array(
                    'status' => 0,
                );
                $enrol = enrol_get_plugin('guest');
                $enrol->add_instance($course, $instance);
            } else {
                $enrolrec->status = 0;
                $DB->update_record('enrol', $enrolrec);
            }
        } else {
            // Remove access.
            if ($enrolrec = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'guest'))) {
                $enrolrec->status = 1;
                $DB->update_record('enrol', $enrolrec);
            }
        }

        if (!empty($self)) {
            mtrace('Adding self access to course '.$course->id."\n");
            if (!$enrolrec = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'self'))) {
                $instance = array(
                    'status' => 0,
                );
                $enrol = enrol_get_plugin('self');
                $enrol->add_instance($course, $instance);
            } else {
                $enrolrec->status = 0;
                $DB->update_record('enrol', $enrolrec);
            }
        } else {
            // Remove access.
            if ($enrolrec = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'self'))) {
                $enrolrec->status = 1;
                $DB->update_record('enrol', $enrolrec);
            }
        }
    }

    public function create_course_from_template($course, $syncconfig) {
        global $DB, $CFG, $USER;

        $origincourse = $DB->get_record('course', array('shortname' => $course['template']));

        // Find the most suitable archive file.
        if (tool_sync_is_course_identifier($course['template'])) {
            // Template is NOT a real path and thus designates a course shortname.
            if (!$archive = tool_sync_locate_backup_file($origincourse->id, 'course')) {

                // Get course template from publishflow backups if publishflow installed.
                if ($DB->get_record('block', array('name' => 'publishflow'))) {
                    $archive = tool_sync_locate_backup_file($origincourse->id, 'publishflow');
                    if (!$archive) {
                        return -20;
                    }
                } else {
                    return -21;
                }
            }
        } else {
            if (!preg_match('/^\/|[a-zA-Z]\:/', $course['template'])) {
                /*
                 * If relative path we expect finding those files somewhere in the distribution.
                 * Not in dataroot that may be a fresh installed one).
                 */
                $course['template'] = $CFG->dirroot.'/'.$course['template'];
            }

            /*
             * Template is a real path. Integrate in a draft filearea of current user
             * (defaults to admin) and get an archive stored_file for it.
             */
            if (!file_exists($course['template'])) {
                return -22;
            }

            // Now create a draft file from this.
            $fs = get_file_storage();

            $contextid = \context_user::instance($USER->id)->id;

            $fs->delete_area_files($contextid, 'user', 'draft', 0);

            $filerec = new \StdClass;
            $filerec->contextid = $contextid;
            $filerec->component = 'user';
            $filerec->filearea = 'draft';
            $filerec->itemid = 0;
            $filerec->filepath = '/';
            $filerec->filename = basename($course['template']);
            $archive = $fs->create_file_from_pathname($filerec, $course['template']);
        }

        $this->report(get_string('creatingcoursefromarchive', 'tool_sync', $archive->get_filename()));

        $uniq = rand(1, 9999);

        $tempdir = $CFG->tempdir . '/backup/' . $uniq;
        if (!is_dir($tempdir)) {
            mkdir($tempdir, 0777, true);
        }
        // Unzip all content in temp dir.

        // Actually locally copying archive.
        $contextid = \context_system::instance()->id;

        require_once($CFG->dirroot.'/lib/filestorage/mbz_packer.php');

        if ($archive->extract_to_pathname(new \mbz_packer(), $tempdir)) {

            // Transaction.
            $transaction = $DB->start_delegated_transaction();

            // Create new course.
            $userdoingtherestore = $USER->id; // E.g. 2 == admin.
            $newcourseid = \restore_dbops::create_new_course('', '', $course['category']);

            /*
             * Restore backup into course.
             * folder needs being a relative path from $CFG->tempdir.'/backup/'.
             * @see /backup/util/helper/convert_helper.class.php function detect_moodle2_format
             */
            $controller = new \restore_controller($uniq, $newcourseid,
                    \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $userdoingtherestore,
                    \backup::TARGET_NEW_COURSE );
            $controller->execute_precheck();
            if (empty($syncconfig->simulate)) {
                $controller->execute_plan();
            }

            // Commit.
            $transaction->allow_commit();

            // And import.
            if ($newcourseid) {

                // Add all changes from incoming courserec.
                $newcourse = $DB->get_record('course', array('id' => $newcourseid));
                foreach ((array)$course as $field => $value) {
                    if (($field == 'format') || ($field == 'id')) {
                        continue; // Protect sensible identifying fields.
                    }
                    $newcourse->$field = $value;
                }
                if (empty($syncconfig->simulate)) {
                    try {
                        $DB->update_record('course', $newcourse);
                    } catch (Exception $e) {
                        mtrace('failed updating');
                    }
                }
                return $newcourseid;
            } else {
                return -23;
            }
        } else {
            return -24;
        }
    }

    public static function trimvalues(&$arr, $k) {
        $arr[$k] = trim($arr[$k]); // Remove whitespaces.
    }
}

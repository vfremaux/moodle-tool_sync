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
 * @author    Valery Fremaux
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function tool_sync_execute_bind($cmd, $enrol, $courseid, $cohortid, $roleid, $starttime = 0, $endtime = 0, $makegroup = 0, $extra1 = 0, $extra2 = 0) {
    global $DB;

    $report = '';

    switch ($cmd) {
        case 'add': {
            $params = array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohortid, 'roleid' => $roleid);
            if (!$oldrec = $DB->get_record('enrol', $params)) {
                $enrolobj = new StdClass;
                $enrolobj->enrol = $enrol;
                $enrolobj->status = 0;
                $enrolobj->courseid = $courseid;
                $enrolobj->enrolstartdate = $starttime;
                $enrolobj->enrolenddate = $endtime;
                $enrolobj->roleid = $roleid;
                $enrolobj->customint1 = $cohortid;
                $enrolobj->customint2 = $makegroup;
                $enrolobj->customint3 = $extra1;
                $enrolobj->customint4 = $extra2;
                $DB->insert_record('enrol', $enrolobj);
            } else {
                if ($oldrec->status == 1) {
                    $oldrec->status = 0;
                    $enrol->enrolstartdate = time();
                    $DB->update_record('enrol', $oldrec);
                }
            }
            $e = new StdClass;
            $e->course = $courseid;
            $e->cohort = $cohortid;
            $e->enrol = $enrol;
            $e->role = $DB->get_field('role', 'shortname', array('id' => $roleid));
            $report .= get_string('cohortbindingadded', 'tool_sync', $e);
            break;
        }

        case 'del':
        case 'restore': {
            if ($roleid != '*') {
                $params = array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohortid, 'roleid' => $roleid);
            } else {
                $params = array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohortid);
            }
            if ($oldrecs = $DB->get_records('enrol', $params)) {
                foreach ($oldrecs as $oldrec) {
                    // Disable all enrols of any role on this cohort.
                    $oldrec->status = ($cmd == 'del') ? 1 : 0;
                    $DB->update_record('enrol', $oldrec);

                    $e = new StdClass;
                    $e->course = $courseid;
                    $e->cohort = $cohortid;
                    $e->enrol = $enrol;
                    $e->role = $DB->get_field('role', 'shortname', array('id' => $oldrec->roleid));
                    $report .= get_string('cohortbindingdisabled', 'tool_sync', $e);
                }
            }
            break;
        }

        case 'fulldel': {
            if ($roleid != '*') {
                $params = array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohortid, 'roleid' => $roleid);
            } else {
                $params = array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohortid);
            }
            if ($oldrecs = $DB->get_records('enrol', $params)) {
                foreach ($oldrecs as $oldrec) {
                    $DB->delete_records('enrol', array('id' => $oldrec->id));

                    $e = new StdClass;
                    $e->course = $courseid;
                    $e->cohort = $cohortid;
                    $e->enrol = $enrol;
                    $e->role = $DB->get_field('role', 'shortname', array('id' => $oldrec->roleid));
                    $report .= get_string('cohortbindingdeleted', 'tool_sync', $e);
                }
            }
            break;
        }

        default:
    }

    return $report;
}
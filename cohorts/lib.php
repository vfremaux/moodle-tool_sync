<?php

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
                $enrolobj->enrolstartdate = $timestart;
                $enrolobj->enrolenddate = $timeend;
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
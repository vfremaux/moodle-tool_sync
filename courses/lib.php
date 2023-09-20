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
defined('MOODLE_INTERNAL') || die();

if (!function_exists('debug_trace')) {
    @include_once($CFG->dirroot.'/local/advancedperfs/debugtools.php');
    if (!function_exists('debug_trace')) {
        function debug_trace($msg, $tracelevel = 0, $label = '', $backtracelevel = 1) {
            // Fake this function if not existing in the target moodle environment.
            assert(1);
        }
        define('TRACE_ERRORS', 1); // Errors should be always traced when trace is on.
        define('TRACE_NOTICE', 3); // Notices are important notices in normal execution.
        define('TRACE_DEBUG', 5); // Debug are debug time notices that should be burried in debug_fine level when debug is ok.
        define('TRACE_DATA', 8); // Data level is when requiring to see data structures content.
        define('TRACE_DEBUG_FINE', 10); // Debug fine are control points we want to keep when code is refactored and debug needs to be reactivated.
    }
}

/**
 * An helper function to create the course deletion file from a selection
 */
function tool_sync_create_course_deletion_file($selection) {

    $filename = 'deletecourses.txt';

    $fs = get_file_storage();
    $content = '';

    $size = count($selection);
    for ($i = 0; $i < $size - 1; $i++) {
        $content .= "$selection[$i]";
        $content .= "\n";
    }
    $size = $size - 1;
    $content .= "$selection[$size]";

    $filerec = new StdClass();
    $filerec->contextid = context_system::instance()->id;
    $filerec->component = 'tool_sync';
    $filerec->filearea = 'syncfiles';
    $filerec->itemid = 0;
    $filerec->filepath = '/';
    $filerec->filename = $filename;

    // Ensure no collisions.
    if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea,
                                 $filerec->itemid, $filerec->filepath, $filerec->filename)) {
        $oldfile->delete();
    }

    $fs->create_file_from_string($filerec, $content);
}

/**
 * Scans for and display the list of empty course categories (recusively)
 * @param int $parentcatid parent category
 * @param arrayref $scannedids accumulator of scanned categories
 * @param arrayref $path the relative textual path to the current category
 */
function tool_sync_scan_empty_categories($parentcatid, &$scannedids, &$path) {
    global $DB;

    // Get my subs.
    $sql = "
        SELECT DISTINCT
            cc.id,
            cc.parent,
            cc.name,
            count(c.id) as courses
        FROM
            {course_categories} cc
        LEFT JOIN
            {course} c
        ON
            cc.id = c.category
        WHERE
            cc.parent = ?
        GROUP BY
            cc.id
    ";
    $cats = $DB->get_records_sql($sql, array($parentcatid));
    if ($parentcatid != 0) {
        $countcourses = $DB->count_records('course', array('category' => $parentcatid));
    } else {
        $countcourses = 0;
    }

    if (!empty($cats)) {
        foreach ($cats as $ec) {

            $mempath = $path;
            $path .= ' / '.$ec->name;
            $subcountcourses = tool_sync_scan_empty_categories($ec->id, $scannedids, $path);
            $path = $mempath;

            if ($subcountcourses == 0) {
                // This is a really empty cat.
                echo "<tr><td align=\"left\"><b>{$ec->name}</b></td><td align=\"left\">$path</td></tr>";
                $scannedids[] = $ec->id;
            }
            $countcourses += $subcountcourses;
        }
    }
    return $countcourses;
}

/**
 * checks locally if a deployable/publishable backup is available
 * @param int $courseid the courseid where to locate a backup
 * @param string $filearea the filearea to consider
 * @return false or a stored_file object
 */
function tool_sync_locate_backup_file($courseid, $filearea) {

    $fs = get_file_storage();

    $coursecontext = context_course::instance($courseid);
    $files = $fs->get_area_files($coursecontext->id, 'backup', $filearea, 0, 'timecreated DESC', false);

    if (count($files) > 0) {
        return array_shift($files);
    }

    return false;
}

/**
 * completes unqualified key names
 * @param object $cfg a configuration object.
 */
function tool_sync_config_add_sync_prefix($cfg) {

    $formobj = new StdClass();

    foreach ($cfg as $key => $value) {
        $fullkey = 'tool_sync/'.$key;
        $formobj->$fullkey = $value;
    }

    return $formobj;
}

/**
 * Checks if the token is a path to an archive (.mbz)
 * If not, should be s course shortname.
 * @param $str string to check
 * @return true is a shortname, false elsewhere
 */
function tool_sync_is_course_identifier($str) {
    return (!preg_match('/\.mbz/', $str));
}

function tool_sync_get_empty_categories($catid, $ignoresubs, &$hascontent) {
    global $DB;

    $cat = $DB->get_record('course_categories', array('id' => $catid));
    $emptycats = array();

    $hascontent = false;
    if ($catid > 0) {
        if ($DB->record_exists('course', array('category' => $catid))) {
            // Really not empty, thus parent is not empty anyway.
            $hascontent = true;
        }
    }
    if ($childs = $DB->get_records('course_categories', array('parent' => $catid), 'id,id')) {
        $childshavecontent = false;
        foreach ($childs as $child) {
            $emptycats = array_merge($emptycats, tool_sync_get_empty_categories($child->id, $ignoresubs, $childhascontent));
            if ($childhascontent) {
                $childshavecontent = true;
            }
        }

        if (!$ignoresubs || ($childshavecontent == true)) {
            $hascontent = true;
        }
    }

    if (($catid > 0) && !$hascontent) {
        $emptycats[] = $cat;
    }

    return $emptycats;
}

function tool_sync_erase_empty_categories($catid, $ignoresubs, &$hascontent) {
    global $DB;

    $str = '';

    $cat = $DB->get_record('course_categories', array('id' => $catid));

    $hascontent = false;
    if ($catid > 0) {
        if ($DB->record_exists('course', array('category' => $catid))) {
            // Really not empty, thus parent is not empty anyway.
            $hascontent = true;
        }
    }
    if ($childs = $DB->get_records('course_categories', array('parent' => $catid), 'id,id')) {
        $childshavecontent = false;
        foreach ($childs as $child) {
            $str .= tool_sync_erase_empty_categories($child->id, $ignoresubs, $childhascontent);
            if ($childhascontent) {
                $childshavecontent = true;
            }
        }

        if (!$ignoresubs || $childshavecontent == true) {
            $hascontent = true;
        }
    }

    if (($catid > 0) && !$hascontent) {
        $str .= get_string('coursecatdeleted', 'tool_sync', $cat->name)."\n";
        $catobj = tool_sync_get_category($catid);
        $catobj->delete_full();
    }

    return $str;
}


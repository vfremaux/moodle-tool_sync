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
 * An helper function to create the course deletion file from a selection
 */
function tool_sync_create_course_deletion_file($selection) {
    global $CFG;

    $filename = 'deletecourses.txt';

    $fs = get_file_storage();
    $content = '';

    $size = count($selection);
    for ($i = 0 ; $i < $size - 1 ; $i++) {
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

    // Ensure no collisions
    if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename)) {
        $oldfile->delete();
    }

    $fs->create_file_from_string($filerec, $content);
}

function tool_sync_scan_empty_categories($parentcatid, &$scannedids, &$path) {
    global $CFG, $DB;

    // get my subs
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
        foreach($cats as $ec) {

            $mempath = $path;
            $path .= ' / '.$ec->name;
            $subcountcourses = tool_sync_scan_empty_categories($ec->id, $scannedids, $path);
            $path = $mempath;

            if ($subcountcourses == 0) {
                // this is a really empty cat
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
 * @param reference $loopback variable given to setup an XMLRPC loopback message for testing
 * @return boolean
 */
function tool_sync_locate_backup_file($courseid, $filearea) {

    $fs = get_file_storage();

    $coursecontext = context_course::instance($courseid);
    $files = $fs->get_area_files($coursecontext->id, 'backup', $filearea, 0, 'timecreated', false);

    if (count($files) > 0) {
        return array_pop($files);
    }

    return false;
}

function tool_sync_config_add_sync_prefix($cfg){

    $formobj = new StdClass();
    
    foreach($cfg as $key => $value){
        $fullkey = 'tool_sync/'.$key;
        $formobj->$fullkey = $value;
    }

    return $formobj;
}

/**
 * Reads a line in a stream converting to utf8 if necessary
 * @param resource $filereader the opened stream
 * @param int $length max length of read
 * @param objectref $config the surrounding configuration
 * @return a string or false if no more data
 */
function tool_sync_read($filereader, $length, &$config){
    $input = fgets($filereader, 1024);

    if ($config->encoding != 'UTF-8') {
        return utf8_encode($input);
    }
    return $input;
}

/**
 * Checks if the token is a path to an archive (.mbz)
 * If not, should be s course shortname.
 * @return true is a shortname, false elsewhere
 */
function tool_sync_is_course_identifier($str) {
    return (!preg_match('/\.mbz/', $str));
}

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
 * @package tool_sync
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * A generaml library for userpictures operations.
 */

if (!defined('MOODLE_INTERNAL')) die('You cannot use this script this way');

/**
 * Create a unique temporary directory with a given prefix name,
 * inside a given directory, with given permissions. Return the
 * full path to the newly created temp directory.
 *
 * @param string $dir where to create the temp directory.
 * @param string $prefix prefix for the temp directory name (default '')
 *
 * @return string The full path to the temp directory.
 */
function sync_my_mktempdir($dir, $prefix = '') {
    global $CFG;

    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (file_exists($path));

    check_dir_exists($path);

    return $path;
}

/**
 * Try to save the given file (specified by its full path) as the
 * picture for the user with the given id.
 *
 * @param integer $id the internal id of the user to assign the picture file to.
 * @param string $originalfile the full path of the picture file.
 *
 * @return bool
 */
function sync_my_save_profile_image($id, $originalfile) {
    $context = context_user::instance($id);
    return process_new_icon($context, 'user', 'icon', 0, $originalfile);
}

/**
 * Stores a md5 checksum of the user picture in a user customized field
 * to help diff update.
 *
 * @param integer $id the internal id of the user to assign the picture file to.
 * @param string $directfile if empty, fetches existing or stored file into user's context, if provided, is the full path of the picture file.
 *
 * @return void
 */
function sync_register_image_checksum($id, $directfile) {
    global $DB;

    $field = $DB->get_record('user_info_field', array('shortname' => 'userpicturehash'));
    if (empty($field)) {
        return;
    }

    $checksum = '';

    if (file_exists($directfile)) {
        if ($FILE = fopen($directfile, 'rb')) {
            $buff = fread($FILE, filesize($directfile));
            $checksum = md5($buff);
            fclose($FILE);
        } else {
            return;
        }
    }

    if (!empty($checksum)) {
        if ($oldrec = $DB->get_record('user_info_data', array('userid' => $id, 'fieldid' => $field->id))) {
            $oldrec->data = $checksum;
            $DB->update_record('user_info_data', $oldrec);
        } else {
            $newrec = new StdClass;
            $newrec->fieldid = $field->id;
            $newrec->userid = $id;
            $newrec->data = $checksum;
            $newrec->dataformat = 0;
            $DB->insert_record('user_info_data', $newrec);
        }
    }
}

/**
 * Register/update all available user pictures
 * @param bool $verbose if true outputs some reporting to output
 */
function update_all_user_picture_hashes($verbose) {
    global $DB;

    $fs = get_file_storage();

    $allfilerecs = $DB->get_records('files', array('component' => 'user', 'filearea' => 'icon', 'filename' => 'f1.png'));

    if ($allfilerecs) {
        foreach ($allfilerecs as $filerec) {
            $context = $DB->get_record('context', array('id' => $filerec->contextid));
            if ($verbose) {
                $user = $DB->get_record('user', array('id' => $context->instanceid));
                mtrace('Generating hash for '.fullname($user));
            }
            sync_register_image_checksum($context->instanceid, null);
        }
    } else {
        if ($verbose) {
            mtrace('No user files to process');
        }
    }
}
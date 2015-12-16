<?php

/**
 * A local library revamped from 
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @see /admin/tool/uploaduser/picture.php 
 *
 * The essential reasons of the revamping are : 
 * - getting a real separate library for functions (original functions embedded in page script) 
 * - changing notifications to message logging output
 */

/**
 * Create a unique temporary directory with a given prefix name,
 * inside a given directory, with given permissions. Return the
 * full path to the newly created temp directory.
 *
 * @param string $dir where to create the temp directory.
 * @param string $prefix prefix for the temp directory name (default '')
 * @param string $mode permissions for the temp directory (default 700)
 *
 * @return string The full path to the temp directory.
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
 * @param integer $id the internal id of the user to assign the
 *                picture file to.
 * @param string $originalfile the full path of the picture file.
 *
 * @return bool
 */
function sync_my_save_profile_image($id, $originalfile) {
    $context = context_user::instance($id);
    return process_new_icon($context, 'user', 'icon', 0, $originalfile);
}

/**
 * Stores a md5 checksum of the user picture in a suer customized field
 * to help diff update.
 *
 * @param integer $id the internal id of the user to assign the
 *                picture file to.
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

    $context = context_user::instance($id);
    $checksum = '';

    if (empty($directfile)) {
        $fs = get_file_storage();
        $filerec = $DB->get_records('file', array('contextid'=> $context->id, 'component' => 'user', 'filearea' => 'icon', 'filename' => 'f1.png'));
        if ($filerec) {
            $icon = $fs->get_file_by_id($filerec->id);
            $checksum = md5($icon->get_content());
        }
    } else {
        if (file_exists($originalfile)) {
            $checksum = md5(implode('', file($originalfile)));
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
 *
 */
function update_all_user_picture_hashes($verbose) {
    global $DB;

    $fs = get_file_storage();

    $allfilerecs = $DB->get_records('file', array('component' => 'user', 'filearea' => 'icon', 'filename' => 'f1.png'));

    if ($allfilerecs) {
        foreach ($allfilerecs as $filerec) {
            if ($verbose) {
                $user = $DB->get_record('user', array('id' => $filerec->userid));
                mtrace('Generating hash for '.fullname($user));
            }
            sync_register_image_checksum($id, null);
        }
    }
}
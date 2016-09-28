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
require_once($CFG->dirroot.'/admin/tool/sync/userpictures/lib.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->dirroot.'/admin/tool/sync/sync_manager.class.php');

define('PIX_FILE_UPDATED', 0);
define('PIX_FILE_ERROR', 1);
define('PIX_FILE_SKIPPED', 2);

class userpictures_sync_manager extends sync_manager {

    public function form_elements(&$frm) {
        global $CFG;

        $frm->addElement('text', 'tool_sync/userpictures_fileprefix', get_string('userpicturesfilesprefix', 'tool_sync'));
        $frm->setType('tool_sync/userpictures_fileprefix', PARAM_TEXT);

        $label = get_string('existfileidentifier', 'tool_sync');
        $frm->addElement('select', 'tool_sync/userpictures_userfield', $label, $this->get_userfields());

        $rarr1 = array();
        $rarr1[] = $frm->createElement('radio', 'tool_sync/userpictures_overwrite', '', get_string('yes').' ', 1);
        $rarr1[] = $frm->createElement('radio', 'tool_sync/userpictures_overwrite', '', get_string('no'), 0);
        $frm->addGroup($rarr1, 'overwritearray', get_string('userpicturesoverwrite', 'tool_sync'), array(' '), false);

        $rarr2 = array();
        $rarr2[] = $frm->createElement('radio', 'tool_sync/userpictures_forcedeletion', '', get_string('yes').' ', 1);
        $rarr2[] = $frm->createElement('radio', 'tool_sync/userpictures_forcedeletion', '', get_string('no'), 0);
        $frm->addGroup($rarr2, 'forcedeletearray', get_string('userpicturesforcedeletion', 'tool_sync'), array(' '), false);

        $frm->addElement('static', 'userpicturesst1', '<hr>');

        $barr = array();
        $cronurl = new moodle_url('/admin/tool/sync/userpictures/execcron.php');
        $attribs = array('onclick' => 'document.location.href= \''.$cronurl.'\'');
        $frm->addElement('button', 'manualuserpictures', get_string('manualuserpicturesrun', 'tool_sync'), $attribs);
        $registerurl = new moodle_url('/admin/tool/sync/courses/execcron.php', array('what' => 'registerallpictures'));
        $attribs = array('onclick' => 'document.location.href= \''.$registerurl.'\'');
        $barr[] = $frm->createElement('button', 'manualusers', get_string('executecoursecronmanually', 'tool_sync'), $attribs);

        $frm->addGroup($barr, 'manualcourses', get_string('manualhandling', 'tool_sync'), array('&nbsp;&nbsp;'), false);
    }

    public function cron($syncconfig) {
        global $USER, $CFG;

        $fs = get_file_storage();

        $filerec = new \StdClass();
        $contextid = \context_system::instance()->id;
        $component = 'tool_sync';
        $filearea = 'syncfiles';
        $itemid = 0;
        $areafiles = $fs->get_area_files($contextid, $component, $filearea, $itemid);

        // Searching in area what matches userpicture archives.
        if (!empty($areafiles)) {
            foreach ($areafiles as $f) {
                if (preg_match('/^'.$syncconfig->userpictures_fileprefix.'.*\.zip/', $f->get_filename())) {
                    $filestoprocess[] = $f;
                }
            }
        }

        if (empty($filestoprocess)) {
            $this->report(get_string('nofiletoprocess', 'tool_sync'));
            return;
        }

        $userfields = $this->get_userfields();

        $userfield = $syncconfig->userpictures_userfield;
        $overwritepicture = $syncconfig->userpictures_overwrite;

        if (!array_key_exists($userfield, $userfields)) {
            $this->report(get_string('uploadpicture_baduserfield', 'admin'));
            return;
        }

        foreach ($filestoprocess as $f) {

            $this->report(get_string('processingfile', 'tool_sync', $f->get_filename()));
            // User pictures processing.

            /*
             * Large files are likely to take their time and memory. Let PHP know
             * that we'll take longer, and that the process should be recycled soon
             * to free up memory.
             */
            @set_time_limit(0);
            @raise_memory_limit("512M");
            if (function_exists('apache_child_terminate')) {
                @apache_child_terminate();
            }

            // Create a unique temporary directory, to process the zip file contents.
            $zipdir = sync_my_mktempdir($CFG->tempdir.'/', 'usrpic');

            $fp = get_file_packer('application/zip');
            $unzipresult = $f->extract_to_pathname($fp, $zipdir, null);
            if (!$unzipresult) {
                $this->report(get_string('erroruploadpicturescannotunzip', 'tool_sync', $f));
                @remove_dir($zipdir);
            } else {
                $results = array ('errors' => 0, 'updated' => 0);

                $this->process_directory($zipdir, $userfield, $overwritepicture, $results);

                // Finally remove the temporary directory with all the user images and print some stats.
                remove_dir($zipdir);
                $this->report(get_string('usersupdated', 'tool_sync') . ": " . $results['updated']);
                $this->report(get_string('errors', 'tool_sync') . ": " . $results['errors']);
            }

            // Files cleanup.

            $filerec = new \StdClass();
            $filerec->contextid = $f->get_contextid();
            $filerec->component = $f->get_component();
            $filerec->filearea = $f->get_filearea();
            $filerec->itemid = $f->get_itemid();
            $filerec->filepath = $f->get_filepath();
            $filerec->filename = $f->get_filename();

            if (!empty($syncconfig->filearchive)) {
                $this->archive_input_file($filerec);
            }

            if (!empty($syncconfig->filecleanup) || !empty($syncconfig->userfiles_forcedeletion)) {
                $this->cleanup_input_file($filerec);
            }
        }

        $this->report("\n".get_string('endofreport', 'tool_sync'));

        return true;
    }

    public function get_userfields() {

        $ufs = array (
            'id' => 'id',
            'idnumber' => 'idnumber',
            'username' => 'username',
            'hostedusername' => 'username@mnethostid',
            'email' => 'email' );

        return $ufs;
    }

    /**
     * Recursively process a directory, picking regular files and feeding
     * them to process_file().
     *
     * @param string $dir the full path of the directory to process
     * @param string $userfield the prefix_user table field to use to
     *               match picture files to users.
     * @param bool $overwrite overwrite existing picture or not.
     * @param array $results (by reference) accumulated statistics of
     *              users updated and errors.
     *
     * @return nothing
     */
    protected function process_directory ($dir, $userfield, $overwrite, &$results) {
        global $OUTPUT, $CFG;

        if (!($handle = opendir($dir))) {
            $this->report(get_string('uploadpicture_cannotprocessdir', 'tool_uploaduser'));
            return;
        }

        while (false !== ($item = readdir($handle))) {
            if ($item != '.' && $item != '..') {
                if (is_dir($dir.'/'.$item)) {
                    $this->process_directory($dir.'/'.$item, $userfield, $overwrite, $results);
                } else if (is_file($dir.'/'.$item)) {
                    $result = $this->process_file($dir.'/'.$item, $userfield, $overwrite);
                    switch ($result) {
                        case PIX_FILE_ERROR:
                            $results['errors']++;
                            break;
                        case PIX_FILE_UPDATED:
                            $results['updated']++;
                            break;
                    }
                }
                /*
                 * Ignore anything else that is not a directory or a file (e.g.,
                 * symbolic links, sockets, pipes, etc.)
                 */
            }
        }
        closedir($handle);
    }


    /**
     * Given the full path of a file, try to find the user the file
     * corresponds to and assign him/her this file as his/her picture.
     * Make extensive checks to make sure we don't open any security holes
     * and report back any success/error.
     *
     * @param string $file the full path of the file to process
     * @param string $userfield the prefix_user table field to use to
     *               match picture files to users.
     * @param bool $overwrite overwrite existing picture or not.
     *
     * @return integer either PIX_FILE_UPDATED, PIX_FILE_ERROR or
     *                  PIX_FILE_SKIPPED
     */
    protected function process_file ($file, $userfield, $overwrite) {
        global $DB, $OUTPUT, $CFG;

        /*
         * Add additional checks on the filenames, as they are user
         * controlled and we don't want to open any security holes.
         */
        $parts = pathinfo(cleardoubleslashes($file));
        $basename  = $parts['basename'];
        $extension = $parts['extension'];

        /*
         * The picture file name (without extension) must match the
         * userfield attribute.
         */
        $uservalue = substr($basename, 0, strlen($basename) - strlen($extension) - 1);

        // Userfield names are safe, so don't quote them.

        if ($userfield == 'hostedusername') {
            list($username, $mnethostid) = explode('@', $uservalue);
            $user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $mnethostid, 'deleted' => 0));
        } else {
            $params = array($userfield => $uservalue, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0);
            $user = $DB->get_record('user', $params);
        }

        if (!$user) {
            $a = new \StdClass();
            $a->userfield = clean_param($userfield, PARAM_CLEANHTML);
            $a->uservalue = clean_param($uservalue, PARAM_CLEANHTML);
            $this->report(get_string('uploadpicture_usernotfound', 'tool_uploaduser', $a));
            return PIX_FILE_ERROR;
        }

        $haspicture = $DB->get_field('user', 'picture', array('id' => $user->id));
        if ($haspicture && !$overwrite) {
            $this->report(get_string('uploadpicture_userskipped', 'tool_uploaduser', $user->username));
            return PIX_FILE_SKIPPED;
        }

        if (sync_my_save_profile_image($user->id, $file)) {
            $DB->set_field('user', 'picture', 1, array('id' => $user->id));
            $this->report(get_string('uploadpicture_userupdated', 'tool_uploaduser', $user->username));
            return PIX_FILE_UPDATED;
        } else {
            $this->report(get_string('uploadpicture_cannotsave', 'tool_uploaduser', $user->username));
            return PIX_FILE_ERROR;
        }
    }
}
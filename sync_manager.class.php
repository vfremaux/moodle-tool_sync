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

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot use this script this way!');
}

class sync_manager {

    public $log;

    // preserves processed file header to rebuild tryback file
    private $trybackhead;

    // keeps tryback lines
    private $trybackarr;

    protected function report($message, $screen = true) {
        if (empty($this->log)) {
            $this->log = '';
        }
        if ($screen) {
            mtrace($message);
        }
        $this->log .= $message."\n";
    }

    /**
     * Initiates tryback buffer
     */
    protected function init_tryback($headline) {
        $this->trybackhead = $headline;
        $this->tryback = '';
    }

    protected function feed_tryback($line) {
        $this->tryback[] = $line;
    }

    public function write_tryback($originalfilerec) {

        if (empty($this->tryback)) {
            return;
        }

        $fs = get_file_storage();

        $path_parts = pathinfo($originalname);
        $trybackfilename = $path_parts['filename'].'_tryback_'.date('Ymd-Hi').'.'.$path_parts['extension'];

        $buffer = $this->trybackhead."\n";
        $buffer .= implode("\n", $this->tryback);

        $filerec = $originalfilerec;
        $filerec->filename = $trybackfilename;

        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        $fs->create_file_from_string($filerec, $buffer);
    }
    
    protected function get_input_file($configlocation, $defaultlocation) {
        if (empty($configlocation)) {
            $filename = $defaultlocation;  // Default location
            $filepath = '/';  // Default name
        } else {
            $parts = pathinfo($configlocation);
            $filename = $parts['basename'];
            $filepath = $parts['dirname'];
            // Ensures starts and ends with slashes.
            $filepath = preg_replace('#//#', '/', '/'.$filepath.'/');
        }

        $filerec = new StdClass();
        $filerec->contextid = context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = $filepath;
        $filerec->filename = $filename;

        return $filerec;
    }

    /**
     * Given a file rec, get an open strem on it and process error cases.
     *
     *
     */
    protected function open_input_file($filerec) {

        $fs = get_file_storage();

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename);
        if (!$inputfile) {
            $this->report(get_string('filenotfound', 'tool_sync', "{$filerec->filepath}{$filerec->filename}"));
            return false;
        } else {
            $filereader = $inputfile->get_content_file_handle();
            return $filereader;
        }
    }

    /**
     * Given an input file as stored file record, store an archived file.
     *
     */
    protected function archive_input_file($filerec) {

        $fs = get_file_storage();

        $now = date('Ymd-hi', time());
        $archiverec = clone($filerec);
        $archiverec->filename = $now.'_cohorts_'.$filerec->filename;
        $archiverec->filepath = '/archives/';

        // Ensure no collisions.
        if ($oldfile = $fs->get_file($archiverec->contextid, $archiverec->component, $archiverec->filearea, 
                                        $archiverec->itemid, $archiverec->filepath, $archiverec->filename)) {
            $oldfile->delete();
        }

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename);
        $fs->create_file_from_storedfile($archiverec, $inputfile);
    }
    
    protected function cleanup_input_file($filerec) {
        $fs = get_file_storage();

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename);
        $inputfile->delete();
    }
}
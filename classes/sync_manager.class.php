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
namespace tool_sync;

defined('MOODLE_INTERNAL') || die;

class sync_manager {

    public $log;

    // Preserves processed file header to rebuild tryback file.
    private $trybackhead;

    // Keeps tryback lines.
    private $trybackarr;

    /**
     * Adds a report message into the log buffer.
     */
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
     * Given an input file as stored file record, store an archived file.
     *
     */
    protected function store_report_file($filerec) {

        $fs = get_file_storage();

        $now = date('Ymd-Hi', time());
        $reportrec = clone($filerec);
        $reportrec->filename = $now.'_report_'.$filerec->filename;
        $reportrec->filepath = '/reports/';

        // Ensure no collisions.
        if ($oldfile = $fs->get_file($reportrec->contextid, $reportrec->component, $reportrec->filearea,
                                    $reportrec->itemid, $reportrec->filepath, $reportrec->filename)) {
            $oldfile->delete();
        }

        if (!empty($this->log)) {
            mtrace("Storing report");
            $fs->create_file_from_string($reportrec, $this->log);
        }
    }

    /**
     * Initiates tryback buffer and adds the first headline.
     * @param array $headlines and array of headers lines that were in the original file.
     */
    protected function init_tryback($headlines) {
        $this->trybackhead = $headlines;
        $this->trybackarr = array();
    }

    /**
     * Feeds a single line into the tryback buffer.
     * @param string $line a control file line being processed.
     * @return void
     */
    protected function feed_tryback($line) {
        $this->trybackarr[] = $line;
    }

    /**
     * Writes the tryback buffer in a file.
     * @param string $originalfilerec the original file record being processed.
     */
    public function write_tryback($originalfilerec) {

        $config = get_config('tool_sync');

        if (empty($this->trybackarr)) {
            return;
        }

        $fs = get_file_storage();

        $parts = pathinfo($originalfilerec->filename);
        $trybackfilename = $parts['filename'].'_tryback_'.date('Ymd-Hi').'.'.$parts['extension'];

        $buffer = implode($config->csvseparator, $this->trybackhead)."\n";
        $buffer .= implode("\n", $this->trybackarr);

        $filerec = $originalfilerec;
        $filerec->filename = $trybackfilename;

        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                     $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        echo "Creating tryback\n";
        $fs->create_file_from_string($filerec, $buffer);
    }

    /**
     * Provides a full filled moodle file descriptor of a command file
     * @param string $configfilelocation
     * @param string $defaultlocation
     * @return a file record.
     */
    protected function get_input_file($configlocation, $defaultlocation) {
        global $CFG;

        if (empty($configlocation)) {
            $filename = $defaultlocation;  // Default location.
            $filepath = '/';  // Default name.
        } else {
            if (preg_match('#(http|ftp)s?://#', $configlocation)) {
                if (tool_sync_supports_feature('fileloading/remote')) {
                    // This is a remotely stored exposed file on the web. First retreive it.
                    require_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
                    tool_sync_get_remote_file($configlocation);
                } else {
                    print_error('notsupported', 'tool_sync', 'fileloading/remote');
                }
            } else {
                // This is an existing file in our local tool sync filearea.
                $parts = pathinfo($configlocation);
                $filename = $parts['basename'];
                $filepath = $parts['dirname'];
                // Ensures starts and ends with slashes.
                $filepath = preg_replace('#//#', '/', '/'.$filepath.'/');
            }
        }

        if (!$this->filename_has_wildcard($filename)) {
            $filerec = new \StdClass();
            $filerec->contextid = \context_system::instance()->id;
            $filerec->component = 'tool_sync';
            $filerec->filearea = 'syncfiles';
            $filerec->itemid = 0;
            $filerec->filepath = $filepath;
            $filerec->filename = $filename;
            return $filerec;
        } else {
            if (tool_sync_supports_feature('fileloading/wildcard')) {
                // This is a remotely stored exposed file on the web. First retreive it.
                require_once($CFG->dirroot.'/admin/tool/sync/pro/lib.php');
                return tool_sync_get_first_availablefile($filerec);
            } else {
                print_error('notsupported', 'tool_sync', 'fileloading/wildcard');
            }
        }

    }

    /*
     * Checks if 
     */
    protected function filename_has_wildcard($filename) {
        return preg_match('/\\*/', $filename);
    }

    /**
     * Given a file rec, get an open strem on it and process error cases.
     */
    protected function open_input_file($filerec) {

        if (!$filerec) {
            return false;
        }

        $fs = get_file_storage();

        if ($filerec->filepath == '/./') {
            $filerec->filepath = '/';
        }

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                   $filerec->filepath, $filerec->filename);
        if (!$inputfile) {
            $this->report(get_string('filenotfound', 'tool_sync', "{$filerec->filepath}{$filerec->filename}"));
            return false;
        } else {
            ini_set('auto_detect_line_endings', true);
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

        $now = date('Ymd-Hi', time());
        $archiverec = clone($filerec);
        $archiverec->filename = $now.'_'.$filerec->filename;
        $archiverec->filepath = '/archives/';

        // Ensure no collisions.
        if ($oldfile = $fs->get_file($archiverec->contextid, $archiverec->component, $archiverec->filearea,
                                        $archiverec->itemid, $archiverec->filepath, $archiverec->filename)) {
            $oldfile->delete();
        }

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                   $filerec->filepath, $filerec->filename);
        echo "Archive input file\n";
        $fs->create_file_from_storedfile($archiverec, $inputfile);
    }

    /**
     *
     */
    protected function cleanup_input_file($filerec) {
        $fs = get_file_storage();

        $inputfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                                   $filerec->filepath, $filerec->filename);
        echo "Cleaning out input file...";
        $inputfile->delete();
        echo " cleaned.\n";
    }

    protected function check_headers($headers, $required, $patterns, $metas, $optional, $optionaldefaults) {

        // Check for valid field names.
        foreach ($headers as $h) {
            $header[] = trim($h);
            $patternized = implode('|', $patterns) . "\\d+";
            $metapattern = implode('|', $metas);
            if (!(isset($required[$h]) ||
                    isset($optionaldefaults[$h]) ||
                            isset($optional[$h]) ||
                                    preg_match("/$patternized/", $h) ||
                                            preg_match("/$metapattern/", $h))) {
                $this->report(get_string('invalidfieldname', 'error', $h));
                return false;
            }

            if (isset($required[$h])) {
                $required[$h] = 0;
            }
        }

        // Check for required fields.
        foreach ($required as $key => $value) {
            if ($value) {
                // Required field missing.
                $this->report(get_string('fieldrequired', 'error', $key));
                return false;
            }
        }

        return true;
    }
}
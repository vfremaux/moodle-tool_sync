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
 * @author Funck Thibaut
 */

defined('MOODLE_INTERNAL') || die;

class file_checker {

    /**
     * operates format transforms on incoming course definition file
     * @param string $filename
     */
    function transform_checkcourses_file($filename) {
        global $CFG;

        $name = $CFG->dataroot.'/'.$filename;
        $i = 0;
        $tmp = '';
        if ($file = fopen($name,'r')) {
            while (!feof($file)) {
                $tmp = fgets($file);
                $i++;
            }
        }

        $this->setEncoding($filename);
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
    }

    /**
     * operates format transforms on incoming enrol definition file
     * @param string $filename
     */
    function transform_enrol_file($filename) {
        global $CFG;

        $name = $CFG->dataroot.'/'.$filename;
        $i = 0;
        $tmp = '';
        if ($file = fopen($name, 'r')) {
            while (!feof($file)) {
                $tmp = fgets($file);
                $i++;
            }
        }

        $this->setEncoding($filename);
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
    }

    /**
     * operates format transforms on incoming user definition file
     * @param string $filename
     */
    function transform_users_file($filename) {
        global $CFG;

        $name = $CFG->dataroot.'/'.$filename;

        $i = 0;
        $tmp = '';
        if ($file = fopen($name,'r')) {
            while (!feof($file)) {
                $tmp = fgets($file);
                $i++;
            }
        }

        $this->setEncoding($filename);

        $this->deleteLine($filename,2);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
        $this->deleteLine($filename,$i);
        $i--;
    }

    /**
     *
     *
     */
    function setEncoding($filename) {
        global $CFG;

        $filename = $CFG->dataroot.'/'.$filename;

        if (file_exists($filename) ) {
            $csv_encode = '/\&\#44/';
            if (isset($CFG->CSV_DELIMITER)) {
                $csv_delimiter = '\\' . $CFG->CSV_DELIMITER;
                $csv_delimiter2 = $CFG->CSV_DELIMITER;

                if (isset($CFG->CSV_ENCODE)) {
                    $csv_encode = '/\&\#' . $CFG->CSV_ENCODE . '/';
                }
            } else {
                $csv_delimiter = "\,";
                $csv_delimiter2 = ",";
            }

            /**
             * File that is used is currently hardcoded here!
             * Large files are likely to take their time and memory. Let PHP know
             * that we'll take longer, and that the process should be recycled soon
             * to free up memory.
             */
            @set_time_limit(0);
            @raise_memory_limit('192M');
            if (function_exists('apache_child_terminate')) {
                @apache_child_terminate();
            }

            $text = $this->my_file_get_contents($filename);
            // Trim utf-8 bom.
            $textlib = new core_text();
            $text = $textlib->trim_utf8_bom($text);
            // Fix mac/dos newlines.
            $text = preg_replace('!\r\n?!',"\n",$text);
            $text = preg_replace('!;!',", ",$text);
            $fp = fopen($filename, 'w');
            fwrite($fp,$text);
            fclose($fp);
        }
    }

    function my_file_get_contents($filename, $use_include_path = 0) {
        // Returns the file as one big long string.
        $data = '';
        $file = @fopen($filename, 'rb', $use_include_path);
        if ($file) {
            while (!feof($file)) {
                $data .= fread($file, 1024);
            }
            fclose($file);
        }
        return $data;
    }

    /**
     * deletes a text line in a stored file
     * @param string $filename
     * @param int $linenumber
     */
    function deleteLine($filename, $linenumber) {
        global $CFG;

        $filename = $CFG->dataroot.'/'.$filename;

        if (!($file = @fopen($filename,'r'))) {
            exit;
        }

        $text = '';
        $i = 1;
        while ($i < $linenumber) {
            $text .= fgets($file);
            $i++;
        }
        $tmp = fgets($file);
        while (!feof($file)) {
            $text .= fgets($file);
        }
        fclose($file);

        $fp = fopen($filename, 'w');
        fwrite($fp,$text);
        fclose($fp);
    }

}


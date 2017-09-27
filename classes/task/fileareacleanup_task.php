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

namespace tool_sync\task;

use \context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to sync users by file.
 */
class fileareacleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_fileareacleanup', 'tool_sync');
    }

    /**
     * Do the job.
     */
    public function execute() {

        $config = get_config('tool_sync');

        $fs = get_file_storage();

        $systemcontext = context_system::instance();

        $delay = $config->fileareacleanupdelay;

        if (empty($delay)) {
            return;
        }

        mtrace("Searching files older than ".$delay." days");
        $files = $fs->get_area_files($systemcontext->id, 'tool_sync', 'syncfiles', 0, "itemid, filepath, filename", false);

        if (!empty($files)) {
            foreach ($files as $f) {
                $fc = $f->get_timecreated();
                if ($fc < (time() - ($delay * DAYSECS))) {
                    mtrace("Removing ".$f->get_filepath().$f->get_filename());
                    $f->delete();
                }
            }
        } else {
            mtrace("No files to remove ");
        }

        return true;
    }
}
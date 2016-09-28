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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/userpictures/userpictures.class.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/logmuter.class.php');

/**
 * Scheduled task to sync users by file.
 */
class userpicturessync_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_syncuserpictures', 'tool_sync');
    }

    /**
     * Do the job.
     */
    public function execute() {
        $logmuter = new logmuter();
        $logmuter->activate();

        // Ensure we have all input files.
        tool_sync_capture_input_files(false);

        // Process task.
        $syncconfig = get_config('tool_sync');
        $userpicturesmanager = new \tool_sync\userpictures_sync_manager();
        $userpicturesmanager->cron($syncconfig);

        $logmuter->deactivate();
        return true;
    }
}
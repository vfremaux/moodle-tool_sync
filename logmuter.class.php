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
 * IPAG Plugin - local utilities library
 *
 * @copyright  2016 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    local_ipag
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_sync\task;

defined('MOODLE_INTERNAL') || die;

/**
 * log muter class
 * This class encapsulates Moodle logsystem disabling functionality
 * It works by overriding configuration parameters to clear out the list of active log stores
 * The muter object must be deactived to reinstate the previous configuration
 * Error checking code checks that:
 * - All logmuter objects are manually deactivated before destruction
 * - If several logmuter objects are activated in a cascade then they are deactrivated again
 *   in reverse of the order in which they were activated (they must be unstacked in order)
 */

class logmuter
{
    // static variables
    private static $oldsettings = null;
    private static $stacksize   = 0;

    // instance variables
    private $stackidx           = -1;


    /**
     * the class constructor
     */
    function __construct()
    {
        global $CFG;
        // if this is the first object to be instantiated then store away the previous configuration settings to allow us to restore them on unmute
        if (self::$oldsettings===null){
                self::$oldsettings =
                (array_key_exists('tool_log', $CFG->forced_plugin_settings))
                ? $CFG->forced_plugin_settings['tool_log']
                : array();
        }
    }

    /**
     * the class constructor
     * ensures that the log is
     */
    function __destruct()
    {
        if ($this->stackidx !== -1){
            throw new Exception('Coding error: logmuter must be deactivated before destruction');
        }
    }

    /**
     * activate()
     * Disable logging in moodle - the logging must be reenabled before the object is destroyed
     * If the muter is already active then this routine returns safely
     */
    public function activate()
    {
        global $CFG;
        
        // if we're already active then there's nothing to do so just return
        if ($this->stackidx !== -1){
            return;
        }

        // keep track of where we are in the instance stack
        $this->stackidx = ++self::$stacksize;
        
        // if we're not the first stack entry then the logs are already mute so nothing to do ...
        if ($this->stackidx !== 1){
            return;
        }

        // override the configuration settings to disable all log stores and force re-construction of log manager singleton
        $CFG->forced_plugin_settings['tool_log'] = array('enabled_stores'=>'');
        get_log_manager( true );
    }

    /**
     * deactivate()
     * Re-enable logging in moodle
     * If the muter is already deactivated then this routine returns safley
     */
    public function deactivate()
    {
        global $CFG;
        
        // if we're not currently active then there's nothing to do so just return
        if ($this->stackidx === -1){
            return;
        }

        // make sure that muters are deactivated in the reverse order of activation
        if ($this->stackidx !== self::$stacksize){
            throw new Exception('Coding error: logmuter being deactivated out of order');
        }

        // deal with unstacking logic
        $this->stackidx = -1;
        self::$stacksize--;
        
        // if the stack isn't empty then it isn't time to unmute the logs yet
        if (self::$stacksize !== 0){
            return;
        }

        // restore old logging configuration settings and force re-construction of log manager singleton
        $CFG->forced_plugin_settings['tool_log'] = self::$oldsettings;
        get_log_manager( true );
    }
}


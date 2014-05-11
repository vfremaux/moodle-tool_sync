<?php // $Id: version.php,v 1.2 2013-01-18 16:26:59 vf Exp $
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
 * Flat file enrolment plugin version specification.
 *
 * @package    tool_sync
 * @copyright  2013 Valery Fremaux 
 * @author     Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2013011800;
$plugin->requires  = 2011092100;        // Requires this Moodle version
$plugin->component = 'tool_sync';  // Full name of the plugin (used for diagnostics)
$plugin->cron      = 5;
$plugin->maturity = MATURITY_BETA;
$plugin->dependencies = array('block_publishflow' => 2012031800); // Needed for templating creation of new courses
$plugin->release = '2.2.0 (Build 2012102200)';

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
 * @package     tool_sync
 * @category    tool
 * @author      Funck Thibaut
 * @copyright   2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This test script generates a set of three synchronisation descriptor files
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
if (!isadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}
if (! $site = get_site()) {
    print_error('errornosite', 'tool_sync');
}
if (!$adminuser = get_admin()) {
    print_error('errornoadmin', 'tool_sync');
}

$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('filegenerator', 'tool_sync'));

// create a test course definition file.

$filename = $CFG->dataroot.'/sync/uploadcourses.csv';
$file = fopen($filename, 'w');

fputs($file, "fullname, shortname, idnumber\n");
for ($i = 0 ; $i < 500 ; $i++) {
    fputs($file,"full$i, short$i, id$i\n");
}
fputs($file,"full500, short500, id500");
fclose($file);

// create a test user definition file.

$filename = $CFG->dataroot.'/sync/uploadusers.csv';	
$file = fopen($filename, "w");

fputs($file,"username, firstname, lastname, email, password, lang, country, idnumber, auth\n");
for ($i = 0 ; $i < 500 ; $i++) {
    fputs($file,"full$i, short$i, last$i, mail$i@ldap.fr, pass$i, fr_utf8, FR, id$i, ldap\n");
}
fputs($file,"full500, short500, last500, mail500@ldap.fr, pass500, fr_utf8, FR, id500, ldap");
fclose($file);	

// create a test enrollement file.

$filename = $CFG->dataroot.'/sync/enrol.txt';	
$file = fopen($filename, 'w');

for ($i = 0 ; $i < 500 ; $i++) {
    fputs($file,"student, id$i, id$i\n");
    fputs($file,"student, id7, id$i\n");
}
fputs($file,"student, id500, id500");
fclose($file);

echo $OUTPUT->footer();

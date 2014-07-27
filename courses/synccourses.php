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
 * @author Funck Thibaut
 *
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/courses.class.php');

require_login();

if (!is_siteadmin()) {
    print_error('erroradminrequired', 'tool_sync');
}
if (! $site = get_site()) {
    print_error('errornosite', 'tool_sync');
}
if (!$adminuser = get_admin()) {
    print_error('errornoadmin', 'tool_sync');
}

$url = $CFG->wwwroot.'/admin/tool/sync/courses/synccourses.php';
$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
$PAGE->navigation->add(get_string('coursecheck', 'tool_sync'));
$PAGE->set_url($url);
$PAGE->set_context(null);
$PAGE->set_title("$site->shortname");
$PAGE->set_heading($site->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('checkingcourse', 'tool_sync'));
$returntotoolsstr = get_string('returntotools', 'tool_sync');
sync_print_remote_tool_portlet('importfile', $CFG->wwwroot.'/admin/tool/sync/courses/synccourses.php', 'createcourse', 'upload');
sync_print_local_tool_portlet($CFG->tool_sync_course_fileuploadlocation, 'commandfile', 'synccourses.php');
require_once($CFG->dirroot.'/lib/uploadlib.php');

// If there is a file to upload... do it... else do the rest of the stuff.
$um = new upload_manager('createcourse', false, false, null, false, 0);

if ($um->preprocess_files() || isset($_POST['uselocal'])) {
    // All file processing stuff will go here. ID=2...
    if (isset($um->files['createcourse'])) {
          echo $OUTPUT->notification(get_string('parsingfile', 'tool_sync'), 'notifysuccess');
        $filename = $um->files['createcourse']['tmp_name'];
    }

    $uselocal = optional_param('uselocal', false, PARAM_BOOL);
    if(!empty($uselocal)){
        $filename = $CFG->tool_sync_course_fileuploadlocation;
        $filename = $CFG->dataroot.'/'.$filename;
    }
    
    // execron do everything a cron will do.
    echo($filename);
    if (isset($filename) && file_exists($filename)){
        $filestouse = new StdClass;
        $filestouse->create = $filename;
        $coursesmanager = new course_sync_manager($filestouse, SYNC_COURSE_CREATE);

        echo '<pre>';
        $coursesmanager->cron();
        echo '</pre>';
        
        sync_save_check_report();
    }
}

/**
* writes an operation report file telling about all course tested.
*
*/
function sync_save_check_report() {
    global $CFG;

    $t = time();
    $today = date("Y-m-d_H-i-s",$t);
    $filename = $CFG->dataroot."/sync/reports/CC_$today.txt";
    
    if($FILE = @fopen($filename,'w')) {
        fputs($FILE, '' + @$CFG->tool_sync_courselog);
    }
    fclose($FILE);
}

if ($del = optional_param('del', 0, PARAM_BOOL)){
    $filename = optional_param('delname', '', PARAM_TEXT);
    if($filename){
        @unlink($filename);
    }
}    

if ($purge = optional_param('purge', false, PARAM_TEXT)){
    $reports = glob($CFG->dataroot.'/sync/reports/CC_*');
    if (!empty($reports)){
        foreach($reports as $report){
            @unlink($report);
        }
    }
}

echo '<br/><br/><fieldset><legend><strong>'.get_string('displayoldreport', 'tool_sync').'</strong></legend>';
$entries = glob($CFG->dataroot."/sync/reports/CC_*");
$filecabinetstr = get_string('filecabinet', 'tool_sync');
$filenameformatstr = get_string('filenameformatcc', 'tool_sync');
echo "<br/><strong>$filecabinetstr</strong>: $CFG->dataroot/sync/reports<br/>";
echo "$filenameformatstr<br/><br/>";
echo '<ul>';
foreach($entries as $entry){
    echo '<li> '.basename($entry).'</li>';
}
echo '</ul>';
echo '<br/>';

$loadstr = get_string('load', 'tool_sync');
$purgestr = get_string('purge', 'tool_sync');
echo '<center>';
echo '<form method="post" action="synccourses.php" style="display:inline">';
echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
print_string('enterfilename', 'tool_sync');
echo '<input type="text" name="filename" size="30"> <input type="submit" value="'.$loadstr.'">';
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
echo '</form>';

echo '<form method="post" action="synccourses.php" style="display:inline">';
echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
echo '<input type="submit" name="purge" value="'.$purgestr.'">';
echo '</form>';
echo '</center>';
 
echo '<br/>';

$name = optional_param('filename', '', PARAM_TEXT);
if(!empty($name)){
    $filename = "$CFG->dataroot/sync/reports/$name";
    
    if ($file = file($filename)){
        echo '<pre>';
        echo implode("\n", $file);
        echo '</pre>';
    }

    echo '<center>';
    echo '<form method="post" action="synccourses.php">';
    echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
    echo '<input type="hidden" name="delname" value="'.$filename.'">';
    print_string('deletethisreport', 'tool_sync');
    echo '<input type=radio name="del" value="1" /> '.get_string('yes').' <input type=radio name="del" value="0" checked/> '.get_string('no').'<br/>';
    echo '<input type="submit" value="'.get_string('delete').'">';
    echo '</form>';
    echo '</center>';
}

echo '</fieldset>';

// always return to main tool view.
echo $renderer->print_return_button();

echo $OUTPUT->footer();

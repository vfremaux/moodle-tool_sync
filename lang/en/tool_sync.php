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

$string['automation'] = 'Feeders and automation';
$string['addedtogroup'] = 'User {$a->myuser} added to group {$a->group}';
$string['addedtogroupnot'] = 'User {$a->myuser} NOT added to group {$a->group}';
$string['allowrename'] = 'Allow renaming users';
$string['alreadyassigned'] = 'User {$a->myuser} already assigned to {$a->myrole} in course {$a->mycourse}';
$string['archivecontrolfiles'] = 'Archivates command files after sync has run';
$string['assign'] = 'Role "{$a->myrole}" added for {$a->myuser} in course {$a->mycourse}';
$string['backtoprevious'] = 'Back to previous screen';
$string['boxdescription'] = 'Management tool syncs current user and group using txt and csv files called by cron.<br/><br/>Simply specify the directories of the files <br/>It is also possible to trigger these scripts manually.';
$string['builddeletefile'] = 'Generate a deletion command file';
$string['buildresetfile'] = 'Generate a reinitialisation CSV file';
$string['button'] = 'Save the configuration tools';
$string['categoryremoved'] = 'Category {$a} deleted';
$string['checkingcourse'] = 'Checking courses';
$string['choosecoursetodelete'] = 'Choose courses to be deleted:';
$string['choosecoursetoreset'] = 'Choose courses to be reinitialized:';
$string['cleancategories'] = 'Clean empty categories';
$string['cohortcohortidentifier'] = 'Cohort identifier';
$string['cohortfilelocation'] = 'Cohorts file location';
$string['cohortcoursebindingfilelocation'] = 'Cohorts to course file location';
$string['cohortcourseidentifier'] = 'Course identifier';
$string['cohortmanualsync'] = 'Manual execution of cohort synchronisation processes';
$string['cohortmgtmanual'] = 'Manual management for cohorts';
$string['cohortsconfig'] = 'Cohorts synchonisation configuration';
$string['cohortautocreate'] = 'Create missing cohorts';
$string['cohortsyncdelete'] = 'Cleanup empty cohorts when syncing';
$string['cohortsync'] = 'Cohorts synchonisation';
$string['cohortsstarting'] = 'Cohort sync starting...';
$string['cohortcreated'] = 'Cohort {$a->name} created';
$string['cohortmemberadded'] = 'Cohort member {$a->username} ({$a->idnumber}) added to cohort {$a->cname}';
$string['cohortmemberremoved'] = 'Cohort member {$a->username} ({$a->idnumber}) removed from cohort {$a->cname}';
$string['cohortalreadymember'] = 'User {$a->username} ({$a->idnumber}) already member of cohort {$a->cname}';
$string['cohortuseridentifier'] = 'Cohort user identifier';
$string['cohortusernotfound'] = 'Cohort user {$a->identifier} as {$a->uid} was not found.';
$string['cohortnotfound'] = 'Cohort {$a->identifier} as {$a->cid} was not found. Script cannot create.';
$string['commandfile'] = 'Control file';
$string['communicationerror'] = 'Communication error with remote. Errors : {$a}';
$string['configdefaultcmd'] = 'Default value for the command column';
$string['configuration'] = 'Input files format configuration';
$string['confirm'] = 'Confirm';
$string['confirmdelete'] = 'Confirm deletion with the choosen file';
$string['confirmcleancats'] = 'Confirm deletion of empty categories';
$string['coursecheck'] = 'course check';
$string['coursecatdeleted'] = 'Course category {$a} has been deleted.';
$string['coursecreated'] = 'Course [{$a->shortname}] {$a->fullname} has been created.';
$string['coursecronprocessing'] = 'Course synchronisation by cron';
$string['coursedefaultsummary'] = 'Write a concise and interesting paragraph here that explains what this course is about';
$string['coursedeleted'] = 'Course {$a} deleted.';
$string['coursedeletefile'] = 'Course deletion file';
$string['coursedeletion'] = 'Course destruction';
$string['courseexists'] = 'Course [{$a->shortname}] {$a->fullname} already exists.';
$string['coursefoundas'] = 'Course with idnumber {$a->idnumber} found as : <ol><li>fullname = {$a->fullname} </li><li> shortname = {$a->shortname}</li></ol>';
$string['coursefullname'] = 'Full Name';
$string['coursemanualsync'] = 'Manual execution of course synchronisation processes';
$string['coursemgtmanual'] = 'Manual management for courses';
$string['coursenodeleteadvice'] = 'Bulk Deleter will not delete the course : {$a}. Course not found.';
$string['coursenotfound'] = 'Course {$a} does not exist in this Moodle. \n';
$string['coursenotfound2'] = 'Course with idnumber "{$a->idnumber}" "{$a->description}" not found in this moodle';
$string['coursereset'] = 'Mass reinitialization of courses';
$string['coursescronconfig'] = 'Enable synchronization cron courses';
$string['coursesmgtfiles'] = 'Course management command files';
$string['coursesync'] = 'Courses synchronization';
$string['courseupdated'] = 'Course {$a->shortname} updated.';
$string['createpasswords'] = 'Create passwords';
$string['createtextreport'] = 'Do you want to create a text report?';
$string['creatingcohort'] = 'Creating cohort {$a}';
$string['creatingcoursefromarchive'] = 'Creating course with {$a}';
$string['criticaltime'] = 'Time limit';
$string['taskrunmsg'] = 'Script execution on {$a}<br/>.';
$string['taskrunmsgnofile'] = 'No file<br/>.';
$string['runlocalfiles'] = 'Run all commands';
$string['csvseparator'] = 'CSV field separator';
$string['day_fri'] = 'Friday';
$string['day_mon'] = 'Monday';
$string['day_sat'] = 'Saturday';
$string['day_sun'] = 'Sunday';
$string['day_thu'] = 'Thursday';
$string['day_tue'] = 'Tuesday';
$string['day_wed'] = 'Wednesday';
$string['deletecontrolfiles'] = 'Delete command files after sync has run';
$string['deletecoursesconfirmquestion'] = 'Are you absolutely sure you want to completely delete these courses<br />for all eternity and from the face of this planet, forever?';
$string['deletefile'] = 'Delete command file after running the sync';
$string['deletefilebuilder'] = 'Creating command files for course deletion';
$string['deletefileidentifier'] = 'File deletion identifier';
$string['deletefileinstructions'] = 'Choose a file that givs the list of course identifiers to delete (one item per line).';
$string['deletefromremote'] = 'Upload and instant run a deletion file';
$string['deletethisreport'] = 'Do you want to delete this report?';
$string['description'] = '<center><a href="/enrol/sync/index.php">Complete course and user synchronization manager</a></center>';
$string['disabled'] = 'Disabled.';
$string['displayoldreport'] = 'Display an old report';
$string['rootcategory'] = '--- Root category ---';
$string['startcategory'] = 'Starting category';
$string['ignoresubcats'] = 'Ignore subcategories (if empty)';
$string['cleancats'] = 'Cleanup categories';
$string['emptycats'] = 'Empty categories from: {$a}';
$string['emptygroupsdeleted'] = 'Empty groups deleted';
$string['encoding'] = 'Source file encoding';
$string['endofprocess'] = 'End of process<br/>';
$string['endofreport'] = 'end of report';
$string['enrolcourseidentifier'] = 'Course identifier field';
$string['enrolcronprocessing'] = 'Enrol cron processing';
$string['enroldefault'] = 'Default processing';
$string['enroldefaultcmd'] = 'Default value for the command column';
$string['enroldefaultcmd_desc'] = 'If the "cmd" column is missing or value is empty, what will be the default command processed';
$string['enroldefaultinfo'] = 'Default configuration for the column cmd';
$string['enrolemailcourseadmins'] = 'Notify enrolments to course admins';
$string['enrolemailcourseadmins_desc'] = 'If enabled, sends a notification (enrolment summary) to course admins (usually teachers)';
$string['enrolfile'] = 'Enrol command file';
$string['enrolfilelocation'] = 'Enrol file location';
$string['enrolled'] = 'User {$a->myuser} enrolled in course {$a->mycourse}';
$string['enrollednot'] = 'User enrol {$a->myuser} failure in course {$a->mycourse}';
$string['enrolmanualsync'] = 'Manual execution of enrollment syncronisation';
$string['enrolmgtmanual'] = 'Manual execution of the enrollment command file';
$string['enrolname'] = 'Courses and Users Synchronization Manager ';
$string['enrolsconfig'] = 'configuration for enrol synchronisation';
$string['enrolscronconfig'] = 'Enable synchronization cron enrolments';
$string['enrolsync'] = 'Automation of enrolments';
$string['enroluseridentifier'] = 'User identifier field';
$string['enterfilename'] = 'Enter the file name to be viewed:';
$string['erroradminrequired'] = 'You must be an administrator to edit courses in this way.';
$string['errorbackupfile'] = 'Something was wrong with the backup file (Error code: {$a->error}).';
$string['errorinputconditions'] = 'Incorrect course create input condition in function call.';
$string['errorbadchooseformat'] = 'Incorrect format for choose parameter';
$string['errorbadcmd'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : bad command column value.';
$string['errorbadcount'] = 'Error at line {$a->i} : {$a->count} values found. {$a->expected} expected.';
$string['errorcategorycontextdeletion'] = 'Error while removing context : category {$a}';
$string['errorcategorycreate'] = 'Error at line {$a->i}: An error occured creating category with name {$a->catname}, meaning a total {$a-failed} category(ies) failed';
$string['errorcategorydeletion'] = 'Error while removing category {$a}';
$string['errorcategoryparenterror'] = 'Error at line {$a->i}: Course with shortname {$a->coursename} could not be created because parent category(ies) failed';
$string['errorcoursedeletion'] = 'Error deleting course with id : {$a} The course may have been deleted, but not all of the elements.';
$string['errorcoursemisconfiguration'] = 'Error at line {$a->i}: Could not enrol teacher for course due to course misconfiguration. Course shortname: {$a->coursename}';
$string['errorcourseupdated'] = 'Error at line {$a->i}: Course {$a->shortname} update failed.';
$string['errorcritical'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : critical error.';
$string['errordirectory'] = 'Requested directory does not exist.';
$string['erroremptycommand'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : no value for the command column \'cmd\'';
$string['erroremptyrole'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : empty role';
$string['errorenrol'] = 'Enrol failed. {$a->myuser} in course {$a->mycourse}';
$string['errorgcmdvalue'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : No group command column value';
$string['errorinvalidcolumnname'] = 'Error: column name as n{$a} is invalid';
$string['errorinvalidfieldname'] = 'Error: invalide field name "{$a}"';
$string['errorline'] = 'Line';
$string['erroropeningfile'] = 'Error opening file';
$string['errornoadmin'] = 'Could not find site admin';
$string['errornocourse'] = 'Error at line {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : No such course';
$string['errornocourses'] = 'Error : No courses were parsed from CSV';
$string['errornomanualenrol'] = 'No manual enrol. Disabling enrolment activation.';
$string['errornorole'] = 'Error at line {$a->i}: {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : you need mention a role identifier for assigning or changing assignation';
$string['errornosite'] = 'Could not find site-level course';
$string['errornoteacheraccountkey'] = 'Error at line {$a->i}: Invalid value for field teacher{$a->key} - other fields were specified but required teacher {$a->key}_account was null.';
$string['errornouser'] = 'Error at line {$a->i}: {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : No such user';
$string['errornullcourseidentifier'] = 'Error at line {$a}: Null or invalid course identifier.';
$string['errornullcsvheader'] = 'Error : Null CSV columns are not permitted in header';
$string['errorrequiredcolumn'] = 'Error : Missing column: {$a}';
$string['errorrestoringtemplate'] = 'Error at line {$a->i}: An Error occured creating course with shortname {$a->coursename} from template backup';
$string['errorrestoringtemplatesql'] = 'Error at line {$a->i}: SQL Template Error for template course with shortname {$a->template} Course with shortname {$a->coursename} failed.';
$string['errorrpcparams'] = 'Error : RPC param error: {$a}';
$string['errors'] = 'Errors ';
$string['errorsectioncreate'] = 'Error at line {$a->i}: Could not create topic sections for course with shortname {$a->coursename}';
$string['errorsettingremoteaccess'] = 'Error openning user remote mnet access : {$a}';
$string['errorsitepermissions'] = 'The site administrator needs to fix the file permissions';
$string['errorteacherenrolincourse'] = 'Error at line {$a->i}: Could not enrol teachers for course with shortname {$a->coursename}';
$string['errorteacherrolemissing'] = 'Error at line {$a->i}: Could not find teacher role for course with shortname {$a->coursename}';
$string['errortemplatenotfound'] = 'Error at line {$a->i}: Could not find template course with shortname {$a->template}. Course with shortname {$a->coursename} failed.';
$string['errortoooldlock'] = 'Error : a locked.txt file is too old an in the way. A previous synchronisation might have failed.';
$string['errorunassign'] = 'Error ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : Role {$a->myrole} unassignation failed.';
$string['errorunassignall'] = 'Error ligne {$a->i}: {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : All Roles unassignation failed.';
$string['errorunenrol'] = 'Unenrol failed. {$a->myuser} in course {$a->mycourse}';
$string['erroruploadpicturescannotunzip'] = 'Error: Zip file cannot be unzipped : {$a} (may be empty)';
$string['errorvalidationbadtype'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (not an integer or string).';
$string['errorvalidationbaduserid'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (No User with ID {$a->value}).';
$string['errorvalidationcategorybadpath'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Path string "{$a->path}" invalid - not delimited correctly).';
$string['errorvalidationcategoryid'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (No Category with ID {$a->category}).';
$string['errorvalidationcategorylength'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Category name "{$a->item}" length &gt; 30).';
$string['errorvalidationcategorytype'] = 'Eror at line {$a->i}: Invalid value for field {$a->fieldname} (Path string "{$a->value}" invalid - category name at position {$a->pos} as shown is invalid).';
$string['errorvalidationcategoryunpathed'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Path string not set).';
$string['errorvalidationempty'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (only spaces or missing).';
$string['errorvalidationintegerabove'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (&gt; {$a->max}).';
$string['errorvalidationintegerbeneath'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (&lt; {$a->min}).';
$string['errorvalidationintegercheck'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (not an integer).';
$string['errorvalidationmultipleresults'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Search string ambiguous; returned multiple [{$a->ucount}] results).';
$string['errorvalidationsearchfails'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Search string returned no results).';
$string['errorvalidationsearchmisses'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Search string returned a nonexistent user ?!).';
$string['errorvalidationstringlength'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (length &gt; {$a->length}).';
$string['errorvalidationtimecheck'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Bad Timestamp).';
$string['errorvalidationvalueset'] = 'Error at line {$a->i}: Invalid value for field {$a->fieldname} (Must be one of {$a->set}).';
$string['eventscleanup'] = 'Generated events cleanup';
$string['execstartsat'] = 'Exec starts at {$a}';
$string['executecoursecronmanually'] = 'Execute all course tasks manually';
$string['existcoursesfile'] = 'Course existance check command file';
$string['existfileidentifier'] = 'Existance identifier';
$string['failedfile'] = 'Tryback file';
$string['filearchive'] = 'Control files archiving';
$string['filecabinet'] = 'Report file repo path';
$string['filecleanup'] = 'Control files cleanup';
$string['filegenerator'] = 'Command file generator';
$string['filemanager'] = 'File manager';
$string['filemanager2'] = 'File manager';
$string['filenameformatcc'] = '<strong>Filename format:</strong> CC_YYYY-MM-DD_hh-mm.txt';
$string['filenameformatuc'] = '<strong>Filename format:</strong> UC_YYYY-MM-DD_hh-mm.txt';
$string['filenotfound'] = 'File {$a} not found.';
$string['filetoprocess'] = 'File to process';
$string['final_action'] = 'Post-processing';
$string['flatfilefoundforenrols'] = 'Command file found for enrols: ';
$string['forcecourseupdateconfig'] = 'If enabled, existing courses will have settings updated to new values. Course content remains unchanged.';
$string['foundfile'] = 'File found : {$a}';
$string['foundfilestoprocess'] = 'Found {$a} files to process';
$string['generate'] = 'Generate';
$string['getfile'] = 'Get the command file';
$string['groupcreated'] = 'Group {$a->group} created in course {$a->mycourse}';
$string['group_clean'] = 'Group cleanup';
$string['group_cleanex'] = 'Clear groups that are present in moodle but empty of users';
$string['groupunkown'] = 'This group {$a->group} is not known in {$a->mycourse} and cannot be created.';
$string['groupnotaddederror'] = 'Error on group assignation : {$a}';
$string['hiddenroleadded'] = 'Hidden role added on context:';
$string['hour'] = 'hour';
$string['importfile'] = 'Import a new test file';
$string['invalidseparatordetected'] = 'Invalid separator detected. The file formatting may not match the tool settings.';
$string['load'] = 'Load';
$string['location'] = 'Emplacement';
$string['mail'] = 'Process report';
$string['mailenrolreport'] = 'Enrolment report:';
$string['makedeletefile'] = 'Create a file for course deletion';
$string['makefailedfile'] = 'Generate a tryback file';
$string['makeresetfile'] = 'Create a file for course reinitialisation';
$string['manualcleancategories'] = 'Manually clean up empty categories';
$string['manualcohortrun'] = 'Run this script manually from the stored command file';
$string['manualdeleterun'] = 'Run manually course deletion';
$string['manualenrolrun'] = 'Run this script manually from the stored command file';
$string['manualhandling'] = 'Manual handling of operations';
$string['manualmetasrun'] = 'Manual programming metacourse relations';
$string['manualuploadrun'] = 'Run manually a course creation';
$string['manualuserpicturesrun'] = 'Run user pictures resync manually';
$string['manualuserrun'] = 'Run user sync manually from the stored command file';
$string['manualuserrun2'] = 'Run user sync manually from a file upload';
$string['metabindingfile'] = 'Metacourse binding file';
$string['metabindingfileidentifier'] = 'course identifier for Metacourse bindings';
$string['metalinkcreated'] = 'Metacourse binding for {$e->for} from {$e->from} created';
$string['metalinkrevived'] = 'Metacourse binding for {$e->for} from {$e->from} restored';
$string['metalinkdisabled'] = 'Metacourse binding for {$e->for} from {$e->from} disabled';
$string['missingidentifier'] = 'Required identifier {$a} is missing in input data';
$string['minute'] = 'minute';
$string['ncategoriesdeleted'] = '{$a} categories deleted';
$string['noeventstoprocess'] = 'No event at line {$a}';
$string['nofile'] = 'No file available';
$string['nofileconfigured'] = 'No file configured for this operation';
$string['nofiletoprocess'] = 'No file to process';
$string['nogradestoprocess'] = 'No grades at line {$a}';
$string['nogrouptoprocess'] = 'No groups to process';
$string['nologstoprocess'] = 'No logs at line {$a}';
$string['nonotestoprocess'] = 'No notes to process at line {$a}';
$string['nonuniqueidentifierexception'] = 'The ID number at row {$a} leads to a non unique course identification. Reinitialisation is bypassed for those courses';
$string['nothingtodelete'] = 'No element to delete';
$string['optionheader'] = 'Synchronization options';
$string['parsingfile'] = 'Parsing file...';
$string['passwordnotification'] = 'Your credentials on {$a}';
$string['pluginname'] = 'User and course synchronisation by files';
$string['pluginname_desc'] = 'User and course synchronisation by CSV files';
$string['predeletewarning'] = '<b><font color="red">WARNING:</font></b> Bulk Deleter is about to delete the following courses:';
$string['process'] = 'Process';
$string['processingfile'] = 'Processing...';
$string['processresult'] = 'Process result';
$string['processerror'] = 'Process error. the reason is: {$a}';
$string['protectemails'] = 'Protect emails';
$string['purge'] = 'Prune all reports';
$string['primaryidentity'] = 'Primary identity field';
$string['reinitialisation'] = 'Reset courses';
$string['remoteenrolled'] = 'User {$a->username} remote enrolled as {$a->rolename} on {$a->wwwroot} on course {$a->coursename}';
$string['remoteserviceerror'] = 'Remote service error';
$string['registeringincohort'] = 'Will register in cohort {$a}';
$string['resetfile'] = 'Course Reset command file';
$string['resetfilebuilder'] = 'Course Reset CSV file generator';
$string['resetfileidentifier'] = 'Reinitialisation selection identifier';
$string['resetfileidentifier'] = 'Reset file course identifier';
$string['resettingcourse'] = 'Resetting course: ';
$string['resettingcourses'] = 'Mass course reset';
$string['returntotools'] = 'Return to tools';
$string['roleadded'] = 'Role "{$a->rolename}" added on context {$a->contextid}';
$string['run'] = 'Run';
$string['runtime'] = 'Run time';
$string['selecteditems'] = 'Selected courses for generation';
$string['selectencoding'] = 'Select encoding for source files';
$string['selectseparator'] = 'Choose the separator for CSV fields. This separator operates for all command files in the synchronisation manager.';
$string['shortnametodelete'] = 'Course to delete';
$string['skippedline'] = 'Source line ({$a}) skipped as not in correct format';
$string['startingcheck'] = 'Starting check courses...';
$string['startingreset'] = 'Starting resetting courses...';
$string['startingdelete'] = 'Starting deleting courses...';
$string['startingcreate'] = 'Starting creating courses...';
$string['storedfile'] = 'Stored file: {$a}';
$string['sync:configure'] = 'Configure the synchronisation manager';
$string['task_synccohorts'] = 'CSV Cohorts synchronisation';
$string['task_synccourses'] = 'CSV Course Synchronisation';
$string['task_syncenrols'] = 'CSV Enrolment Synchronisation';
$string['task_syncuserpictures'] = 'CSV User Picture Synchronisation';
$string['task_syncusers'] = 'CSV Users Synchronisation';
$string['sendpasswordtousers'] = 'Send password by mail to users';
$string['simulate'] = 'Simulate result without altering data';
$string['storereport'] = 'Store report file';
$string['synccohorts'] = 'Cohorts synchronisation';
$string['syncconfig'] = 'Synchronisation configuration';
$string['synccourses'] = 'Course Manager';
$string['syncenrol'] = 'Enrol and roles synchronisation';
$string['syncenrols'] = 'Enrolment manager';
$string['syncfiles'] = 'Synchronisation files';
$string['syncforcecourseupdate'] = 'Force course update';
$string['synchronization'] = 'Data synchronisation';
$string['syncuserpictures'] = 'User picture synchronisation';
$string['syncusers'] = 'Users manager';
$string['testcourseexist'] = 'Test if courses exist in Moodle';
$string['title'] = '<center><h1>Synchronization manager : configuration</h1></center>';
$string['toolindex'] = 'Tool index';
$string['totaltime'] = 'Total Execution Time: ';
$string['unassign'] = 'The {$a->myrole} role asignation deletion for {$a->myuser} in course {$a->mycourse}';
$string['unassignall'] = 'All role deletion for {$a->myuser} in course {$a->mycourse}';
$string['unenrolled'] = 'User {$a->myuser} unenrolled from course {$a->mycourse}';
$string['unknownrole'] = 'Role unknown at line {$a->i}';
$string['unknownshortname'] = 'Course name unkown at line {$a->i}';
$string['upload'] = 'Upload';
$string['uploadcourse'] = 'Course synchronisation';
$string['uploadcoursecreationfile'] = 'Course creation file';
$string['uploadfile'] = 'Import file';
$string['uploadfile'] = 'Upload file';
$string['uploadpictures'] = 'Pictures synchronisation';
$string['uploadusers2'] = 'User account synchronisation';
$string['uselocal'] = 'Use the local file: {$a}';
$string['useraccountadded'] = 'User account added: {$a} ';
$string['useraccountupdated'] = 'User account changed: {$a} ';
$string['usercollision'] = 'Error : User username collision when creating for {$a}';
$string['usercreatedremotely'] = 'User {$a->username} created on {$a->wwwroot} ';
$string['usercronprocessing'] = 'User Accounts Synchronisation';
$string['userexistsremotely'] = 'User {$a} exists on remote end';
$string['usermgtmanual'] = 'Manual user update';
$string['userpicturehashfieldname'] = 'User picture hash';
$string['usernotaddederror'] = 'Error on account creation : {$a}';
$string['usernotrenamedexists'] = 'Error on account rename (target name exists) : {$a}';
$string['usernotrenamedmissing'] = 'Error on acocunt rename (source account missing) : {$a}';
$string['usernotupdatederror'] = 'Error on account update : {$a}';
$string['userpicturesconfig'] = 'Configuration for user pictures synchronisation';
$string['userpicturescronconfig'] = 'Enable prcessing of user pictures files';
$string['userpicturescronprocessing'] = 'Processing user picture files';
$string['userpicturesfilesprefix'] = 'Picture files prefix';
$string['userpicturesfilesprefix_desc'] = 'All files matching this prefix will be processed in lexicographic order.';
$string['userpicturesforcedeletion'] = 'Force archive deletion after job';
$string['userpicturesforcedeletion_desc'] = 'Force deletion of source archives even if global deletion of command files is disabled in general sync settings.';
$string['userpicturesmanualsync'] = 'Manual user pictures update';
$string['userpicturesmgtmanual'] = 'User pictures management';
$string['userpicturesoverwrite'] = 'Overwrite existing pictures';
$string['userpicturesoverwrite_desc'] = 'If enabled, forces overwrite of existing pictures with new versions';
$string['userpicturesuserfield'] = 'User identification field';
$string['userpicturesuserfield_desc'] = 'Value of this field is used for image names (before file extension).';
$string['userpicturesync'] = 'User pictures synchronisation';
$string['userrevived'] = 'User was revived : {$a}';
$string['usersconfig'] = 'Configuration for user synchronisation';
$string['userscronconfig'] = 'Enable synchronization cron users';
$string['usersfile'] = 'File for syncing users ';
$string['usersupdated'] = 'Users updated ';
$string['userpicturehash'] = 'User picture checksum (MD5)';
$string['usersync'] = 'Users synchronization';
$string['userunknownremotely'] = 'User {$a} unkown on remote end';
$string['utilities'] = 'Utilities';

$string['coursesync_help'] = '
<p>this service allows creating massively courses with a default format or using a designated course template
stored as a restorable backup on Moodle. </p>
<p>Please consult the online documentation on docs.moodle.org for exact format possibilites
as they are numerous.</p>
';

$string['usersync_help'] = '
<p>this service allows importing and enrolling massively users from CSV formatted file,
eventually adding enrolments at the same time. </p>
<p>Please consult the online documentation on docs.moodle.org for exact format possibilites.</p>
';

$string['userpicturesync_help'] = '
<p>This service allows synchornising user\'s pictures using a Zip file with images.
It extends the standard pictures upload script with automation capability.
';

$string['enrolsync_help'] = '
<p>This service allows synchronising user\'s pictures using a Zip file with images.
It extends the standard pictures upload script with automation capability.
';

$string['cohortsync_help'] = '
<p>This service allows synchronising cohort definitions using a CSV file.
';

$string['syncconfig_help'] = '
These options mostly define the parameters required for automated scheduled execution of command files.';

$string['cleancategories_help'] = '
All categories having no more courses will be deleted from Moodle. This processing is recursive and
will then delete all full empty branches.
';

$string['coursedeletion_help'] = '
This service deletes massively courses based on the identifier choosed in settings. Please read the online documentation
for file format exact specification at http://docs.moodle.org/en/23".
';

$string['coursecreateformat'] = 'Course creation file format';
$string['coursecreateformat_help'] = '
Course reinitialisation file must be in ISO or UTF-8 format depending on Sync Tool settings.
The first line must hold column titles in any order.

<p>Two columns are mandatory, <b>shortname</b> and <b>fullname</b>. Shortname must not be already used in Moodle for the course
to be properly created.</p>

<p>Optional fields: <b>category, sortorder, summary, format, idnumber, showgrades, newsitems, startdate,
marker, maxbytes, legacyfiles, showreports, visible, visibleold, groupmode, groupmodeforce, defaultgroupingid,
lang, theme, timecreated, timemodified, self, guest, template</b></p>

';

$string['coursedeleteformat'] = 'Course deletion file format';
$string['coursedeleteformat_help'] = '
The file is a simple list of course primary identifiers, one per line, without any column title line.
the primary identifier field is given by the Sync Tool configuration.

the course identifier used depends on Sync Tool settings.
';

$string['coursecheckformat_help'] = '
The file is a simple list of course primary identifiers, one per line, without any column title line.
the primary identifier field is given by the Sync Tool configuration.
';

$string['coursereinitializeformat_help'] = '
Course reinitialisation file must be in ISO or UTF-8 format depending on Sync Tool settings.
The first line must hold column titles in any order.
The first field must identify a course, dpending on the selected course primary identifier in configuration :

<li><i>id</i>: Using the numeric internal DN identifier of the course.</li>
<li><i>shortname</i>: Using the course shortname</li>
<li><i>idnumber</i>: Using the IDNumber of the course</li>

<p>Mandatory fields : <b>events, logs, notes, completion, grades, roles, local_roles, groups, groupings,
blog_associations, comments, modules</b>

<p>Usual value is \'yes\' or \'no\' unless :</p>
<li><i>roles</i>: a list of role shortnames, separed by spaces.</li>
<li><i>local_roles</i>: \'all\' (roles and overrides), \'roles\' or \'overrides\'.</li>
<li><i>grades</i>: \'all\' (items and grades), \'items\' or \'grades\'.</li>
<li><i>groups</i>: \'all\' (groups and members), \'groups\' or \'members\'.</li>
<li><i>groupings</i>: \'all\' (groups and members), \'groups\' or \'members\'.</li>
<li><i>modules</i>: \'all\' (reset all modules), or a list of module shortnames to reset.</li>

<p>Additional fields can be added for more specific control for modules:
<b>forum_all, forum_subscriptions, glossary_all, chat, data, slots (scheduler), apointments, assignment_submissions,
assign_submissions, survey_answers, lesson, choice, scorm, quiz_attempts</b></p>

';

$string['userformat'] = 'User creation/update/deletion file format.';
$string['userformat_help'] = '
User definition file must be in ISO or UTF-8 format depending on Sync Tool settings.
The first line must hold column titles in any order.

<p>Mandatory fields: <b>username, firstname, lastname</b></p>

<p>Optional fields: <b>idnumber, email, auth, icq, phone1, phone2, address, url, description, mailformat,
maildisplay, htmleditor, autosubscribe, cohort, cohortid, course1, group1, type1, role1, enrol1, start1,
end1, wwwroot1, password, oldusername</b></p>

<p>Patterns are groups of fieldnames that should be used alltogether in an indexed form (fieldname<n>).</p>

<p>Enrolment pattern: <b>course, group, type, role, enrol, start, end, wwwroot</b>. This pattern will allow
enrol and setup user\'s course access in several courses. You may use several pattern instances numbered 1,
2? 3 etc. Leave all values blank for an unused pattern.</p>

<p>Additionnaly you may use additional special fields for adding values in custome profile fields. The general
form of those fiedls is: <i>user_profile_xxxxx</i></p>
';

$string['enrolformat'] = 'Enrol sync file format';
$string['enrolformat_help'] = '
Enrol cvs file is a CSV UTF-8 or ISO encoded (depending on tool configuration) file that automates enrol
constructions in Moodle.

<p>Mandatory fields: <b>rolename, uid, cid</b></p>

<li><i>rolename</i>: the shortname of the role</li>
<li><i>uid</i>: The relevant user id, depending on settings selection.</li>
<li><i>cid</i>: The relevant course id, depending on settings selection.</li>

<p>Optional fields: <b>hidden, starttime, endtime, enrol, cmd, g1 to g9</b></p>

<li><i>cmd</i>: implicitely \'add\', but could be \'del\' for enrolment deletion. \'shift\' will delete all
old roles and set this unique role.</li>
<li><i>hidden:</i></li>
<li><i>starttime, endtime</i>: Should be linux time stamps.</li>
<li><i>enrol</i>: the enrolment method (manual, mnet, cohort, etc...). If column not set, only role assignements
will be added.</li>
<li><i>gcmd</i>: \'gadd\' or \'gaddcreate\', \'greplace\' or \'greplacecreate\', but could be \'gdel\' for group
membership deletion</li>
<li><i>g1 to g9</i>: up to 9 goupnames the enrolled user will be member of. The group is created if missing and
using a \'gaddcreate\' or a \'greplacecreate\'.

';

$string['cohortformat'] = 'Cohort sync file format';
$string['cohortformat_help'] = '
Cohort creation/update file must be in ISO or UTF-8 format depending on Sync Tool settings.
The first line must hold column titles in any order.

<p>Mandatory fields: <b>cohortid, userid</b></p>

<li><i>cohortid</i>: An identifier, depending on Sync Tools settings. Can be cohort internal id, name or idnumber.</li>
<li><i>userid</i>: A primary identifier for the user. Can be internal id, username, email or idnumber.</li>

<p>Optional fields: <b>cdescription, cidnumber</b></p>

<li><i>cdescription</i>: If cohort needs to be created, a textuel description for it.</li>
<li><i>cidnumber</i>: If cohort needs to be created, the id number. In that case, should the primary cohort id be choosen as \'name\'.</li>
';

$string['userpicturesformat'] = 'User pictures file format';
$string['userpicturesformat_help'] = '
The User Pictures feeding file must be a zip with png, jpg or gif images for users, named using their primary identifier.
';

$string['passwordnotification_tpl'] = '
A password has been created for you: {$a}
';

$string['allowrename_help'] = 'If checked, username can be changed. an "oldusername" column needs to be present to match the old identity.';

$string['protectemails_help'] = 'If checked, import will not change user emails when they have one in their account. Missing emails will be completed from the file data.';

$string['createpasswords_help'] = 'If checked, missing password will be created when password column is missing.';

$string['sendpasswordtousers_help'] = 'If checked and passwords are incoming from the user creation file, passwords will be notified to users.';
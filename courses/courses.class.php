<?php
// The following flags are set in the configuration
// $CFG->course_filedeletelocation:       where is the file which delete courses we are looking for?
// $CFG->course_fileuploadlocation:       where is the file which upload courses we are looking for?
// author - Funck Thibaut !

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/courses/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/sync_manager.class.php');

class course_sync_manager extends sync_manager {

    private $manualfilerec;

    private $identifieroptions;

    public $execute;

    function __construct($execute = SYNC_COURSE_CREATE_DELETE, $manualfilerec = null) {
        $this->manualfilerec = $manualfilerec;
        $this->execute = $execute;
        $this->identifieroptions = array('0' => 'idnumber', '1' => 'shortname', '2' => 'id');
    }

    function form_elements(&$frm) {
        global $CFG;

        $frm->addElement('text', 'tool_sync/course_fileuploadlocation', get_string('uploadcoursecreationfile', 'tool_sync'));
        $frm->setType('tool_sync/course_fileuploadlocation', PARAM_TEXT);

        $frm->addElement('text', 'tool_sync/course_filedeletelocation', get_string('coursedeletefile', 'tool_sync'));
        $frm->setType('tool_sync/course_filedeletelocation', PARAM_TEXT);

        $frm->addElement('select', 'tool_sync/course_filedeleteidentifier', get_string('deletefileidentifier', 'tool_sync'), $this->identifieroptions);

        $frm->addElement('text', 'tool_sync/course_fileexistlocation', get_string('existcoursesfile', 'tool_sync'));
        $frm->setType('tool_sync/course_fileexistlocation', PARAM_TEXT);

        $frm->addElement('select', 'tool_sync/course_existfileidentifier', get_string('existfileidentifier', 'tool_sync'), $this->identifieroptions);

        $frm->addElement('text', 'tool_sync/course_fileresetlocation', get_string('resetfile', 'tool_sync'));
        $frm->setType('tool_sync/course_fileresetlocation', PARAM_TEXT);

        $frm->addElement('select', 'tool_sync/course_fileresetidentifier', get_string('resetfileidentifier', 'tool_sync'), $this->identifieroptions);


        $rarr = array();
        $rarr[] =& $frm->createElement('radio', 'tool_sync/forcecourseupdate', '', get_string('yes'), 1);
        $rarr[] =& $frm->createElement('radio', 'tool_sync/forcecourseupdate', '', get_string('no'), 0);
        $frm->addGroup($rarr, 'courseupdategroup', get_string('syncforcecourseupdate', 'tool_sync'), array('&nbsp;&nbsp;&nbsp;&nbsp;'), false);

        $frm->addElement('static', 'coursesst1', '<hr>');

        $barr = array();
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/deletecourses_creator.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('makedeletefile', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/resetcourses_creator.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('makeresetfile', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/checkcourses.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('testcourseexist', 'tool_sync'), $attribs);
        $frm->addGroup($barr, 'utilities', get_string('utilities', 'tool_sync'), array('&nbsp;&nbsp;'), false);

        $frm->addElement('static', 'coursesst2', '<hr>');

        $barr = array();
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/resetcourses.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('reinitialisation', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/synccourses.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('manualuploadrun', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/deletecourses.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('manualdeleterun', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/clearemptycategories.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('manualcleancategories', 'tool_sync'), $attribs);
        $attribs = array('onclick' => 'document.location.href= \''.$CFG->wwwroot.'/admin/tool/sync/courses/execcron.php\'');
        $barr[] =& $frm->createElement('button', 'manualusers', get_string('executecoursecronmanually', 'tool_sync'), $attribs);

        $frm->addGroup($barr, 'manualcourses', get_string('manualhandling', 'tool_sync'), array('&nbsp;&nbsp;'), false);

    }

    function cron($syncconfig) {
        global $CFG, $USER, $DB;

        define('TOPIC_FIELD','/^(topic)([0-9]|[1-4][0-9]|5[0-2])$/');
        define('TEACHER_FIELD','/^(teacher)([1-9]+\d*)(_account|_role)$/');

        // process files

        $this->report('Starting...');

        /* ****** Launching reset Files tool ****** */

        if ($this->execute & SYNC_COURSE_RESET) {

            $this->report(get_string('startingreset', 'tool_sync'));

            $text = '';

            // Get file rec to process depending on something has been provided for immediate processing
            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file($syncconfig->course_fileresetlocation, 'resetcourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            $identifiername = $this->identifieroptions[$syncconfig->course_fileresetidentifier];

            if ($filereader = $this->open_input_file($filerec)) {
                $required = array(
                        $identifiername => 1,
                        'events' => 1,
                        'logs' => 1,
                        'notes' => 1,
                        'grades' => 1,
                        'roles' => 1,
                        'groups' => 1,
                        'modules' => 1);
                $optional = array(
                        'forum_all' => 1,
                        'forum_subscriptions' => 1,
                        'glossary_all' => 1, /*glossary*/
                        'chat' => 1, /*chat*/
                        'data' => 1, /*database*/
                        'slots' => 1, /*scheduler*/
                        'apointments' => 1,
                        'assignment_submissions' => 1, /*assignment*/
                        'survey_answers' => 1, /*survey*/
                        'lesson' => 1, /*lesson*/
                        'choice' => 1,
                        'scorm' => 1,
                        'quiz_attempts' => 1);

                if ($allmods = $DB->get_records('modules') ) {
                    foreach ($allmods as $mod) {
                        $modname = $mod->name;
                        $modfile = $CFG->dirroot."/mod/{$modname}/resetlib.php";
                        $mod_reset_course_form_definition = $modname.'_reset_course_form_definition';
                        if (file_exists($modfile)) {
                            include_once($modfile);
                            if (function_exists($mod_reset_course_form_definition)) {
                                $vars = $mod_reset_course_form_definition();
                                foreach($vars as $var){
                                    $optional[$var] = 1;
                                }
                            }
                        }
                    }
                }

                $text = fgets($filereader, 1024);

                $i = 0;

                // skip comments and empty lines
                while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                    $text = fgets($filereader, 1024);
                    $i++;
                    continue;
                }
                $header = explode($syncconfig->csvseparator, $text);
        
                function trim_elements(&$e){
                    $e = trim($e); // remove whitespaces
                }

                array_walk($header, 'trim_elements');

                foreach ($header as $h) {
                    if (!isset($required[$h]) and !isset($optional[$h])) {
                        $this->report(get_string('invalidfieldname', 'error', $h));
                        return;
                    }
                    if (isset($required[$h])) {
                        $required[$h] = 0;
                    }
                }
                foreach ($required as $key => $value) {
                    if ($value) { //required field missing
                        $this->report(get_string('fieldrequired', 'error', $key));
                        return;
                    }
                }
                while (!feof ($filereader)) {
                    $text = fgets($filereader, 1024);

                    if (tool_sync_is_empty_line_or_format($text, false)) {
                        continue;
                    }

                    $line = explode($CFG->tool_sync_csvseparator, $text);
                    foreach ($line as $key => $value) {
                        // Decode encoded commas.
                        $record[$header[$key]] = trim($value);
                    }
                    $data['reset_start_date'] = 0;
        
                    // Adaptative identifier
        
                    if (@$syncconfig->course_resetfileidentifier == 0 && $DB->count_records('course', array('idnumber' => $record['idnumber']))) {
                        $this->report(get_string('nonuniqueidentifierexception', 'tool_sync', $i));
                        continue;
                    }
        
                    if ($course = $DB->get_record('course', array($identifiername => $record[$identifiername])) ) {
                        $data['id'] = $course->id;
                        $data['reset_start_date_old'] = $course->startdate;
                    } else {
                        $this->report(get_string('unkownshortname', 'tool_sync', $i));
                        continue;
                    }
                    $this->report(get_string('resettingcourse', 'tool_sync').$course->fullname.' ('.$course->shortname.')');

                    // processing events
                    if ($record['events'] == 'yes') {
                        $data['reset_events'] = 1;
                    } else {
                        $this->report(get_string('noeventstoprocess', 'tool_sync', $i), false);
                    }

                    // Processing logs
                    if ($record['logs'] == 'yes') {
                        $data['reset_logs'] = 1;
                    } else {
                        $this->report(get_string('nologstoprocess', 'tool_sync', $i), false);
                    }
                    // Processing notes
                    if ($record['notes'] == 'yes') {
                        $data['reset_notes'] = 1;
                    } else {
                        $this->report(get_string('nonotestoprocess', 'tool_sync', $i), false);
                    }

                    // Processing grades.
                    if ($record["grades"] == 'items') {
                        $data['reset_gradebook_items'] = 1;
                    } else if ($record['grades'] == 'grades'){
                        $data['reset_gradebook_grades'] = 1;
                    } else {
                        $this->report(get_string('nogradestoprocess', 'tool_sync', $i));
                    }
                    // processing role assignations
                    $roles = explode(' ', $record['roles']);
                    $reset_roles = array();
                    $nbrole = 0;
                    foreach($roles as $rolename) {
                        if ($role = $DB->get_record('role', array('shortname' => $rolename))) {
                            $reset_roles[$nbrole] = $role->id;
                            $data['reset_roles'] = $reset_roles;
                            $nbrole++;
                        } else {
                            $this->report("[Error] role $rolename unkown.\n");
                        }
                    }
                    // Processing groups.
                    if ($record['groups'] == 'groups') {
                        $data['reset_groups_remove'] = 1;
                    } else if ($record['groups'] == 'members'){
                        $data['reset_groups_members'] = 1;
                    } else {
                        $this-Sreport(get_string('nogrouptoprocess', 'tool_sync', $i));
                    }
        
                    echo '<br/>';
        
                    // processing course modules
                    if ($allmods = $DB->get_records('modules') ) {
                        $modmap = array();
                        $modlist = array();
                        $allmodsname = array();
                        foreach ($allmods as $mod) {
                            $modname = $mod->name;
                            $allmodsname[$modname] = 1;
                            if (!$DB->count_records($modname, array('course' => $data['id']))) {
                                continue; // skip mods with no instances
                            }
                            $modlist[$modname] = 1;
                            $modfile = $CFG->dirroot."/mod/$modname/lib.php";
                            $mod_reset_course_form_definition = $modname.'_reset_course_form_defaults';
                            $mod_reset_userdata = $modname.'_reset_userdata';
                            if (file_exists($modfile)) {
                                include_once($modfile);
                                if (function_exists($mod_reset_course_form_definition)) {
                                    $modmap[$modname] = $mod_reset_course_form_definition($data['id']);
                                } else if (!function_exists($mod_reset_userdata)) {
                                    $unsupported_mods[] = $mod;
                                }
                            } else {
                                debugging('Missing lib.php in '.$modname.' module');
                            }
                        }
                    }
                    $avalablemods = array();
                    foreach ($modmap as $modname => $mod) {
                        foreach ($mod as $key => $value) {
                            $avalablemods[$modname][$key] = $value;
                        }
                    }
                    if (count($header) > 8){
                        if((isset($record['forum_all']))&&($record['forum_all'] == 1)){
                            $data['reset_forum_all'] = 1;    
                        }
                        if((isset($record['forum_subscriptions'])) && ($record['forum_subscriptions'] == 1)){
                            $data['reset_forum_subscriptions'] = 1;    
                        }
                        if((isset($record['glossary_all'])) && ($record['glossary_all'] == 1)){
                            $data['reset_glossary_all'] = 1;    
                        }
                        if((isset($record['chat'])) && ($record['chat'] == 1)){
                            $data['reset_chat'] = 1;    
                        }
                        if((isset($record['data'])) && ($record['data'] == 1)){
                            $data['reset_data'] = 1;    
                        }
                        if((isset($record['slots']))&&($record['slots'] == 1)){
                            $data['reset_slots'] = 1;    
                        }
                        if((isset($record['apointments'])) && ($record['apointments'] == 1)){
                            $data['reset_apointments'] = 1;    
                        }
                        if((isset($record['assignment_submissions'])) && ($record['assignment_submissions'] == 1)){
                            $data['reset_assignment_submissions'] = 1;    
                        }
                        if((isset($record['survey_answers'])) && ($record['survey_answers'] == 1)){
                            $data['reset_survey_answers'] = 1;    
                        }
                        if((isset($record['lesson']))&&($record['lesson'] == 1)){
                            $data['reset_lesson'] = 1;    
                        }
                        if((isset($record['choice']))&&($record['choice'] == 1)){
                            $data['reset_choice'] = 1;    
                        }
                        if((isset($record['scorm'])) && ($record['scorm'] == 1)){
                            $data['reset_scorm'] = 1;    
                        }
                        if((isset($record['quiz_attempts'])) && ($record['quiz_attempts'] == 1)){
                            $data['reset_quiz_attempts'] = 1;    
                        }
                    } else {
                        $mods = explode(' ', $record['modules']);
                        $nbmods = 0;
                        $modlist = array();
                        foreach ($mods as $mod) {
                            $modlist[$mod] = 1;
                        }
                        if (isset($modlist['all']) && ($modlist['all'] == 1)) {
                            foreach ($avalablemods as $modname => $fcts) {
                                foreach ($fcts as $fct => $value) {
                                    $data[$fct]=$value;
                                }
                            }
                        } else {
                            if (!isset($modlist[''])) {
                                $neg = 0;
                                $negmods = array();
                                foreach ($modlist as $mymod => $value) {
                                    if ($mymod[0] == '-') {
                                        $neg = 1;
                                        $modnam = substr($mymod, 1);
                                        $negmods[$modnam] = 1;
                                    }
                                }
                                if ($neg == 1) {
                                    foreach ($avalablemods as $modname => $fcts) {
                                        foreach ($fcts as $fct => $value) {
                                            $data[$fct] = $value;
                                        }
                                    }
                                    foreach ($negmods as $k => $v) {
                                        foreach ($avalablemods as $mod => $fcts) {
                                            if ($k == $mod) {
                                                foreach ($fcts as $fct => $value) {
                                                    unset($data[$fct]);
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($neg == 0) {
                                    foreach ($avalablemods as $modname => $fcts) {
                                        if (isset($modlist[$modname]) && ($modlist[$modname] == 1)) {
                                            foreach ($fcts as $fct => $value) {
                                                $data[$fct] = $value;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $data = (object)$data;

                    $status = reset_course_userdata($data);
                    
                    print_object($status);

                    // array operation ligne par ligne avec array component / item / error
                    $this->report("Summary:", false);
                    foreach ($status as $line) {

                        $str = $line['component'] ." : ";
                        if (!empty($line['item'])) {
                            $str .= $line['item'] ." : ";
                        }
                        if (empty($line['error'])) {
                            $str .= 'OK';
                        } else {
                            $str .= $line['error'];
                        }
                        $this->report($str);
                    }
                }
            }
        }

        /* ****** Launching check Files tool ****** */

        if ($this->execute & SYNC_COURSE_CHECK) {

            $this->report(get_string('startingcheck', 'tool_sync'));

            $text = '';

            // Get file rec to process depending on somethoing has been provided for immediate processing
            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file($syncconfig->course_fileexistlocation, 'courses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;
    
                $identifieroptions = array('idnumber', 'shortname', 'id');
                $identifiername = $identifieroptions[0 + @$syncconfig->course_existfileidentifier];

                while (!feof($filereader)) {

                    $text = fgets($filereader, 1024);

                    // skip comments and empty lines
                    if (tool_sync_is_empty_line_or_format($text, $i == 0)) {
                        continue;
                    }

                    $valueset = explode($syncconfig->csvseparator, $text);

                    $size = count($valueset);

                    $c = new StdClass();
                    $c->$identifiername = $valueset[0];
                    if ($size == 2) {
                        $c->description = $valueset[1];
                    }
                    $course = $DB->get_record('course', array($identifiername => $c->$identifiername));

                    // give report on missing courses
                    if (!$course) {
                        $this->report(get_string('coursenotfound2', 'tool_sync', $course));
                    } else {
                        $this->report(get_string('coursefoundas', 'tool_sync', $course));
                    }
                    $i++;
                }
                fclose($filereader);
            }
        }

        /* ******************** delete (clean) courses ********************** */

        if ($this->execute & SYNC_COURSE_DELETE) {

            if (empty($this->manualfilerec)) {
                $filerec = $this->get_input_file($syncconfig->course_filedeletelocation, 'deletecourses.csv');
            } else {
                $filerec = $this->manualfilerec;
            }

            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;
                $shortnames = array();

                while (!feof($filereader)) {
                    $text = fgets($filereader);
                    // Skip comments and empty lines.
                    if (tool_sync_is_empty_line_or_format($text, $i)) {
                        continue;
                    }
                    $identifiers[] = $text;
                }
                
                // Fill this with a list of comma seperated id numbers to delete courses.
                $deleted = 0;
                $identifieroptions = array('idnumber', 'shortname', 'id');
                $identifiername = $identifieroptions[0 + @$syncconfig->course_filedeleteidentifier];

                foreach ($identifiers as $cid) {
                    if (!($c = $DB->get_record('course', array($identifiername => $cid)))) {
                        $this->report(get_string('coursenotfound', 'tool_sync', $cid));
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($text);
                        }
                        $i++;
                        continue;
                    }

                    if (delete_course($c->id, false)) {
                        $deleted++;
                        $this->report(get_string('coursedeleted', 'tool_sync', $cid));
                    }
                }
                if ($deleted) {
                    fix_course_sortorder();
                }
            }
            fclose($filereader);

            if (!empty($syncconfig->filefailed)) {
                $this->write_tryback($filerec);
            }
            if (!empty($syncconfig->filearchive)) {
                $this->archive_input_file($filerec);
            }
            if (!empty($syncconfig->filecleanup)) {
                $this->cleanup_input_file($filerec);
            }
        }

        /* *************** update/create courses *********************** */

        if ($this->execute & SYNC_COURSE_CREATE) {

            // Make arrays of fields for error checking.
            $defaultcategory = $this->get_default_category();
            $defaultmtime = time();

            $required = array(  'fullname' => false, // Mandatory fields
                                'shortname' => false);

            $optional = array(  'category' => $defaultcategory, // Default values for optional fields
                                'sortorder' => 0,
                                'summary' => get_string('coursedefaultsummary', 'tool_sync'),
                                'format' => 'topics',
                                'idnumber' => '',
                                'showgrades' => 1,
                                'newsitems' => 5,
                                'startdate' => $defaultmtime,
                                'marker' => 0,
                                'maxbytes' => 2097152,
                                'legacyfiles' => 0,
                                'showreports' => 0,
                                'visible' => 1,
                                'visibleold' => 0,
                                'groupmode' => 0,
                                'groupmodeforce' => 0,
                                'defaultgroupingid' => 0,
                                'lang' => '',
                                'theme' => '',
                                'timecreated' => $defaultmtime,
                                'timemodified' => $defaultmtime,
                                'self' => 0, // special processing adding a self enrollment plugin instance
                                'guest' => 0, // special processing adding a guest enrollment plugin instance
                                'template' => '');

            // TODO : change default format from weeks to course default options
            $validate = array(  'fullname' => array(1,254,1), // Validation information - see validate_as function
                                'shortname' => array(1,15,1),
                                'category' => array(5),
                                'sortorder' => array(2,4294967295,0),
                                'summary' => array(1,0,0),
                                'format' => array(4,'social,topics,weeks,page,flexpage,activity'),
                                'showgrades' => array(4,'0,1'),
                                'newsitems' => array(2,10,0),
                                'legacyfiles' => array(4,'0,1'),
                                'marker' => array(3),
                                'startdate' => array(3),
                                'maxbytes' => array(2,$CFG->maxbytes,0),
                                'visible' => array(4,'0,1'),
                                'visibleold' => array(4,'0,1'),
                                'groupmode' => array(4,NOGROUPS.','.SEPARATEGROUPS.','.VISIBLEGROUPS),
                                'timecreated' => array(3),
                                'timemodified' => array(3),
                                'idnumber' => array(1,100,0),
                                'groupmodeforce' => array(4,'0,1'),
                                'lang' => array(1,50,0),
                                'theme' => array(1,50,0),
                                'showreports' => array(4,'0,1'),
                                'guest' => array(4,'0,1'),
                                'template' => array(1,0,0),
                                'topic' => array(1,0,0),
                                'teacher_account' => array(6,0),
                                'teacher_role' => array(1,40,0));

            $filerec = $this->get_input_file($syncconfig->course_filedeletelocation, 'deletecourses.csv');
            if ($filereader = $this->open_input_file($filerec)) {

                $i = 0;

                while (!feof($filereader)) {
                    $text = fgets($filereader, 1024);
                    if (!tool_sync_is_empty_line_or_format($text, $i == 0)) {
                        break;
                    }
                    $i++;
                }

                $header = explode($syncconfig->csvseparator, $text);

                // check for valid field names

                function trim_values(&$e) {
                    $e = trim($e);
                }

                array_walk($header, 'trim_values');

                foreach ($header as $h) {
                    if (empty($h)) {
                        $this->report(get_string('errornullcsvheader', 'tool_sync'));
                        return;
                    }
                    if (preg_match(TOPIC_FIELD, $h)) { // Regex defined header names
                    } elseif (preg_match(TEACHER_FIELD, $h)) {
                    } else {
                        if (!(isset($required[$h]) || isset($optional[$h]))) { 
                            $this->report(get_string('errorinvalidfieldname', 'tool_sync', $h));
                            return;
                        }

                        if (isset($required[$h])) {
                            $required[$h] = true; 
                        }
                    }
                }

                // check for required fields
                foreach ($required as $key => $value) {
                    if ($value != true) {
                        $this->report(get_string('fieldrequired', 'error', $key));
                        return;
                    }
                }

                $fieldcount = count($header);

                unset($bulkcourses);
                $courseteachers = array();

                // start processing lines

                while (!feof($filereader)) {
                    $text = fgets($filereader, 1024);

                    if (tool_sync_is_empty_line_or_format($text)) {
                        $i++;
                        continue;
                    }

                    $valueset = explode($syncconfig->csvseparator, $text);

                    if (count($valueset) != $fieldcount) {
                           $e->i = $i;
                           $e->count = count($valueset);
                           $e->expected = $fieldcount;
                        $this->report(get_string('errorbadcount', 'tool_sync', $e));
                        if (!empty($syncconfig->filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $text, null);
                        $i++;
                        continue;
                    }

                    unset($coursetocreate);
                    unset($coursetopics);
                    unset($courseteachers);

                    // Set course array to defaults
                    foreach ($optional as $key => $value) { 
                        $coursetocreate[$key] = $value;
                    }

                    $coursetopics = array();

                    // Validate incoming values
                    foreach ($valueset as $key => $value) { 
                        $cf = $header[$key];

                        if (preg_match(TOPIC_FIELD, $cf, $matches)) {
                              $coursetopics[$matches[2]] = $this->validate_as($value, $matches[1], $i, $cf);
                        } elseif (preg_match(TEACHER_FIELD, $cf, $matches)) {
                              $tmp = $this->validate_as(trim($value), $matches[1].$matches[3], $i, $cf);
                              (isset($tmp) && ($tmp != '')) and ($courseteachers[$matches[2]][$matches[3]] = $tmp);
                        } else {
                            $coursetocreate[$cf] = $this->validate_as($value, $cf, $i); // Accept value if it passed validation
                        }
                    }
                    $coursetocreate['topics'] = $coursetopics;

                    if (isset($courseteachers)) {
                        foreach ($courseteachers as $key => $value) { // Deep validate course teacher info on second pass
                              if (isset($value) && (count($value) > 0)) {
                                if (!(isset($value['_account']) && $this->check_is_in($value['_account']))) {
                                    $e->i = $i;
                                    $e->key = $key;
                                    $this->report(get_string('errornoteacheraccountkey', 'tool_sync', $e));
                                      continue;
                                  }
                                // Hardcoded default values (that are as close to moodle's UI as possible)
                                // and we can't assume PHP5 so no pointers!
                                if (!isset($value['_role'])) {
                                    $courseteachers[$key]['_role'] = '';
                                }
                              }
                          }
                    } else {
                        $courseteachers = array();
                    }
                    $coursetocreate['teachers_enrol'] = $courseteachers;
                    $bulkcourses["$i"] = $coursetocreate; // Merge into array
                    $sourcetext["$i"] = $text; // Save text line for futher reference
                    $i++;
                }
                fclose($filereader);
            } else {
                $this->report(get_string('erroropeningfile', 'tool_sync'));
            }

            if (empty($bulkcourses)) {
                $this->report(get_string('errornocourses', 'tool_sync'));
                return;
            }

            /// All validation is over. Starting the course creation process

            // Running Status Totals

            $t = 0; // Read courses
            $s = 0; // Skipped courses
            $n = 0; // Created courses
            $p = 0; // Broken courses (failed halfway through
            
            $cat_e = 0; // Errored categories
            $cat_c = 0; // Created categories

            foreach ($bulkcourses as $i => $bulkcourse) {
                $a = new StdClass;
                $a->shortname = $bulkcourse['shortname'];
                $a->fullname = $bulkcourse['fullname'];

                // Try to create the course.
                if (!$oldcourse = $DB->get_record('course', array('shortname' => $bulkcourse['shortname']))) {

                    $coursetocategory = 0; // Category ID

                    if (is_array($bulkcourse['category'])) {
                        // Course Category creation routine as a category path was given.

                        $curparent = 0;
                        $curstatus = 0;
        
                        foreach ($bulkcourse['category'] as $catindex => $catname) {
                              $curparent = $this->fast_get_category_ex($catname, $curstatus, $curparent);
                            switch ($curstatus) {
                                  case 1: // Skipped the category, already exists.
                                      break;
                                  case 2: // Created a category.
                                    $cat_c++;
                                      break;
                                  default:
                                    $cat_e += count($bulkcourse['category']) - $catindex;
                                    $coursetocategory = -1;
                                    $e = new StdClass;
                                    $e->catname = $catname;
                                    $e->failed = $cat_e;
                                    $e->i = $i;
                                    $this->report(get_string('errorcategorycreate', 'tool_sync', $e));
                                    if (!empty($syncconfig->filefailed)) {
                                        $this->feed_tryback($sourcetext[$i]);
                                    }
                                    continue;
                            }
                        }
                        ($coursetocategory == -1) or $coursetocategory = $curparent;
                        // Last category created will contain the actual course
                    } else {
                        // It's just a straight category ID
                        $coursetocategory = (!empty($bulkcourse['category'])) ? $bulkcourse['category'] : -1 ;
                    }

                    if ($coursetocategory == -1) {
                        $e = new StdClass;
                        $e->i = $i;
                        $e->coursename = $bulkcourse['shortname'];
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($sourcetext[$i]);
                        }
                        $this->report(get_string('errorcategoryparenterror', 'tool_sync', $e));
                        continue;
                    } else {
                        $result = $this->fast_create_course_ex($coursetocategory, $bulkcourse, $header, $validate);
                        $e = new StdClass;
                        $e->coursename = $bulkcourse['shortname'];
                        $e->i = $i;
                        switch ($result) {
                            case 1:
                                $this->report(get_string('coursecreated', 'tool_sync', $a));
                                $n++; // Succeeded
                            break;
                            case -3:
                                $this->report(get_string('errorsectioncreate', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -4:
                                $this->report(get_string('errorteacherenrolincourse', 'tool_sync', $e));
                                if (!empty($CFG->sync_filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -5:
                                $this->report(get_string('errorteacherrolemissing', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -6:
                                $this->report(get_string('errorcoursemisconfiguration', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -7:
                                $e->template = $bulkcourse['template'];
                                $this->report(get_string('errortemplatenotfound', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            case -8:
                                $this->report(get_string('errorrestoringtemplatesql', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                                $p++;
                            break;
                            default:
                                $this->report(get_string('errorrestoringtemplate', 'tool_sync', $e));
                                if (!empty($syncconfig->filefailed)) {
                                    $this->feed_tryback($sourcetext[$i]);
                                }
                            break;
                        }
                      }
                } else {
                    if (!empty($syncconfig->forcecourseupdate)) {

                        $coursetocategory = 0;

                        if (is_array($bulkcourse['category'])) {
                            // Course Category creation routine as a category path was given

                            $curparent = 0;
                            $curstatus = 0;

                            foreach ($bulkcourse['category'] as $catindex => $catname) {
                                  $curparent = $this->fast_get_category_ex($catname, $curstatus, $curparent);
                                switch ($curstatus) {
                                      case 1: // Skipped the category, already exists
                                          break;
                                      case 2: // Created a category
                                        $cat_c++;
                                          break;
                                      default:
                                        $cat_e += count($bulkcourse['category']) - $catindex;
                                        $coursetocategory = -1;
                                        $e = new StdClass;
                                        $e->catname = $catname;
                                        $e->failed = $cat_e;
                                        $e->i = $i;
                                        $this->report(get_string('errorcategorycreate', 'tool_sync', $e));
                                        if (!empty($syncconfig->filefailed)) {
                                            $this->feed_tryback($sourcetext[$i]);
                                        }
                                          continue;
                                }
                            }
                            ($coursetocategory == -1) or $coursetocategory = $curparent;
                            // Last category created will contain the actual course
                        } else {
                            // It's just a straight category ID
                            $coursetocategory = (!empty($bulkcourse['category'])) ? $bulkcourse['category'] : -1 ;
                        }

                        if ($coursetocategory == -1) {
                            $e = new StdClass;
                            $e->i = $i;
                            $e->coursename = $oldcourse->shortname;
                            if (!empty($syncconfig->filefailed)) {
                                $this->feed_tryback($sourcetext[$i]);
                            }
                            $this->report(get_string('errorcategoryparenterror', 'tool_sync', $e));
                            continue;
                        } else {
                            $oldcourse->category = $coursetocategory;
                        }

                        foreach($bulkcourse as $key => $value) {
                            if (isset($oldcourse->$key) && $key != 'id' && $key != 'category') {
                                $oldcourse->$key = $value;
                            }
                        }
                        if ($DB->update_record('course', $oldcourse)) {
                            $e = new StdClass;
                            $e->i = $i;
                            $e->shortname = $oldcourse->shortname;
                            $this->report(get_string('courseupdated', 'tool_sync', $e));
                        } else {
                            $e = new StdClass;
                            $e->i = $i;
                            $e->shortname = $oldcourse->shortname;
                            $this->report(get_string('errorcourseupdated', 'tool_sync', $e));
                        }
                    } else {
                        $this->report(get_string('courseexists', 'tool_sync', $a));
                          // Skip course, already exists
                    }
                      $s++;
                }
                $t++;
            }

            fix_course_sortorder(); // Re-sort courses

            if (!empty($syncconfig->filefailed)) {
                $this->write_tryback($filerec);
            }

            if (!empty($syncconfig->filearchive)) {
                $this->archive_input_file($filerec);
            }

            if (!empty($CFG->sync_filecleanup)) {
                $this->cleanup_input_file($filerec);
            }
        }
        
        // F.e. for course reset operation.
        if (isset($satus)) {
            return $status;
        }

        return true;
    }

    /**
    *
    */
    function get_default_category() {
        global $CFG, $USER, $DB;

        if (!$mincat = $DB->get_field('course_categories', 'MIN(id)', array())) {
            return 1; // *SHOULD* be the Misc category?
        }
        return $mincat;
    }

    /**
    *
    *
    *
    */
    function check_is_in($supposedint) {
        return ((string)intval($supposedint) == $supposedint) ? true : false;
    }

    /**
    *
    *
    */
    function check_is_string($supposedstring) {
        $supposedstring = trim($supposedstring); // Is it just spaces?
        return (strlen($supposedstring) == 0) ? false : true;
    }

    /**
    * Validates each field based on information in the $validate array
    *
    */
    function validate_as($value, $validatename, $lineno, $fieldname = '') {
        global $USER;
        global $CFG;
        global $validate;

        $validate = array(  'fullname' => array(1,254,1), // Validation information - see validate_as function
                            'shortname' => array(1,100,1),
                            'category' => array(5),
                            'sortorder' => array(2,4294967295,0),
                            'summary' => array(1,0,0),
                            'format' => array(4,'social,topics,weeks'),
                            'showgrades' => array(4,'0,1'),
                            'newsitems' => array(2,10,0),
                            'teacher' => array(1,100,1),
                            'teachers' => array(1,100,1),
                            'student' => array(1,100,1),
                            'students' => array(1,100,1),
                            'startdate' => array(3),
                            'numsections' => array(2,52,0),
                            'maxbytes' => array(2,$CFG->maxbytes,0),
                            'visible' => array(4,'0,1'),
                            'groupmode' => array(4,NOGROUPS.','.SEPARATEGROUPS.','.VISIBLEGROUPS),
                            'timecreated' => array(3),
                            'timemodified' => array(3),
                            'idnumber' => array(1,100,0),
                            'password' => array(1,50,0),
                            'enrolperiod' => array(2,4294967295,0),
                            'groupmodeforce' => array(4,'0,1'),
                            'metacourse' => array(4,'0,1'),
                            'lang' => array(1,50,0),
                            'theme' => array(1,50,0),
                            'cost' => array(1,10,0),
                            'showreports' => array(4,'0,1'),
                            'guest' => array(4,'0,1,2'),
                            'enrollable' => array(4,'0,1'),
                            'enrolstartdate' => array(3),
                            'enrolenddate' => array(3),
                            'notifystudents' => array(4,'0,1'),
                            'template' => array(1,0,0),
                            'expirynotify' => array(4,'0,1'),
                            'expirythreshold' => array(2,30,1), // Following ones cater for [something]N
                            'topic' => array(1,0,0),
                            'teacher_account' => array(6,0),
                            'teacher_role' => array(1,40,0));        

        if ($fieldname == '') {
            $fieldname = $validatename;
        }

        if (!isset($validate[$validatename])) {
            // we dont translate this > developper issue
            $errormessage = 'Coding Error: Unvalidated field type: "'.$validatename.'"';
            $this->report($errormessage);
            return;
        }

        $format = $validate[$validatename];

        switch($format[0]) {
            case 1: // String
                if (($maxlen = $format[1]) != 0) {  // Max length?
                    if (strlen($value) > $format[1]) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->length = $format[1];
                        $this->report(get_string('errorvalidationstringlength', 'tool_sync', $e));
                        return;
                    }
                }

                if ($format[2] == 1) { // Not null?
                    if (!$this->check_is_string($value)) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationempty', 'tool_sync', $e));
                        return;
                    }
                }
            break;

            case 2: // Integer
                if (!$this->check_is_in($value)) { 
                    $e = new StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationintegercheck', 'tool_sync', $e));
                    return;
                }

                if (($max = $format[1]) != 0) {  // Max value?
                    if ($value > $max) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->max = $max;
                        $this->report(get_string('errorvalidationintegerabove', 'tool_sync', $e));
                        return;
                    }
                }

                if (isset($format[2]) && !is_null($format[2])) {  // Min value
                    $min = $format[2];
                    if ($value < $min) { 
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->min = $min;
                        $this->report(get_string('errorvalidationintegerbeneath', 'tool_sync', $e));
                        return;
                    }
                }
            break;

            case 3: // Timestamp - validates and converts to Unix Time
                $value = strtotime($value);
                if ($value == -1) { // failure
                    $e = new StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationtimecheck', 'tool_sync', $e));
                    return;
                }
            break;

            case 4: // Domain
                $validvalues = explode(',', $format[1]);
                if (array_search($value, $validvalues) === false) {
                    $e = new StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $e->set = $format[1];
                    $this->report(get_string('errorvalidationvalueset', 'tool_sync', $e));
                    return;
                }
            break; 

            case 5: // Category
                if ($this->check_is_in($value)) {
                  // It's a Category ID Number
                    if (!$DB->record_exists('course_categories', array('id' => $value))) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->category = $value;
                        $this->report(get_string('errorvalidationcategoryid', 'tool_sync', $e));
                        return;
                    }
                } elseif ($this->check_is_string($value)) {
                       // It's a Category Path string
                       $value = trim(str_replace('\\','/',$value)," \t\n\r\0\x0B/");
                       // Clean path, ensuring all slashes are forward ones
                       if (strlen($value) <= 0) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationcategoryunpathed', 'tool_sync', $e));
                        return;
                    }

                    unset ($cats);
                    $cats = explode('/', $value); // Break up path into array

                    if (count($cats) <= 0) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->path = $value;
                        $this->report(get_string('errorvalidationcategorybadpath', 'tool_sync', $e));
                        return;
                    }

                    foreach ($cats as $n => $item) { // Validate the path

                          $item = trim($item); // Remove outside whitespace

                          if (strlen($item) > 100) { 
                            $e = new StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->item = $item;
                            $this->report(get_string('errorvalidationcategorylength', 'tool_sync', $e));
                              return;
                        }
                          if (!$this->check_is_string($item)) {
                            $e = new StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->value = $value;
                            $e->pos = $n + 1;
                            $this->report(get_string('errorvalidationcategorytype', 'tool_sync', $e));
                              return;
                        }
                    }

                    $value = $cats; // Return the array
                    unset ($cats);
                } else {
                    $e = new StdClass;
                    $e->i = $lineno;
                    $e->fieldname = $fieldname;
                    $this->report(get_string('errorvalidationbadtype', 'tool_sync', $e));
                    return;
                }
            break;

            case 6: // User ID or Name (Search String)
                $value = trim($value);
                if ($this->check_is_in($value)) { // User ID
                    if (!$DB->record_exists('user', array('id' => $value))) {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $e->value = $value;
                        $this->report(get_string('errorvalidationbaduserid', 'tool_sync', $e));
                        return;
                    }
                } elseif ($this->check_is_string($value)) { // User Search String
                    // Only PHP5 supports named arguments
                    $usersearch = get_users_listing('lastaccess', 'ASC', 0, 99999, mysql_real_escape_string($value), '', '');
                    if (isset($usersearch) and ($usersearch !== false) and is_array($usersearch) and (($ucountc = count($usersearch)) > 0)) {
                        if ($ucount > 1) {
                            $e = new StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $e->ucount = $ucount;
                            $this->report(get_string('errorvalidationmultipleresults', 'tool_sync', $e));
                            return;
                        }

                        reset($usersearch);

                        $uid = key($usersearch);

                        if (!$this->check_is_in($uid) || !$DB->record_exists('user', array('id' => $uid))) {
                            $e = new StdClass;
                            $e->i = $lineno;
                            $e->fieldname = $fieldname;
                            $this->report(get_string('errorvalidationsearchmisses', 'tool_sync', $e));
                            return;
                        }

                        $value = $uid; // Return found user id

                    } else {
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationsearchfails', 'tool_sync', $e));
                        return;
                    }
                } else {
                      if ($format[1] == 1) { // Not null?
                        $e = new StdClass;
                        $e->i = $lineno;
                        $e->fieldname = $fieldname;
                        $this->report(get_string('errorvalidationempty', 'tool_sync', $e));
                        return;
                    }
                }
            break; 

            default:
                // not translated
                $errormessage = 'Coding Error: Bad field validation type: "'.$fieldname.'"';
                $this->report($errormessage);
                return;
            break;
        }

        return $value;
    }

    function microtime_float() {
        // In case we don't have php5
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function fast_get_category_ex($hname, &$hstatus, $hparent = 0) {
        // Find category with the given name and parentID, or create it, in both cases returning a category ID
        /* $hstatus:
            -1  :   Failed to create category
            1   :   Existing category found
            2   :   Created new category successfully
        */
        global $CFG, $USER, $DB;

        // Check if a category with the same name and parent ID already exists
        if ($cat = $DB->get_field_select('course_categories', 'id', " name = ? AND parent = ? ", array($hname, $hparent))) {
            $hstatus = 1;
            return $cat;
        } else {
            if (!$parent = $DB->get_record('course_categories', array('id' => $hparent))) {
                $parent = new StdClass;
                $parent->path = '';
                $parent->depth = 0;
                $hparent = 0;
            }

            $cat = new StdClass;
            $cat->name = $hname;
            $cat->description = '';
            $cat->parent = $hparent;
            $cat->sortorder = 999;
            $cat->coursecount = 0;
            $cat->visible = 1;
            $cat->depth = $parent->depth + 1;
            $cat->timemodified = time();
            if ($cat->id = $DB->insert_record('course_categories', $cat)) {
                $hstatus = 2;

                // Must post update.
                $cat->path = $parent->path.'/'.$cat->id;
                $DB->update_record('course_categories', $cat);
                // We must make category context.
                create_contexts(CONTEXT_COURSECAT, $cat->id);
            } else {
                $hstatus = -1;
            }
            return $cat->id;
        }
    }

    // Edited by Ashley Gooding & Cole Spicer to fix problems with 1.7.1 and make easier to dynamically add new columns
    // We keep that old code till next work.
    function fast_create_course_ex($hcategory, $course, $header, $validate) { 
        global $CFG, $DB;

        if (!is_array($course) || !is_array($header) || !is_array($validate)) {
            return -2;
        }  

        // trap when template not found
        if (!empty($course['template'])) {
            if (!($tempcourse = $DB->get_record('course', array('shortname' => $course['template'])))) {
                return -7;
            }
        }

        // Dynamically Create Query Based on number of headings excluding Teacher[1,2,...] and Topic[1,2,...]
        // Added for increased functionality with newer versions of moodle
        // Author: Ashley Gooding & Cole Spicer

        $courserec = (object)$course;
        $courserec->category = $hcategory;
        unset($courserec->template);

        foreach ($header as $i => $col) {
            $col = strtolower($col);
            if (preg_match(TOPIC_FIELD, $col) || preg_match(TEACHER_FIELD, $col) || $col == 'category') {
                continue;
            }
            if ($col == 'expirythreshold') {
                $courserec->$col = $course[$col]*86400;
            } else {
                $courserec->$col = $course[$col];
            }
        }

        if (!empty($course['template'])) {

            if (!$archivefile = tool_sync_locate_backup_file($tempcourse->id, 'course')) {

                // Get course template from publishflow backups if publishflow installed.
                if ($DB->get_record('blocks', array('name' => 'publishflow'))) {
                    $archivefile = tool_sync_locate_backup_file($tempcourse->id, 'publishflow');
                    if (!$archivefile) {
                        return -2;
                    }
                } else {
                    return -2;
                }
            }

            $uniq = uniqid();
                                
            $tempdir = $CFG->dataroot."/temp/backup/$uniq";
            if (!is_dir($tempdir)) {
                mkdir($tempdir, 0777, true);
            }
            // Unzip all content in temp dir.

            // Actually locally copying archive.
            $contextid = context_system::instance()->id;
            $component = 'tool_sync';
            $filearea = 'temp';
            $itemid = $uniq;
            if ($archivefile->extract_to_storage(new zip_packer(), $contextid, $component, $filearea, $itemid, $tempdir, $USER->id)) {    

                // Transaction
                $transaction = $DB->start_delegated_transaction();

                // Create new course
                $folder                 = $tempdir; // as found in: $CFG->dataroot . '/temp/backup/' 
                $categoryid             = $hcategory->id; // e.g. 1 == Miscellaneous
                $user_doing_the_restore = $USER->id; // e.g. 2 == admin
                $newcourse_id           = restore_dbops::create_new_course('', '', $hcategory->id );

                // Restore backup into course
                $controller = new restore_controller($folder, $newcourse_id, 
                        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $user_doing_the_restore,
                        backup::TARGET_NEW_COURSE );
                $controller->execute_precheck();
                $controller->execute_plan();

                // Commit
                $transaction->allow_commit();

                // and import
                if ($newcourse_id) {

                    // Add all changes from incoming courserec.
                    $newcourse = $DB->get_record('course', array('id' => $newcourse_id));
                    foreach ((array)$courserec as $field => $value) {
                        if ($field == 'format' || $field == 'id') {
                            continue; // protect sensible identifying fields
                        }
                        $newcourse->$field = $value;
                    }
                    if (!$DB->update_record('course', $newcourse)) {
                        mtrace('failed updating');
                    }
                } else {
                    return -2;
                }
            } else {
                return -2;
            }
        } else {
            // create default course
            $newcourse = create_course($courserec);
            $format = (!isset($course['format'])) ? 'topics' : $course['format'] ; // maybe useless
            if (isset($course['topics'])) { // Any topic headings specified ?
                $maxfilledtopics = 1;
                foreach ($course['topics'] as $dtopicno => $dtopicname) {
                    if (!empty($dtopicname)) $maxfilledtopics = $dtopicno; // we guess the max declared topic
                    if (strstr($dtopicname, '|') === false) {
                        $sectionname = $dtopicname;
                        $sectionsummary = '';
                    } else {
                        list($sectionname, $sectionsummary) = explode('|', $dtopicname);
                    }

                    if (!$sectiondata = $DB->get_record('course_sections', array('section' => $dtopicno, 'course' => $newcourse->id))) { 
                        // Avoid overflowing topic headings.
                        $csection = new StdClass;
                        $csection->course = $newcourse->id;
                        $csection->section = $dtopicno;
                        $csection->name = $sectionname;
                        $csection->summary = $sectionsummary;
                        $csection->sequence = '';
                        $csection->visible = 1;
                        if (!$DB->insert_record('course_sections', $csection)) {
                        }
                    } else {
                        $sectiondata->summary = $sectionname;
                        $sectiondata->name = $sectionsummary;
                        $DB->update_record('course_sections', $sectiondata);
                    }
                }
                if (!isset($course['topics'][0])) {
                    if (!$DB->get_record('course_sections', array('section' => 0, 'course' => $newcourse->id))) {
                        $csection = new StdClass;
                        $csection->course = $newcourse->id;
                        $csection->section = 0;
                        $csection->name = '';
                        $csection->summary = '';
                        $csection->sequence = '';
                        $csection->visible = 1;
                        if (!$DB->insert_record('course_sections', $csection)) {
                            return -3;
                        }
                    }
                }

                // finally we can bind the course to have $maxfilledtopics topics
                $new = 0;
                if (!$formatoptions = $DB->get_record('course_format_options', array('courseid' => $newcourse->id, 'name' => 'numsections', 'format' => $format))) {
                    $formatoptions = new StdClass();
                    $new = 1;
                }
                $formatoptions->courseid = $newcourse->id;
                $formatoptions->format = $format;
                $formatoptions->name = 'numsections';
                $formatoptions->section = 0;
                $formatoptions->value = $maxfilledtopics;
                if ($new) {
                    $DB->insert_record('course_format_options', $formatoptions);
                } else {
                    $DB->update_record('course_format_options', $formatoptions);
                }
            } else {
                $numsections = get_config('numsections', 'moodlecourse');
                for ($i = 1 ; $i < $numsections ; $i++) {
                    // use course default to reshape the course creation
                    $csection = new StdClass;
                    $csection->course = $newcourse->id;
                    $csection->section = $i;
                    $csection->name = '';
                    $csection->summary = '';
                    $csection->sequence = '';
                    $csection->visible = 1;
                    if (!$DB->insert_record('course_sections', $csection)) {
                    }
                }
            }
            rebuild_course_cache($newcourse->id, true);
        }
        if (!$context = context_course::instance($newcourse->id)) {
            return -6;
        }

        if (isset($course['teachers_enrol']) && (count($course['teachers_enrol']) > 0)) { 
            // Any teachers specified?
            foreach ($course['teachers_enrol'] as $dteacherno => $dteacherdata) {
                if (isset($dteacherdata['_account'])) {
                    $roleid = $DB->get_field('role', 'shortname', null);
                    $roleassignrec = new StdClass;
                    $roleassignrec->roleid = $roleid;
                    $roleassignrec->contextid = $context->id;
                    $roleassignrec->userid = $dteacherdata['_account'];
                    $roleassignrec->timemodified = $course['timecreated'];
                    $roleassignrec->modifierid = 0;
                    $roleassignrec->enrol = 'manual';
                    if (!$DB->insert_record('role_assignments', $roleassignrec)) {
                        return -4;
                    }
                }
            }
        }
        return 1;
    }

    public function clear_empty_categories($ids) {
        global $DB;

        $count = 0;

        list($usec, $sec) = explode(' ', microtime());
        $time_start = ((float)$usec + (float)$sec);

        $idarr = explode(',', $ids);
        foreach ($idarr as $id) {
            $deletedcat = $DB->get_record('course_categories', array('id' => $id));
            if ($DB->delete_records('course_categories', array('id' => $id))) {
                if (delete_context(CONTEXT_COURSECAT, $id)) {
                    $this->report(get_string('categoryremoved', 'tool_sync', $deletedcat->name));
                    $count++;
                } else {
                    $this->report(get_string('errorcategorycontextdeletion', 'tool_sync', $id));
                }
            } else {
                $this->report(get_string('errorcategorydeletion', 'tool_sync', $id));
            }
        }

        $this->report(get_string('ncategoriesdeleted', 'tool_sync', $count));
        // Show execute time
        list($usec, $sec) = explode(' ', microtime());
        $time_end = ((float)$usec + (float)$sec);
        $this->report(get_string('totaltime', 'tool_sync').' '.round(($time_end - $time_start),2).' s');
    }

    /*
     * Creates a default reinitialisation file with standard options
     * File is generated in UTF8 only
     * @param array $selection array of course IDs from selection form
     */
    function create_course_reinitialisation_file($selection, $syncconfig) {
        global $CFG, $DB;

        $fs = get_file_storage();

        $filename = 'resetcourses.csv';
        $size = count($selection);

        $rows = array();
        $cols = array('shortname', 'roles', 'grades', 'groups', 'events', 'logs', 'notes', 'modules');
        $rows[] = implode($syncconfig->csvseparator, $cols);

        $identifieroptions = array('idnumber', 'shortname', 'id');
        $identifiername = $identifieroptions[0 + @$syncconfig->course_resetfileidentifier];

        for ($i = 0 ; $i < count($selection) ; $i++) {

            if (@$syncconfig->course_resetfileidentifier == 0 && $DB->count_records('course', array('idnumber' => $selection[$i]))) {
                $this->report(get_string('nonuniqueidentifierexception', 'tool_sync', $i));
                continue;
            }

            $c = $DB->get_record('course', array($identifiername => $selection[$i]));
            $values = array();
            $values[] = $c->shortname;
            $values[] = 'student teacher guest';
            $values[] = 'grades';
            $values[] = 'members';
            $values[] = 'yes';
            $values[] = 'yes';
            $values[] = 'yes';
            $values[] = 'all';
            $rows[] = implode($syncconfig->csvseparator, $values);
        }
        $content = implode("\n", $rows); // We have at least one.

        $filerec = new StdClass();
        $filerec->contextid = context_system::instance()->id;
        $filerec->component = 'tool_sync';
        $filerec->filearea = 'syncfiles';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = $filename;

        // Ensure no collisions
        if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid, $filerec->filepath, $filerec->filename)) {
            $oldfile->delete();
        }

        $fs->create_file_from_string($filerec, $content);
    }
}
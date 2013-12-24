<?php
// The following flags are set in the configuration
// $CFG->course_filedeletelocation:       where is the file which delete courses we are looking for?
// $CFG->course_fileuploadlocation:       where is the file which upload courses we are looking for?
// author - Funck Thibaut !

include_once $CFG->dirroot.'/admin/tool/sync/lib.php';
include_once $CFG->dirroot.'/admin/tool/sync/courses/lib.php';

class courses_plugin_manager {

    var $log;
    var $controlfiles;
    var $execute;
    
    function courses_plugin_manager($controlfiles = null, $execute = SYNC_COURSE_CREATE_DELETE){
    	$this->controlfiles = $controlfiles;
    	$this->execute = $execute;
    }

	/// Override the base config_form() function
	function config_form($frm) {
	    global $CFG, $DB;

	    $vars = array('course_filedeletelocation', 'course_fileuploadlocation', 'reset_course_file', 'course_fileexistlocation');
	    foreach ($vars as $var) {
	        if (!isset($frm->$var)) {
	            $frm->$var = '';
	        } 
	    }
	    $roles = $DB->get_records('role', null, '', 'id, name, shortname');
	    $ffconfig = get_config('course');

	    $frm->enrol_flatfilemapping = array();
	    foreach($roles as $id => $record) {
			$mapkey = "map_{$record->shortname}";
	        $frm->enrol_flatfilemapping[$id] = array(
	            $record->name,
	            isset($ffconfig->$mapkey) ? $ffconfig->$mapkey : $record->shortname
	        );
	    }	    
	    include ($CFG->dirroot.'/admin/tool/sync/courses/config.html');    
	}

	/**
	* Override the base process_config() function
	*/
	function process_config($config) {
		global $CFG;
		
	    if (!isset($config->tool_sync_course_filedeletelocation)) {
	        $config->tool_sync_course_filedeletelocation = 0;
	    }
	    set_config('tool_sync_course_filedeletelocation', $config->tool_sync_course_filedeletelocation);

	    if (!isset($config->tool_sync_course_filedeleteidentifier)) {
	        $config->tool_sync_course_filedeleteidentifier = 0;
	    }
	    set_config('tool_sync_course_filedeleteidentifier', $config->tool_sync_course_filedeleteidentifier);

	    if (!isset($config->tool_sync_course_fileuploadlocation)) {
	        $config->tool_sync_course_fileuploadlocation = '';
	    }
	    set_config('tool_sync_course_fileuploadlocation', $config->tool_sync_course_fileuploadlocation);

	    if (!isset($config->tool_sync_course_fileexistlocation)) {
	        $config->tool_sync_course_fileexistlocation = '';
	    }
	    set_config('tool_sync_course_fileexistlocation', $config->tool_sync_course_fileexistlocation);		
	    if (!isset($config->tool_sync_course_existfileidentifier)) {
	        $config->tool_sync_course_existfileidentifier = 0;
	    }
	    set_config('tool_sync_course_existfileidentifier', $config->tool_sync_course_existfileidentifier);


	    if (!isset($config->tool_sync_reset_course_file)) {
	        $config->tool_sync_reset_course_file = '';
	    }
	    set_config('tool_sync_reset_course_file', $config->tool_sync_reset_course_file);	

	    if (!isset($config->tool_sync_course_resetfileidentifier)) {
	        $config->tool_sync_course_resetfileidentifier = 1; // shortname as default
	    }
	    set_config('tool_sync_course_resetfileidentifier', $config->tool_sync_course_resetfileidentifier);

	    if (!isset($config->tool_sync_forcecourseupdate)) {
	        $config->tool_sync_forcecourseupdate = 0;
	    }
	    set_config('tool_sync_forcecourseupdate', $config->tool_sync_forcecourseupdate);	
		
	    return true;
	}
    
    function cron() {
        global $CFG, $USER, $DB;

	    define('TOPIC_FIELD','/^(topic)([0-9]|[1-4][0-9]|5[0-2])$/');
		define('TEACHER_FIELD','/^(teacher)([1-9]+\d*)(_account|_role)$/');
		
		if (empty($this->controlfiles->deletion)){
			if (empty($CFG->tool_sync_course_filedeletelocation)) {
	            $this->controlfiles->deletion = $CFG->dataroot.'/sync/deletecourses.csv';  // Default location
	        } else {
	            $this->controlfiles->deletion = $CFG->dataroot.'/'.$CFG->tool_sync_course_filedeletelocation;
	        }
	    }
		
		if (empty($this->tool_sync_controlfiles->creation)){
			if (empty($CFG->tool_sync_course_fileuploadlocation)) {
	            $this->controlfiles->creation = $CFG->dataroot.'/sync/makecourses.csv';  // Default location
	        } else {
	            $this->controlfiles->creation = $CFG->dataroot.'/'.$CFG->tool_sync_course_fileuploadlocation;
	        }
	    }
		
		if (empty($this->controlfiles->check)){
			if (empty($CFG->tool_sync_course_fileexistlocation)) {
	            $this->controlfiles->check = $CFG->dataroot.'/sync/courses.csv';  // Default location
	        } else {
	            $this->controlfiles->check = $CFG->dataroot.'/'.$CFG->tool_sync_course_fileexistlocation;
	        }
	    }
	    
	/// process files

		tool_sync_report($CFG->tool_sync_courselog, 'Starting...');

		if(file_exists($this->controlfiles->check) && ($this->execute & SYNC_COURSE_CHECK)){

			tool_sync_report($CFG->tool_sync_courselog, get_string('startingcheck', 'tool_sync'));

			$text = '';

			$fp = @fopen($this->controlfiles->check, 'rb', 0);

			if ($fp) {

				$i = 0;

				$identifieroptions = array('idnumber', 'shortname', 'id');
				$identifiername = $identifieroptions[0 + @$CFG->tool_sync_course_existfileidentifier];

				while (!feof($fp)) {					
					
					$text = fgets($fp, 1024);
					
					// skip comments and empty lines
					if (sync_is_empty_line_or_format($text, $i == 0)){
						continue;
					}
					
					$valueset = explode($CFG->tool_sync_csvseparator, $text);

					$size = count($valueset);

					if($size == 2){
						$c = new StdClass;
						$c->$identifiername = $valueset[0];
						$c->description = $valueset[1];
						$course = $DB->get_record('course', array($identifiername => $c->$identifiername));

						// give report on missing courses
						if(!$course){
							tool_sync_report($CFG->tool_sync_courselog, get_string('coursenotfound2', 'tool_sync', $course));
						} else {
							tool_sync_report($CFG->tool_sync_courselog, get_string('coursefoundas', 'tool_sync', $course));
						}
					}
					$i++;
				}
			} else {
				tool_sync_report($CFG->tool_sync_courselog, get_string('filenotfound', 'tool_sync', $this->controlfiles->check));
			}
			fclose($fp);					
		}

	/// delete (clean) courses
        if (file_exists($this->controlfiles->deletion) && ($this->execute & SYNC_COURSE_DELETE)) {
        	
			$fh = fopen($this->controlfiles->deletion, 'rb');

            if ($fh) {
				$i = 0;
				$shortnames = array();

				while (!feof($fh)) {					
					$text = fgets($fh);
					// skip comments and empty lines
					if (sync_is_empty_line_or_format($text, $i)){
						continue;
					}			
					$identifiers[] = $text;											
				}
				
				// Fill this with a list of comma seperated id numbers to delete courses.
				$deleted = 0;
				$identifieroptions = array('idnumber', 'shortname', 'id');
				$identifiername = $identifieroptions[0 + @$CFG->tool_sync_course_filedeleteidentifier];
				
				foreach($identifiers as $cid) {
					if(!($c = $DB->get_record('course', array($identifiername => $cid))) ) {
						tool_sync_report($CFG->tool_sync_courselog, get_string('coursenotfound', 'tool_sync', $cid));
						if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->deletion, $text, null);
						$i++;
						continue;
					}

					if(delete_course($c->id, false)) {
						$deleted++;
						tool_sync_report($CFG->tool_sync_courselog, get_string('coursedeleted', 'tool_sync', $cid));
					}
				}
				if ($deleted){				
					fix_course_sortorder();
				}									
			}
			fclose($fh);

			if (!empty($CFG->tool_sync_filearchive)){
				$archivename = basename($this->controlfiles->deletion);
				$now = date('Ymd-hi', time());
				$archivename = $CFG->dataroot."/sync/archives/{$now}_deletion_$archivename";
				copy($this->controlfiles->deletion, $archivename);
			}
			if (!empty($CFG->tool_sync_filecleanup)){
				@unlink($this->controlfiles->deletion);
			}
        }

	/// update/create courses
		if (file_exists($this->controlfiles->creation) && ($this->execute & SYNC_COURSE_CREATE)) {

	        // make arrays of fields for error checking
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
			
			$fu = @fopen($this->controlfiles->creation, 'rb');

			$i = 0;

			if ($fu) {

				while(!feof($fu)){
					$text = fgets($fu, 1024);
					if (!sync_is_empty_line_or_format($text, $i == 0)){
						break;
					}
					$i++;
				}

				$header = explode($CFG->tool_sync_csvseparator, $text);

        		// check for valid field names
        		
        		function trim_values(&$e){
        			$e = trim($e);
        		}
        		
        		array_walk($header, 'trim_values');
        		
        		foreach ($header as $h) {
		            if (empty($h)){
		                tool_sync_report($CFG->tool_sync_courselog, get_string('errornullcsvheader', 'tool_sync'));
		                return;
		            }
                    if (preg_match(TOPIC_FIELD, $h)) { // Regex defined header names
                    } elseif (preg_match(TEACHER_FIELD, $h)) {                         
                    } else {
                		if (!(isset($required[$h]) || isset($optional[$h]))){ 
			                tool_sync_report($CFG->tool_sync_courselog, get_string('errorinvalidfieldname', 'tool_sync', $h));
			                return;
						}

                		if (isset($required[$h])){
							$required[$h] = true; 
						}
            		}
        		}

		        // check for required fields
		        foreach ($required as $key => $value) {
		            if ($value != true){
		                tool_sync_report($CFG->tool_sync_courselog, get_string('fieldrequired', 'error', $key));
		                return;
		            }
		        }

        		$fieldcount = count($header);

		        unset($bulkcourses);
				$courseteachers = array();

				// start processing lines

        		while (!feof($fu)) {
        			$text = fgets($fu, 1024);

					if (sync_is_empty_line_or_format($text)) {
						$i++;
						continue;
					}
					
					$valueset = explode($CFG->tool_sync_csvseparator, $text);
        			
            		if (count($valueset) != $fieldcount){					
               			$e->i = $i;
               			$e->count = count($valueset);
               			$e->expected = $fieldcount;
		                tool_sync_report($CFG->tool_sync_courselog, get_string('errorbadcount', 'tool_sync', $e));
						if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $text, null);
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

            		if (isset($courseteachers)){
                		foreach ($courseteachers as $key => $value){ // Deep validate course teacher info on second pass
	                  		if (isset($value) && (count($value) > 0)){
	                    		if (!(isset($value['_account']) && $this->check_is_in($value['_account']))){
	                    			$e->i = $i;
	                    			$e->key = $key;
		                			tool_sync_report($CFG->tool_sync_courselog, get_string('errornoteacheraccountkey', 'tool_sync', $e));
	                          		continue;
	                          	}
                    			// Hardcoded default values (that are as close to moodle's UI as possible)
                    			// and we can't assume PHP5 so no pointers!
	                    		if (!isset($value['_role'])){
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
        		fclose($fu);
        	} else {
    			tool_sync_report($CFG->tool_sync_courselog, get_string('erroropeningfile', 'tool_sync'));
        	}

	        if (empty($bulkcourses)){
    			tool_sync_report($CFG->tool_sync_courselog, get_string('errornocourses', 'tool_sync'));
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

	            // Try to create the course
	            if (!$oldcourse = $DB->get_record('course', array('shortname' => $bulkcourse['shortname']))) {
	              
	                $coursetocategory = 0; // Category ID
	                
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
    								tool_sync_report($CFG->tool_sync_courselog, get_string('errorcategorycreate', 'tool_sync', $e));
									if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
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
						if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
    					tool_sync_report($CFG->tool_sync_courselog, get_string('errorcategoryparenterror', 'tool_sync', $e));
    					continue;
	                } else {
	                    $result = $this->fast_create_course_ex($coursetocategory, $bulkcourse, $header, $validate);
	                	$e = new StdClass;
	                    $e->coursename = $bulkcourse['shortname'];
	                    $e->i = $i;
						switch ($result) {
		                    case 1:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('coursecreated', 'tool_sync', $a));
		                        $n++; // Succeeded
		                    break;
		                    case -3:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorsectioncreate', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
		                        $p++;
		                    break;
		                    case -4:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorteacherenrolincourse', 'tool_sync', $e));
								if (!empty($CFG->sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
		                        $p++;
		                    break;
							case -5:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorteacherrolemissing', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
								$p++;
							break;
							case -6:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorcoursemisconfiguration', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
								$p++;
							break;
							case -7:
								$e->template = $bulkcourse['template'];
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errortemplatenotfound', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
								$p++;
							break;	
							case -8:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorrestoringtemplatesql', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
								$p++;
							break;	
		                    default:
    							tool_sync_report($CFG->tool_sync_courselog, get_string('errorrestoringtemplate', 'tool_sync', $e));
								if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
		                    break;
	                	}
	              	}
	            } else {
	            	if (!empty($CFG->tool_sync_forcecourseupdate)){

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
	    								tool_sync_report($CFG->tool_sync_courselog, get_string('errorcategorycreate', 'tool_sync', $e));
										if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
		                          		continue;
		                        }      
		                    }
		                    ($coursetocategory == -1) or $coursetocategory = $curparent;
		                    // Last category created will contain the actual course
		                } else {
		                    // It's just a straight category ID
		                    $coursetocategory = (!empty($bulkcourse['category'])) ? $bulkcourse['category'] : -1 ;
		                }
		                
		                if ($coursetocategory == -1){
	                		$e = new StdClass;
		                	$e->i = $i;
		                	$e->coursename = $oldcourse->shortname;
							if (!empty($CFG->tool_sync_filefailed)) sync_feed_tryback_file($this->controlfiles->creation, $sourcetext[$i], $header);
	    					tool_sync_report($CFG->tool_sync_courselog, get_string('errorcategoryparenterror', 'tool_sync', $e));
	    					continue;
		                } else {
		                	$oldcourse->category = $coursetocategory;
		                }

	            		foreach($bulkcourse as $key => $value){
	            			if (isset($oldcourse->$key) && $key != 'id' && $key != 'category'){
	            				$oldcourse->$key = $value;
	            			}
	            		}
	            		if ($DB->update_record('course', $oldcourse)){
	                		$e = new StdClass;
	                    	$e->i = $i;
	            			$e->shortname = $oldcourse->shortname;
							tool_sync_report($CFG->tool_sync_courselog, get_string('courseupdated', 'tool_sync', $e));
	            		} else {
	                		$e = new StdClass;
	                    	$e->i = $i;
	            			$e->shortname = $oldcourse->shortname;
							tool_sync_report($CFG->tool_sync_courselog, get_string('errorcourseupdated', 'tool_sync', $e));
	            		}
	            	} else {
						tool_sync_report($CFG->tool_sync_courselog, get_string('courseexists', 'tool_sync', $a));
		              	// Skip course, already exists
		            }
	              	$s++;
	            }
	            $t++;
	        }    
            
	        fix_course_sortorder(); // Re-sort courses

			if (!empty($CFG->tool_sync_filearchive)){
				$archivename = basename($this->controlfiles->creation);
				$now = date('Ymd-hi', time());
				$archivename = $CFG->dataroot."/sync/archives/{$now}_creation_$archivename";
				copy($this->controlfiles->creation, $archivename);
			}
			
			if (!empty($CFG->sync_filecleanup)){
				@unlink($this->controlfiles->creation);
			}
        }
        
        return true;
    }
		
	/**
	*
	*/
    function get_default_category() {
        global $CFG, $USER, $DB;

		if (!$mincat = $DB->get_field('course_categories', 'MIN(id)', array())){
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
        
        if ($fieldname == ''){
        	$fieldname = $validatename;
        }
		 
        if (!isset($validate[$validatename])){
        	// we dont translate this > developper issue
        	$errormessage = 'Coding Error: Unvalidated field type: "'.$validatename.'"';
			tool_sync_report($CFG->tool_sync_courselog, $errormessage);
			return;
        }

        $format = $validate[$validatename];

        switch($format[0]) {
	        case 1: // String
	            if (($maxlen = $format[1]) != 0){  // Max length?
	                if (strlen($value) > $format[1]){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->length = $format[1];
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationstringlength', 'tool_sync', $e));
						return;
                    }
                }

	            if ($format[2] == 1){ // Not null?
	                if (!$this->check_is_string($value)){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationempty', 'tool_sync', $e));
						return;
	                }
	            }
	        break;

	        case 2: // Integer
                if (!$this->check_is_in($value)){ 
	                $e = new StdClass;
                	$e->i = $lineno;
                	$e->fieldname = $fieldname;
					tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationintegercheck', 'tool_sync', $e));
					return;
				}
                
                if (($max = $format[1]) != 0){  // Max value?
                    if ($value > $max){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->max = $max;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationintegerabove', 'tool_sync', $e));
						return;
                    }
                }

                if (isset($format[2]) && !is_null($format[2])){  // Min value
	                $min = $format[2];
                    if ($value < $min){ 
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->min = $min;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationintegerbeneath', 'tool_sync', $e));
						return;
                    }
                }
	        break;
    
	        case 3: // Timestamp - validates and converts to Unix Time
	        	$value = strtotime($value);
	            if ($value == -1){ // failure
					$e = new StdClass;
                	$e->i = $lineno;
                	$e->fieldname = $fieldname;
					tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationtimecheck', 'tool_sync', $e));
					return;
	            }
	        break;

	        case 4: // Domain
	            $validvalues = explode(',', $format[1]);
	            if (array_search($value, $validvalues) === false){
                	$e = new StdClass;
                	$e->i = $lineno;
                	$e->fieldname = $fieldname;
                	$e->set = $format[1];
					tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationvalueset', 'tool_sync', $e));
					return;
				}
	        break; 

	        case 5: // Category
	            if ($this->check_is_in($value)) {
	              // It's a Category ID Number
					if (!$DB->record_exists('course_categories', array('id' => $value))){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->category = $value;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationcategoryid', 'tool_sync', $e));
						return;
					}
	            } elseif ($this->check_is_string($value)) {
	               	// It's a Category Path string
	               	$value = trim(str_replace('\\','/',$value)," \t\n\r\0\x0B/");
	               	// Clean path, ensuring all slashes are forward ones
	               	if (strlen($value) <= 0){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationcategoryunpathed', 'tool_sync', $e));
						return;
	                }
	                
	                unset ($cats);
	                $cats = explode('/', $value); // Break up path into array
					
	                if (count($cats) <= 0){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->path = $value;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationcategorybadpath', 'tool_sync', $e));
						return;
	                }
	                
	                foreach ($cats as $n => $item) { // Validate the path
	                
	                  	$item = trim($item); // Remove outside whitespace
	                	
	                  	if (strlen($item) > 100){ 
	                		$e = new StdClass;
		                	$e->i = $lineno;
		                	$e->fieldname = $fieldname;
		                	$e->item = $item;
							tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationcategorylength', 'tool_sync', $e));
	                      	return;
	                    }
	                  	if (!$this->check_is_string($item)){
	                		$e = new StdClass;
		                	$e->i = $lineno;
		                	$e->fieldname = $fieldname;
		                	$e->value = $value;
		                	$e->pos = $n + 1;
							tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationcategorytype', 'tool_sync', $e));
	                      	return;
	                    }
	                }
	                
	                $value = $cats; // Return the array
	                unset ($cats);
	            } else {
                	$e = new StdClass;
                	$e->i = $lineno;
                	$e->fieldname = $fieldname;
					tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationbadtype', 'tool_sync', $e));
					return;
	            }
	        break;

	        case 6: // User ID or Name (Search String)
	            $value = trim($value);
	            if ($this->check_is_in($value)) { // User ID
	                if (!$DB->record_exists('user', array('id' => $value))){
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
	                	$e->value = $value;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationbaduserid', 'tool_sync', $e));
	                    return;
	                }
	            } elseif ($this->check_is_string($value)) { // User Search String
	                // Only PHP5 supports named arguments
	                $usersearch = get_users_listing('lastaccess', 'ASC', 0, 99999, mysql_real_escape_string($value), '', '');
	                if (isset($usersearch) and ($usersearch !== false) and is_array($usersearch) and (($ucountc = count($usersearch)) > 0)) {
	                    if ($ucount > 1){
	                		$e = new StdClass;
		                	$e->i = $lineno;
		                	$e->fieldname = $fieldname;
	                    	$e->ucount = $ucount;
							tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationmultipleresults', 'tool_sync', $e));
		                    return;
	                    }

	                    reset($usersearch);

	                    $uid = key($usersearch);

	                    if (!$this->check_is_in($uid) || !$DB->record_exists('user', array('id' => $uid))){
	                		$e = new StdClass;
		                	$e->i = $lineno;
		                	$e->fieldname = $fieldname;
							tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationsearchmisses', 'tool_sync', $e));
		                    return;
	                    }
						
	                    $value = $uid; // Return found user id
						
	                } else {
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationsearchfails', 'tool_sync', $e));
	                    return;
	                }
	            } else {
	              	if ($format[1] == 1){ // Not null?
	                	$e = new StdClass;
	                	$e->i = $lineno;
	                	$e->fieldname = $fieldname;
						tool_sync_report($CFG->tool_sync_courselog, get_string('errorvalidationempty', 'tool_sync', $e));
	                    return;
                    }
	            }
	        break; 

	        default:
	        	// not translated
	            $errormessage = 'Coding Error: Bad field validation type: "'.$fieldname.'"';
				tool_sync_report($CFG->tool_sync_courselog, $errormessage);
                return;
	        break;
        }

        return $value;
    }

    function microtime_float(){
        // In case we don't have php5
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    
    function fast_get_category_ex($hname, &$hstatus, $hparent = 0){
        // Find category with the given name and parentID, or create it, in both cases returning a category ID
        /* $hstatus:
            -1  :   Failed to create category
            1   :   Existing category found
            2   :   Created new category successfully
        */
        global $CFG, $USER, $DB;

        // Check if a category with the same name and parent ID already exists
        if ($cat = $DB->get_field_select('course_categories', 'id', " name = ? AND parent = ? ", array($hname, $hparent))){
			$hstatus = 1;
        	return $cat;
        } else {
        	if (!$parent = $DB->get_record('course_categories', array('id' => $hparent))){
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
			if ($cat->id = $DB->insert_record('course_categories', $cat)){
				$hstatus = 2;
				
				// must post update 
				$cat->path = $parent->path.'/'.$cat->id;
				$DB->update_record('course_categories', $cat);
				// we must make category context
				create_contexts(CONTEXT_COURSECAT, $cat->id);
			} else {
				$hstatus = -1;
			}
			return $cat->id;
        }
    }
    
	// Edited by Ashley Gooding & Cole Spicer to fix problems with 1.7.1 and make easier to dynamically add new columns
	// we keep that old code till next work
    function fast_create_course_ex($hcategory, $course, $header, $validate) { 
        global $CFG, $DB;

		if(!is_array($course) || !is_array($header) || !is_array($validate)) {
			return -2;
		}  

		// trap when template not found
		if(isset($course['template']) && $course['template'] != '') {			
			if(!($tempcourse = $DB->get_record('course', array('shortname' => $course['template'])))){
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
			if(preg_match(TOPIC_FIELD, $col) || preg_match(TEACHER_FIELD, $col) || $col == 'category') {
				continue;
			}
			if($col == 'expirythreshold') {
				$courserec->$col = $course[$col]*86400;
			} else {
				$courserec->$col = $course[$col];
			}
		}

		if(!empty($course['template'])) {

            if (!$archivefile = tool_sync_locate_backup_file($tempcourse->id, 'course')){
            				
				// get course template from publishflow backups if publishflow installed.
				if ($DB->get_record('blocks', array('name' => 'publishflow'))){
		            $archivefile = tool_sync_locate_backup_file($tempcourse->id, 'publishflow');
		            if (!$archivefile){
		        		return -2;
		            }
		        } else {
		        	return -2;
		        }
		    }

			$uniq = uniqid();
		        		        
            $tempdir = $CFG->dataroot."/temp/backup/$uniq";
            if (!is_dir($tempdir)){
	            mkdir($tempdir, 0777, true);
	        }
			// unzip all content in temp dir

            // actually locally copying archive
            $contextid = context_system::instance()->id;
            $component = 'tool_sync';
            $filearea = 'temp';
            $itemid = $uniq;
            if ($archivefile->extract_to_storage(new zip_packer(), $contextid, $component, $filearea, $itemid, $tempdir, $USER->id)){	

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
	            if ($newcourse_id){

				    // add all changes from incoming courserec
				    $newcourse = $DB->get_record('course', array('id' => $newcourse_id));
				    foreach((array)$courserec as $field => $value){
				    	if ($field == 'format' || $field == 'id') continue; // protect sensible identifying fields
				    	$newcourse->$field = $value;
				    }
				    if (!$DB->update_record('course', $newcourse)){
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
	            foreach($course['topics'] as $dtopicno => $dtopicname){
	            	if (!empty($dtopicname)) $maxfilledtopics = $dtopicno; // we guess the max declared topic
			        if (strstr($dtopicname, '|') === false){
						$sectionname = $dtopicname;
						$sectionsummary = '';
			        } else {				        	
						list($sectionname, $sectionsummary) = explode('|', $dtopicname);
			        }
			        
		            if (!$sectiondata = $DB->get_record('course_sections', array('section' => $dtopicno, 'course' => $newcourse->id))) { // Avoid overflowing topic headings
				        $csection = new StdClass;
				        $csection->course = $newcourse->id;
				        $csection->section = $dtopicno;
				        $csection->name = $sectionname;
				        $csection->summary = $sectionsummary;
				        $csection->sequence = '';
				        $csection->visible = 1;
				        if (!$DB->insert_record('course_sections', $csection)){
				        }
		            } else {
				        $sectiondata->summary = $sectionname;
				        $sectiondata->name = $sectionsummary;
				        $DB->update_record('course_sections', $sectiondata);
		            }
		        }
		        if (!isset($course['topics'][0])) {
		        	if (!$DB->get_record('course_sections', array('section' => 0, 'course' => $newcourse->id))){
				        $csection = new StdClass;
				        $csection->course = $newcourse->id;
				        $csection->section = 0;
				        $csection->name = '';
				        $csection->summary = '';
				        $csection->sequence = '';
				        $csection->visible = 1;
				        if (!$DB->insert_record('course_sections', $csection)){
				            return -3;
				        }
				    }
		        }
		        
		        // finally we can bind the course to have $maxfilledtopics topics
		        $new = 0;
				if (!$formatoptions = $DB->get_record('course_format_options', array('courseid' => $newcourse->id, 'name' => 'numsections', 'format' => $format))){
			        $formatoptions = new StdClass();
			        $new = 1;
			    }
		        $formatoptions->courseid = $newcourse->id;
		        $formatoptions->format = $format;
		        $formatoptions->name = 'numsections';
		        $formatoptions->section = 0;
		        $formatoptions->value = $maxfilledtopics;
		        if ($new){
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
			        if (!$DB->insert_record('course_sections', $csection)){
			        }
			    }
			}
        	rebuild_course_cache($newcourse->id, true);
		}
		if (!$context = context_course::instance($newcourse->id)) {
        	return -6;
    	}
		
        if (isset($course['teachers_enrol']) && (count($course['teachers_enrol']) > 0)) { // Any teachers specified?
            foreach($course['teachers_enrol'] as $dteacherno => $dteacherdata) {
                if (isset($dteacherdata['_account'])) {
					$roleid = $DB->get_field('role', 'shortname', null);
					$roleassignrec = new StdClass;
					$roleassignrec->roleid = $roleid;
					$roleassignrec->contextid = $context->id;
					$roleassignrec->userid = $dteacherdata['_account'];
					$roleassignrec->timemodified = $course['timecreated'];
					$roleassignrec->modifierid = 0;
					$roleassignrec->enrol = 'manual';
					if (!$DB->insert_record('role_assignments', $roleassignrec)){
	                    return -4;
					}
                }
            }
        }               
        return 1;
    }		
}

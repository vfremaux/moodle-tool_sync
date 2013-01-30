<?php

	function create_course_deletion_file($selection){	
		global $CFG;
		$filename = $CFG->dataroot.'/sync/deletecourses.txt';
		$file = fopen($filename, 'wb');		
		$size = count($selection);
		for($i = 0 ; $i < $size - 1 ; $i++){
			fputs($file, "$selection[$i]");
			fputs($file, "\n");
		}		
		$size = $size - 1;
		fputs($file, "$selection[$size]");
		fclose($file);
	}
	
	function sync_scan_empty_categories($parentcatid, &$scannedids, &$path){
		global $CFG, $DB;

		// get my subs
		$sql = "
			SELECT DISTINCT
				cc.id,
				cc.parent,
				cc.name,
				count(c.id) as courses
			FROM
				{course_categories} cc
			LEFT JOIN
				{course} c
			ON 
				cc.id = c.category
			WHERE 
				cc.parent = ?
			GROUP BY 
				cc.id
		";
		$cats = $DB->get_records_sql($sql, array($parentcatid));
		if ($parentcatid != 0){
			$countcourses = $DB->count_records('course', array('category' => $parentcatid));
		} else {
			$countcourses = 0;
		}

		if (!empty($cats)){			
			foreach($cats as $ec){

				$mempath = $path;
				$path .= ' / '.$ec->name;
				$subcountcourses = sync_scan_empty_categories($ec->id, $scannedids, $path);
				$path = $mempath;

				if ($subcountcourses == 0){
					// this is a really empty cat
					echo "<tr><td align=\"left\"><b>{$ec->name}</b></td><td align=\"left\">$path</td></tr>";
					$scannedids[] = $ec->id;
				}
				$countcourses += $subcountcourses;
			}
		}
		return $countcourses;
	}

	/**
	* checks locally if a deployable/publishable backup is available
	* @param reference $loopback variable given to setup an XMLRPC loopback message for testing
	* @return boolean
	*/
	function enrol_sync_locate_backup_file($courseid, $filearea){
	    global $CFG, $DB;
	  
	    $fs = get_file_storage();
	    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
	    $files = $fs->get_area_files($coursecontext->id,'backup', $filearea, 0, 'timecreated', false);
	    
	    if(count($files) > 0)
	    {
	        return array_pop($files);
	    }
	    
	    return false;
	}
?>
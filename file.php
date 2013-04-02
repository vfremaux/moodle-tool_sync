<?php // $Id: file.php,v 1.1 2013-01-18 15:48:18 vf Exp $

/**
* A derivate of the standard file.php. 
*
* Allows an administrator to manage and upload files in the special "sync" 
* container for course/users synchronisation.
* @package enrol
* @subpackage sync
* @author Funck Thibaut
*/

    require('../../../config.php');
    require($CFG->libdir.'/filelib.php');
    require($CFG->libdir.'/adminlib.php');
    ob_start();

   	// $id      = required_param('id', PARAM_INT);
    $file     = optional_param('file', '', PARAM_PATH);
    $wdir     = optional_param('wdir', '', PARAM_PATH);
    $action   = optional_param('action', '', PARAM_ACTION);
    $name     = optional_param('name', '', PARAM_FILE);
    $oldname  = optional_param('oldname', '', PARAM_FILE);
    $choose   = optional_param('choose', '', PARAM_FILE); //in fact it is always 'formname.inputname'
    $userfile = optional_param('userfile','',PARAM_FILE);
    $save     = optional_param('save', 0, PARAM_BOOL);
    $text     = optional_param('text', '', PARAM_RAW);
    $confirm  = optional_param('confirm', 0, PARAM_BOOL);

    if ($choose) {
        if (count(explode('.', $choose)) > 2) {
            print_error('errorbadchooseformat', 'tool_sync');
        }
    }
    
    require_login();

	// Security : only master admins can do anything here
	if (!is_siteadmin()) {
        print_error('erroradminrequired', 'tool_sync');
    }
	if (! $site = get_site()) {
        print_error('errornosite', 'tool_sync');
    }
	if (!$adminuser = get_admin()) {
        print_error('errornoadmin', 'tool_sync');
    }

    function html_footer() {
        global $COURSE, $choose, $OUTPUT;

        echo '</td></tr></table>';

        echo $OUTPUT->footer($COURSE);
    }

    function html_header($wdir, $formfield = ''){
        global $CFG, $ME, $choose, $USER, $SITE, $PAGE, $OUTPUT;

		$PAGE->navigation->add(get_string('enrolname', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php?sesskey='.sesskey());

        $strfiles = get_string('files');

        if ($wdir == '/') {
			$PAGE->navigation->add($strfiles);
        } else {
            $dirs = explode("/", $wdir);
            $numdirs = count($dirs);
            $link = "";
			$PAGE->navigation->add($strfiles, $ME."?wdir=/&amp;choose=$choose");

            for ($i = 2 ; $i < $numdirs - 1 ; $i++) {
                $link .= "/".urlencode($dirs[$i]);
				$PAGE->navigation->add('sync', $ME."?id=1&amp;wdir=$link&amp;choose=$choose");
            }
			$PAGE->navigation->add($dirs[$numdirs-1]);
        }

        if ($choose) {
        	$url = $CFG->wwwroot.'/admin/tool/sync/file.php';
            $PAGE->set_url($url);
            $PAGE->set_context(null);
            $PAGE->set_title($SITE->fullname);
            $PAGE->set_heading($SITE->fullname);
            /* SCANMSG: may be additional work required for $navigation variable */
            echo $OUTPUT->header();

            $chooseparts = explode('.', $choose);
            if (count($chooseparts) == 2){
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function set_value(txt) {
                opener.document.forms['<?php echo $chooseparts[0]."'].".$chooseparts[1] ?>.value = txt;
                window.close();
            }
            //]]>
            </script>

            <?php
            } elseif (count($chooseparts) == 1){
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function set_value(txt) {
                opener.document.getElementById('<?php echo $chooseparts[0] ?>').value = txt;
                window.close();
            }
            //]]>
            </script>

            <?php

            }
            $fullnav = '';
            $i = 0;
            // TODO : Check here
            foreach ($navlinks as $navlink) {
                // If this is the last link do not link
                if ($i == count($navlinks) - 1) {
                    $fullnav .= $navlink['name'];
                } else {
                    $fullnav .= '<a href="'.$navlink['link'].'">'.$navlink['name'].'</a>';
                }
                $fullnav .= ' -> ';
                $i++;
            }
            $fullnav = substr($fullnav, 0, -4);
            $fullnav = str_replace('->', '&raquo;', format_string($SITE->shortname) . " -> " . $fullnav);
            echo '<div id="nav-bar">'.$fullnav.'</div>';

        } else {
        	$url = $CFG->wwwroot.'/admin/tool/sync/file.php';
            $PAGE->set_url($url);
            $PAGE->set_context(null);
            $PAGE->set_title($SITE->shortname);
            $PAGE->set_heading($SITE->fullname);
            /* SCANMSG: may be additional work required for $navigation variable */
            $PAGE->set_focuscontrol($formfield);
            echo $OUTPUT->header();
        }


        echo "<table border=\"0\" style=\"margin-left:auto;margin-right:auto\" cellspacing=\"3\" cellpadding=\"3\" width=\"640\">";
        echo "<tr>";
        echo "<td colspan=\"2\">";

    }

	function get_syncfile_url($path, $options=null) {
	    global $CFG, $HTTPSPAGEREQUIRED, $OUTPUT;
	    
	    $path = str_replace('//', '/', $path);  
	    $path = trim($path, '/'); // no leading and trailing slashes
	
	    // type of file
        $url = $CFG->wwwroot."/admin/tool/sync/file.php";

		// do not try to deal with slasharguments here	
        $path = rawurlencode('/'.$path);
        $ffurl = $url.'?file='.$path;
        $separator = '&amp;';
	
	    if ($options) {
	        foreach ($options as $name => $value) {
	            $ffurl = $ffurl.$separator.$name.'='.$value;
	            $separator = '&amp;';
	        }
	    }
	
	    return $ffurl;
	}
	
    if (! $basedir = make_upload_directory('sync')) {
        print_error('errorsitepermissions', 'tool_sync');
    }

    $baseweb = $CFG->wwwroot;

//  End of configuration and access control


    if ($wdir == '') {
        $wdir = "/";
    }

    if ($wdir{0} != '/') {  //make sure $wdir starts with slash
        $wdir = "/".$wdir;
    }

    if (!is_dir($basedir.$wdir)) {
        html_header($wdir);
        print_error('errordirectory', 'tool_sync', '', "$CFG->wwwroot/admin/tool/sync/file.php?wdir=");
    }

	// show a file if a file is reqested

	if (!empty($file) && empty($action)){
		
		$pathname = $basedir.$file;
		echo $pathname;
	    // check that file exists
	    if (!file_exists($pathname)) {
	        syncfile_not_found();
	    }
	
	    // ========================================
	    // finally send the file
	    // ========================================
	    session_write_close(); // unlock session during fileserving

	    if (!isset($CFG->filelifetime)) {
	        $lifetime = 86400;     // Seconds for files to remain in caches
	    } else {
	        $lifetime = $CFG->filelifetime;
	    }

	    $forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
	    
	    // extract relative path components
	    $args = explode('/', trim($pathname, '/'));
	    $filename = $args[count($args)-1];
	    
	    ob_end_clean();
	    send_file($pathname, $filename, $lifetime, false, false, $forcedownload);
	
	    function syncfile_not_found() {
	        global $CFG;
	        header('HTTP/1.0 404 not found');
	        print_error('filenotfound', 'error', $CFG->wwwroot.'/admin/tool/sync/file.php?wdir='); //this is not displayed on IIS??
	    }
	}

	// other actions
	
    switch ($action) {

        case 'upload':
            html_header($wdir);
            require_once($CFG->dirroot.'/lib/uploadlib.php');

            if ($save and confirm_sesskey()) {
            	$course = $DB->get_record('course', array('id' => SITEID));
                $course->maxbytes = 0;  // We are ignoring course limits
                $um = new upload_manager('userfile',false,false,$course,false,0);
                $dir = "$basedir$wdir";
                if ($um->process_file_uploads($dir)) {
                    echo $OUTPUT->notification(get_string('uploadedfile'));
                }
                // um will take care of error reporting.
                displaydir($wdir);
            } else {
                $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);
                $filesize = display_size($upload_max_filesize);

                $struploadafile = get_string('uploadafile');
                $struploadthisfile = get_string('uploadthisfile');
                $strmaxsize = get_string('maxsize', '', $filesize);
                $strcancel = get_string('cancel');

                echo "<p>$struploadafile ($strmaxsize) --> <b>$wdir</b></p>";
                echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"file.php\">";
                echo "<div>";
                echo "<table><tr><td colspan=\"2\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"upload\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
				echo "<input type=\"file\" alt=\"userfile\" name=\"userfile\" size=\"50\">";
                echo " </td></tr></table>";
                echo " <input type=\"submit\" name=\"save\" value=\"$struploadthisfile\" />";
                echo "</div>";
                echo "</form>";
                echo "<form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</div>";
                echo "</form>";
            }
            html_footer();
            break;

        case "delete":
            if ($confirm and confirm_sesskey()) {
                html_header($wdir);
                if (!empty($USER->filelist)) {
                    foreach ($USER->filelist as $file) {
                        $fullfile = $basedir.'/'.$file;
                        if (! fulldelete($fullfile)) {
                            echo "<br />Error: Could not delete: $fullfile";
                        }
                    }
                }
                clearfilelist();
                displaydir($wdir);
                html_footer();

            } else {
                html_header($wdir);

                if (setfilelist($_POST)) {
                    echo $OUTPUT->notification(get_string('deletecheckwarning').':');
                    $OUTPUT->box_start("center");
                    printfilelist($USER->filelist);
                    $OUTPUT->box_end();
                    echo "<br />";

                    echo $OUTPUT->confirm(get_string('deletecheckfiles'), "file.php?wdir=".urlencode($wdir)."&amp;action=delete&amp;confirm=>1&amp;sesskey=".sesskey()."&amp;choose=$choose", "file.php?wdir=".urlencode($wdir)."&amp;action=cancel&amp;choose=$choose");
                } else {
                    displaydir($wdir);
                }
                html_footer();
            }
            break;

        case "move":
            html_header($wdir);
            if (($count = setfilelist($_POST)) and confirm_sesskey()) {
                $USER->fileop     = $action;
                $USER->filesource = $wdir;
                echo "<p class=\"centerpara\">";
                print_string("selectednowmove", "moodle", $count);
                echo "</p>";
            }
            displaydir($wdir);
            html_footer();
            break;

        case "paste":
            html_header($wdir);
            if (isset($USER->fileop) and ($USER->fileop == "move") and confirm_sesskey()) {
                foreach ($USER->filelist as $file) {
                    $shortfile = basename($file);
                    $oldfile = $basedir.'/'.$file;
                    $newfile = $basedir.$wdir."/".$shortfile;
                    if (!rename($oldfile, $newfile)) {
                        echo "<p>Error: $shortfile not moved</p>";
                    }
                }
            }
            clearfilelist();
            displaydir($wdir);
            html_footer();
            break;

        case "rename":
            if (($name != '') and confirm_sesskey()) {
                html_header($wdir);
                $name = clean_filename($name);
                if (file_exists($basedir.$wdir."/".$name)) {
                    echo "<center>Error: $name already exists!</center>";
                } else if (!rename($basedir.$wdir."/".$oldname, $basedir.$wdir."/".$name)) {
                    echo "<p align=\"center\">Error: could not rename $oldname to $name</p>";
                }
                displaydir($wdir);

            } else {
                $strrename = get_string('rename');
                $strcancel = get_string('cancel');
                $strrenamefileto = get_string('renamefileto', 'moodle', $file);
                html_header($wdir, "renamename");
                echo "<p>$strrenamefileto:</p>";
                echo "<table><tr><td>";
                echo "<form name=\"renameform\" action=\"file.php\" method=\"post\">";
                echo "<fieldset class=\"invisiblefieldset\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"rename\" />";
                echo " <input type=\"hidden\" name=\"oldname\" value=\"$file\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                echo " <input type=\"text\" id=\"renamename\" name=\"name\" size=\"35\" value=\"$file\" />";
                echo " <input type=\"submit\" value=\"$strrename\" />";
                echo "</fieldset>";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</div>";
                echo "</form>";
                echo "</td></tr></table>";
            }
            html_footer();
            break;

        case "makedir":
            if (($name != '') and confirm_sesskey()) {
                html_header($wdir);
                $name = clean_filename($name);
                if (file_exists("$basedir$wdir/$name")) {
                    echo "Error: $name already exists!";
                } else if (! make_upload_directory("sync$wdir/$name")) {
                    echo "Error: could not create $name";
                }
                displaydir($wdir);

            } else {
                $strcreate = get_string('create');
                $strcancel = get_string('cancel');
                $strcreatefolder = get_string('createfolder', 'moodle', $wdir);
                html_header($wdir, "form.name");
                echo "<p>$strcreatefolder:</p>";
                echo "<table><tr><td>";
                echo "<form action=\"file.php\" method=\"post\">";
                echo "<fieldset class=\"invisiblefieldset\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"makedir\" />";
                echo " <input type=\"text\" name=\"name\" size=\"35\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                echo " <input type=\"submit\" value=\"$strcreate\" />";
                echo "</fieldset>";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</div>";
                echo "</form>";
                echo "</td></tr></table>";
            }
            html_footer();
            break;

        case "edit":
            html_header($wdir);
            if (($text != '') and confirm_sesskey()) {
                $fileptr = fopen($basedir.'/'.$file,"w");
                $text = preg_replace('/\x0D/', '', $text);  // http://moodle.org/mod/forum/discuss.php?d=38860
                fputs($fileptr, stripslashes($text));
                fclose($fileptr);
                displaydir($wdir);

            } else {
                $streditfile = get_string('edit', '', "<b>$file</b>");
                $fileptr  = fopen($basedir.'/'.$file, "r");
                $contents = fread($fileptr, filesize($basedir.'/'.$file));
                fclose($fileptr);

                echo $OUTPUT->heading("$streditfile");

                echo "<table><tr><td colspan=\"2\">";
                echo "<form action=\"file.php\" method=\"post\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"file\" value=\"$file\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"edit\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                print_textarea($usehtmleditor, 25, 80, 680, 400, "text", $contents);
                echo "</td></tr><tr><td>";
                echo " <input type=\"submit\" value=\"".get_string('savechanges')."\" />";
                echo "</div>";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"".get_string('cancel')."\" />";
                echo "</div>";
                echo "</form>";
                echo "</td></tr></table>";

            }
            html_footer();
            break;

        case "zip":
            if (($name != '') and confirm_sesskey()) {
                html_header($wdir);
                $name = clean_filename($name);

                $files = array();
                foreach ($USER->filelist as $file) {
                   $files[] = "$basedir/$file";
                }

                if (!zip_files($files,"$basedir$wdir/$name")) {
                    print_error("zipfileserror","error");
                }

                clearfilelist();
                displaydir($wdir);

            } else {
                html_header($wdir, "form.name");

                if (setfilelist($_POST)) {
                    echo "<p align=\"center\">".get_string('youareabouttocreatezip').":</p>";
                    $OUTPUT->box_start("center");
                    printfilelist($USER->filelist);
                    $OUTPUT->box_end();
                    echo "<br />";
                    echo "<p align=\"center\">".get_string('whattocallzip')."</p>";
                    echo "<table><tr><td>";
                    echo "<form action=\"file.php\" method=\"post\">";
                    echo "<fieldset class=\"invisiblefieldset\">";
                    echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                    echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                    echo " <input type=\"hidden\" name=\"action\" value=\"zip\" />";
                    echo " <input type=\"text\" name=\"name\" size=\"35\" value=\"new.zip\" />";
                    echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                    echo " <input type=\"submit\" value=\"".get_string('createziparchive')."\" />";
                    echo "<fieldset>";
                    echo "</form>";
                    echo "</td><td>";
                    echo "<form action=\"file.php\" method=\"get\">";
                    echo "<div>";
                    echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                    echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                    echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                    echo " <input type=\"submit\" value=\"".get_string('cancel')."\" />";
                    echo "</div>";
                    echo "</form>";
                    echo "</td></tr></table>";
                } else {
                    displaydir($wdir);
                    clearfilelist();
                }
            }
            html_footer();
            break;

        case "unzip":
            html_header($wdir);
            if (($file != '') and confirm_sesskey()) {
                $strok = get_string('ok');
                $strunpacking = get_string('unpacking', '', $file);

                echo "<p align=\"center\">$strunpacking:</p>";

                $file = basename($file);

                if (!unzip_file("$basedir$wdir/$file")) {
                    print_error("unzipfileserror","error");
                }

                echo "<div style=\"text-align:center\"><form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strok\" />";
                echo "</div>";
                echo "</form>";
                echo "</div>";
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "listzip":
            html_header($wdir);
            if (($file != '') and confirm_sesskey()) {
                $strname = get_string('name');
                $strsize = get_string('size');
                $strmodified = get_string('modified');
                $strok = get_string('ok');
                $strlistfiles = get_string('listfiles', '', $file);

                echo "<p align=\"center\">$strlistfiles:</p>";
                $file = basename($file);

                include_once("$CFG->libdir/pclzip/pclzip.lib.php");
                $archive = new PclZip(cleardoubleslashes("$basedir$wdir/$file"));
                if (!$list = $archive->listContent(cleardoubleslashes("$basedir$wdir"))) {
                    echo $OUTPUT->notification($archive->errorInfo(true));

                } else {
                    echo "<table cellpadding=\"4\" cellspacing=\"2\" border=\"0\" width=\"640\" class=\"files\">";
                    echo "<tr class=\"file\"><th align=\"left\" class=\"header name\" scope=\"col\">$strname</th><th align=\"right\" class=\"header size\" scope=\"col\">$strsize</th><th align=\"right\" class=\"header date\" scope=\"col\">$strmodified</th></tr>";
                    foreach ($list as $item) {
                        echo "<tr>";
                        print_cell("left", s($item['filename']), 'name');
                        if (! $item['folder']) {
                            print_cell("right", display_size($item['size']), 'size');
                        } else {
                            echo "<td>&nbsp;</td>";
                        }
                        $filedate  = userdate($item['mtime'], get_string('strftimedatetime'));
                        print_cell("right", $filedate, 'date');
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "<br /><center><form action=\"file.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strok\" />";
                echo "</div>";
                echo "</form>";
                echo "</center>";
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "restore":
            html_header($$wdir);
            if (($file != '') and confirm_sesskey()) {
                echo "<p align=\"center\">".get_string('youaregoingtorestorefrom').":</p>";
                echo $OUTPUT->box_start("center");
                echo $file;
                echo $OUTPUT->box_end();
                echo "<br />";
                echo "<p align=\"center\">".get_string('areyousuretorestorethisinfo')."</p>";
                $restore_path = "$CFG->wwwroot/backup/restore.php";
                echo $OUTPUT->confirm(get_string('areyousuretorestorethis'), $restore_path."?id=".$id."&amp;file=".cleardoubleslashes($id.$wdir."/".$file)."&amp;method=manual", "file.php?id=$id&amp;wdir=$wdir&amp;action=cancel");
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "cancel":
            clearfilelist();

        default:
            html_header($wdir);
            displaydir($wdir);
            html_footer();
            break;
}


/// FILE FUNCTIONS ///////////////////////////////////////////////////////////


function setfilelist($VARS) {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";

    $count = 0;
    foreach ($VARS as $key => $val) {
        if (substr($key,0,4) == "file") {
            $count++;
            $val = rawurldecode($val);
            $USER->filelist[] = clean_param($val, PARAM_PATH);
        }
    }
    return $count;
}

function clearfilelist() {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";
}


function printfilelist($filelist) {
    global $CFG, $basedir, $OUTPUT;

    $strfolder = get_string('folder');
    $strfile   = get_string('file');

    foreach ($filelist as $file) {
        if (is_dir($basedir.'/'.$file)) {
            echo '<img src="'. $OUTPUT->pix_url('f/folder') . '" class="icon" alt="'. $strfolder .'" /> '. htmlspecialchars($file) .'<br />';
            $subfilelist = array();
            $currdir = opendir($basedir.'/'.$file);
            while (false !== ($subfile = readdir($currdir))) {
                if ($subfile <> ".." && $subfile <> ".") {
                    $subfilelist[] = $file."/".$subfile;
                }
            }
            printfilelist($subfilelist);

        } else {
            $icon = mimeinfo("icon", $file);
            echo '<img src="'. $OUTPUT->pix_url('f/'. $icon) .'" class="icon" alt="'. $strfile .'" /> '. htmlspecialchars($file) .'<br />';
        }
    }
}


function print_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.'"';
    }
    echo '<td align="'.$alignment.'" style="white-space:nowrap "'.$class.'>'.$text.'</td>';
}

function displaydir ($wdir) {
//  $wdir == / or /a or /a/b/c/d  etc

    global $basedir;
    global $id;
    global $USER, $CFG, $OUTPUT;
    global $choose;

    $fullpath = $basedir.$wdir;
    $dirlist = array();

    $directory = opendir($fullpath);             // Find all files
    while (false !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
            continue;
        }

        if (is_dir($fullpath."/".$file)) {
            $dirlist[] = $file;
        } else {
            $filelist[] = $file;
        }
    }
    closedir($directory);

    $strname = get_string('name');
    $strsize = get_string('size');
    $strmodified = get_string('modified');
    $straction = get_string('action');
    $strmakeafolder = get_string('makeafolder');
    $struploadafile = get_string('uploadafile');
    $strselectall = get_string('selectall');
    $strselectnone = get_string('deselectall');
    $strwithchosenfiles = get_string('withchosenfiles');
    $strmovetoanotherfolder = get_string('movetoanotherfolder');
    $strmovefilestohere = get_string('movefilestohere');
    $strdeletecompletely = get_string('deletecompletely');
    $strcreateziparchive = get_string('createziparchive');
    $strrename = get_string('rename');
    $stredit   = get_string('edit');
    $strunzip  = get_string('unzip');
    $strlist   = get_string('list');
    $strrestore= get_string('restore');
    $strchoose = get_string('choose');
    $strfolder = get_string('folder');
    $strfile   = get_string('file');
	$returntotoolsstr = get_string('returntotools', 'tool_sync');

    echo "<form action=\"file.php\" method=\"post\" id=\"dirform\">";
    echo '<div>';
    echo '<input type="hidden" name="choose" value="'.$choose.'" />';
    // echo "<hr align=\"center\" noshade=\"noshade\" size=\"1\" />";
    echo '<hr/>';
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\" class=\"files\">";
    echo '<tr>';
    echo "<th class=\"header\" scope=\"col\"></th>";
    echo "<th class=\"header name\" scope=\"col\">$strname</th>";
    echo "<th class=\"header size\" scope=\"col\">$strsize</th>";
    echo "<th class=\"header date\" scope=\"col\">$strmodified</th>";
    echo "<th class=\"header commands\" scope=\"col\">$straction</th>";
    echo "</tr>\n";

    if ($wdir != "/") {
        $dirlist[] = '..';
    }

    $count = 0;

    if (!empty($dirlist)) {
        asort($dirlist);
        foreach ($dirlist as $dir) {
            echo "<tr class=\"folder\">";

            if ($dir == '..') {
                $fileurl = rawurlencode(dirname($wdir));
                print_cell();
                // alt attribute intentionally empty to prevent repetition in screen reader
                print_cell('left', '<a href="'.$CFG->wwwroot.'/admin/tool/sync/file.php?id='.$id.'&amp;wdir='.$fileurl.'&amp;choose='.$choose.'"><img src="'.$OUTPUT->pix_url('f/parent') . '" class="icon" alt="" />&nbsp;'.get_string('parentfolder').'</a>', 'name');
                print_cell();
                print_cell();
                print_cell();

            } else {
                $count++;
                $filename = $fullpath."/".$dir;
                $fileurl  = rawurlencode($wdir."/".$dir);
                $filesafe = rawurlencode($dir);
                $filesize = display_size(get_directory_size("$fullpath/$dir"));
                $filedate = userdate(filemtime($filename), get_string('strftimedatetime'));
                if ($wdir.$dir === '/moddata') {
                    print_cell();
                } else {
                    print_cell('center', "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" />", 'checkbox');
                }
                print_cell("left", "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$fileurl&amp;choose=$choose\"><img src=\"".$OUTPUT->pix_url('f/folder')."\" class=\"icon\" alt=\"$strfolder\" />&nbsp;".htmlspecialchars($dir)."</a>", 'name');
                print_cell('right', $filesize, 'size');
                print_cell('right', $filedate, 'date');
                if ($wdir.$dir === '/moddata') {
                    print_cell();
                } else { 
                    print_cell('right', "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$filesafe&amp;action=rename&amp;choose=$choose\">$strrename</a>", 'commands');
                }
            }

            echo "</tr>";
        }
    }


    if (!empty($filelist)) {
        asort($filelist);
        foreach ($filelist as $file) {

            $icon = mimeinfo("icon", $file);

            $count++;
            $filename    = $fullpath."/".$file;
            $fileurl     = trim($wdir, '/')."/$file";
            $filesafe    = rawurlencode($file);
            $fileurlsafe = rawurlencode($fileurl);
            $filedate    = userdate(filemtime($filename), get_string('strftimedatetime'));

            $selectfile = trim($fileurl, '/');

            echo "<tr class=\"file\">";

            print_cell('center', "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" />", 'checkbox');
            echo "<td align=\"left\" style=\"white-space:nowrap\" class=\"name\">";

            $ffurl = get_syncfile_url($fileurl);
			echo  $OUTPUT->action_icon($ffurl, new pix_icon("f/$icon", $strfile));
            echo  $OUTPUT->action_link($ffurl, htmlspecialchars($file));
            echo '</td>';

            $file_size = filesize($filename);
            print_cell('right', display_size($file_size), 'size');
            print_cell('right', $filedate, 'date');

            if ($choose) {
                $edittext = "<strong><a onclick=\"return set_value('$selectfile')\" href=\"#\">$strchoose</a></strong>&nbsp;";
            } else {
                $edittext = '';
            }


            if ($icon == 'text.gif' || $icon == 'html.gif') {
                $edittext .= "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=edit&amp;choose=$choose\">$stredit</a>";
            } else if ($icon == 'zip.gif') {
                $edittext .= "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=unzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strunzip</a>&nbsp;";
                $edittext .= "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=listzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strlist</a> ";
                if (!empty($CFG->backup_version) and has_capability('moodle/site:restore', context_system::instance())) {
                    $edittext .= "<a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$filesafe&amp;action=restore&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strrestore</a> ";
                }
            }

            print_cell('right', "$edittext <a href=\"{$CFG->wwwroot}/admin/tool/sync/file.php?id=$id&amp;wdir=$wdir&amp;file=$filesafe&amp;action=rename&amp;choose=$choose\">$strrename</a>", 'commands');

            echo '</tr>';
        }
    }
    echo '</table>';
    echo '<hr />';
    //echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";

    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\">";
    echo '<tr><td>';
    echo '<input type="hidden" name="choose" value="'.$choose.'" />';
    echo "<input type=\"hidden\" name=\"wdir\" value=\"$wdir\" /> ";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
    $options = array (
                   'move' => "$strmovetoanotherfolder",
                   'delete' => "$strdeletecompletely",
                   'zip' => "$strcreateziparchive"
               );
    if (!empty($count)) {

        echo html_writer::select($options, 'action', '', "$strwithchosenfiles...", array('onchange' => "getElementById('dirform').submit()"));
        echo '<div id="noscriptgo" style="display: inline;">';
        echo '<input type="submit" value="'.get_string('go').'" />';
        echo '<script type="text/javascript">'.
               "\n//<![CDATA[\n".
               'document.getElementById("noscriptgo").style.display = "none";'.
               "\n//]]>\n".'</script>';
        echo '</div>';

    }
    echo '</td></tr></table>';
    echo '</div>';
    echo '</form>';
    echo '<table border="0" cellspacing="2" cellpadding="2" width="640"><tr>';
    echo '<td align="center">';
    if (!empty($USER->fileop) and ($USER->fileop == 'move') and ($USER->filesource <> $wdir)) {
        echo '<form action="file.php" method="get">';
        echo '<div>';
        echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
        echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
        echo " <input type=\"hidden\" name=\"action\" value=\"paste\" />";
        echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
        echo " <input type=\"submit\" value=\"$strmovefilestohere\" />";
        echo "</div>";
        echo "</form>";
    }
    echo "</td>";
    echo "<td align=\"right\">";
        echo "<form action=\"file.php\" method=\"get\">";
        echo "<div>";
        echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
        echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
        echo " <input type=\"hidden\" name=\"action\" value=\"makedir\" />";
        echo " <input type=\"submit\" value=\"$strmakeafolder\" />";
        echo "</div>";
        echo "</form>";
    echo "</td>";
    echo "<td align=\"right\">";
        echo "<form action=\"file.php\" method=\"get\">"; //dummy form - alignment only
        echo "<fieldset class=\"invisiblefieldset\">";
        echo " <input type=\"button\" value=\"$strselectall\" onclick=\"checkall();\" />";
        echo " <input type=\"button\" value=\"$strselectnone\" onclick=\"uncheckall();\" />";
        echo "</fieldset>";
        echo "</form>";
    echo "</td>";
    echo "<td align=\"right\">";
        echo "<form action=\"file.php\" method=\"get\">";
        echo "<div>";
        echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
        echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
        echo " <input type=\"hidden\" name=\"action\" value=\"upload\" />";
        echo " <input type=\"submit\" value=\"$struploadafile\" />";
        echo "</div>";
        echo "</form>";
    echo "</td>";
    echo "<td align=\"right\">";
        echo "<form action=\"{$CFG->wwwroot}/admin/tool/sync/index.php\" method=\"get\">";
        echo "<div>";
        echo ' <input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
        echo " <input type=\"submit\" value=\"$returntotoolsstr\" />";
        echo "</div>";
        echo "</form>";
    echo "</td>";
    echo '</tr>';
    echo '</table>';
    echo '<hr/>';
    //echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";

}

function syncfile_not_found() {
    global $CFG;
    header('HTTP/1.0 404 not found');
    print_error('filenotfound', 'error', $CFG->wwwroot.'/admin/tool/sync/index.php'); //this is not displayed on IIS??
}

?>

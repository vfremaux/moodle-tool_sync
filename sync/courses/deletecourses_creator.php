<?php	   

/**
* @author Funck Thibaut
* @package enrol
* @subpackage sync
*/

	require_once '../../../../config.php';
	require_once $CFG->dirroot.'/course/lib.php';
	require_once $CFG->libdir.'/adminlib.php';	
	require_once $CFG->dirroot.'/admin/tool/sync/courses/lib.php';
	require_once $CFG->dirroot.'/admin/tool/sync/lib.php';

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

	$PAGE->requires->js('/admin/tool/sync/courses/js.js');

	$selection = optional_param_array('selection', '', PARAM_TEXT);
	if($selection) {
		create_course_deletion_file($selection);
	}

	$url = $CFG->wwwroot.'/admin/tool/sync/courses/deletecourses_creator.php';
	$PAGE->navigation->add(get_string('synchronization', 'tool_sync'), $CFG->wwwroot.'/admin/tool/sync/index.php');
	$PAGE->navigation->add(get_string('builddeletefile', 'tool_sync'));
	$PAGE->set_context(null);
	$PAGE->set_url($url);
	$PAGE->set_title("$site->shortname");
	$PAGE->set_heading($site->fullname);
	echo $OUTPUT->header();	

	echo $OUTPUT->heading(get_string('deletefilebuilder', 'tool_sync'));
	
?>

<form name="form_exemple" method="post" action="#" onSubmit="return select_all(this)">
<center>
<table class="generaltable" width="80%">
	<tr class="r0" valign="top">
		<th class="header c0" align="left">	
			<?php echo get_string('shortname'); ?>
		</th>
		<th class="header c1" align="left">
			<?php echo get_string('fullname'); ?>
		</th>
		<th class="header c2" align="left" colspan="5">
			<?php echo get_string('roles'); ?>
		</th>
	</tr>
	<?php
	$courses = sync_get_all_courses();	
	$class = 'r0' ;
	$distinctcourses = array();
	foreach ($courses as $c){
		$class = ($class == 'r0') ? 'r1' : 'r0' ;
		if (@$prevc->shortname != $c->shortname){
			echo '</tr>';
			echo '<tr valign="top" class="'.$class.'">';
			echo '<td align="left" class="c0">'.$c->shortname .'</td><td align="left" class="c1"> '.$c->fullname .'</td>';
		} else {
			echo "<td>$c->rolename : $c->people</td>";
		}
		$distinctcourses[$c->shortname] = $c;
		$prevc = $c;
	}
	?>
</table>
<hr width="90%"/>
<table width="90%">
	<tr valign="top">
		<td>
		</td>
		<td align="center">
			<?php print_string('choosecoursetodelete', 'tool_sync') ?>
		</td>
		<td align="center">
			<?php print_string('selecteditems', 'tool_sync') ?>
		</td>
	</tr>
	<tr valign="top">
		<td align="center">
			<select style="height:200px" name="courselist" multiple OnDblClick="javascript:selectcourses(this.form.courselist,this.form.selection)" >
				<?php 
				foreach ($distinctcourses as $c){
					switch (0 + @$CFG->tool_sync_course_filedeleteidentifier){
						case 0 : $cid = $c->idnumber; break;
						case 1 : $cid = $c->shortname; break;
						case 2 : $cid = $c->id; break;
					}
					echo '<option value="'.$cid.'">('.$c->idnumber.') '.$c->shortname.' - '.$c->fullname.'</option>';
				}
				?>
			</select>
		</td>
		<td align="center">
			<table>
				<tr valign="top">
					<td>
						<input class="button" type="button" name="select" value=" >> " OnClick="javascript:selectcourses(this.form.courselist,this.form.selection)">
					</td>
				</tr>
				<tr>
					<td>
						<input class="button" type="button" name="deselect" value=" << " OnClick="javascript:selectcourses(this.form.selection,this.form.courselist)">
					</td>
				</tr>
			</table>
		</td>
		<td align="center">
			<select name="selection" multiple  style="height:200px" OnDblClick="javascript:selectcourses(this.form.selection,this.form.courselist)"></select>
		</td>
	</tr>
</table>
<p><input type="submit" value="<?php print_string('generate', 'tool_sync') ?>"/></p>
</center>
</form>

<?php

sync_print_return_button();

echo $OUTPUT->footer();
?>
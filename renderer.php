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

class tool_sync_renderer {
    
    function print_return_button() {
        global $CFG, $OUTPUT;

        $str = '<center>';
        $str .= '<hr/>';
        $str .= '<br/>';
        $url = new moodle_url($CFG->wwwroot.'/admin/tool/sync/index.php', array('sesskey' => sesskey()));
        $text = get_string('returntotools', 'tool_sync');
        $single_button = new single_button($url, $text, 'get');
        $str .= $OUTPUT->render($single_button);
        $str .= '<br/>';
        $str .= '</center>';

        return $str;
    }
    
    function print_delete_course_creator($syncconfig) {

        $str = '<form name="form_exemple" method="post" action="#" onSubmit="return select_all(this)">';
        $str .= '<center>';
        $str .= '<table class="generaltable" width="80%">';
        $str .= '<tr class="r0" valign="top">';
        $str .= '<th class="header c0" align="left">';
        $str .= get_string('shortname');
        $str .= '</th>';
        $str .= '<th class="header c1" align="left">';
        $str .= get_string('fullname');
        $str .=  '</th>';
        $str .= '<th class="header c2" align="left" colspan="5">';
        $str .= get_string('roles');
        $str .= '</th>';
        $str .= '</tr>';

        $COURSE_SORTS = array(
            0 => 'idnumber',
            1 => 'shortname',
            2 => 'id'
        );

        $sortorder = $COURSE_SORTS[0 + @$syncconfig->course_filedeleteidentifier];

        $courses = tool_sync_get_all_courses($sortorder);
        $class = 'r0' ;
        $distinctcourses = array();
        foreach ($courses as $c){
            $class = ($class == 'r0') ? 'r1' : 'r0' ;
            if (@$prevc->shortname != $c->shortname){
                $str .= '</tr>';
                $str .= '<tr valign="top" class="'.$class.'">';
                $str .= '<td align="left" class="c0">'.$c->shortname .'</td><td align="left" class="c1"> '.$c->fullname .'</td>';
            } else {
                $str .= "<td>$c->rolename : $c->people</td>";
            }
            $distinctcourses[$c->shortname] = $c;
            $prevc = $c;
        }

        $str .= '</table>';
        $str .= '<hr width="90%" />';
        $str .= '<table width="90%">';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= get_string('choosecoursetodelete', 'tool_sync');
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= get_string('selecteditems', 'tool_sync');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td align="center">';
        $str .= '<select style="height:200px" name="courselist" multiple OnDblClick="javascript:selectcourses(this.form.courselist,this.form.selection)" >';
        foreach ($distinctcourses as $c) {
            switch (0 + @$syncconfig->course_filedeleteidentifier) {
                case 0 : $cid = $c->idnumber; break;
                case 1 : $cid = $c->shortname; break;
                case 2 : $cid = $c->id; break;
            }
            $str .= '<option value="'.$cid.'">('.$c->idnumber.') '.$c->shortname.' - '.$c->fullname.'</option>';
        }
        $str .= '</select>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= '<table>';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= '<input class="button" type="button" name="select" value=" >> " OnClick="javascript:selectcourses(this.form.courselist,this.form.selection)">';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td>';
        $str .= '<input class="button" type="button" name="deselect" value=" << " OnClick="javascript:selectcourses(this.form.selection,this.form.courselist)">';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= '<select name="selection" multiple  style="height:200px" OnDblClick="javascript:selectcourses(this.form.selection,this.form.courselist)"></select>';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '<p><input type="submit" value="'.get_string('generate', 'tool_sync').'"/></p>';
        $str .= '</center>';
        $str .= '</form>';

        return $str;
    }

    /**
     *
     *
     *
     */
    function print_reset_course_creator($syncconfig) {

        $str = '';

        $str .= '<form name="form_reset" method="post" action="#" onSubmit="return select_all(this)">';
        $str .= '<center>';
        $str .= '<table class="generaltable" width="80%">';
        $str .= '<tr class="r0" valign="top">';
        $str .= '<th class="header c0" align="left">';
        $str .= get_string('shortname');
        $str .= '</th>';
        $str .= '<th class="header c1" align="left">';
        $str .= get_string('fullname');
        $str .= '</th>';
        $str .= '<th class="header c2" align="left" colspan="5">';
        $str .= get_string('roles');
        $str .= '</th>';
        $str .= '</tr>';

        $COURSE_SORTS = array(
            0 => 'idnumber',
            1 => 'shortname',
            2 => 'id'
        );

        $sortorder = $COURSE_SORTS[0 + @$syncconfig->course_resetfileidentifier];

        $courses = tool_sync_get_all_courses($sortorder);
        $class = 'r0' ;
        $distinctcourses = array();
        foreach ($courses as $c){
            $class = ($class == 'r0') ? 'r1' : 'r0' ;
            if (@$prevc->shortname != $c->shortname) {
                $str .= '</tr>';
                $str .= '<tr valign="top" class="'.$class.'">';
                $str .= '<td align="left" class="c0">'.$c->shortname .'</td><td align="left" class="c1"> '.$c->fullname .'</td>';
            } else {
                $str .= "<td>$c->rolename : $c->people</td>";
            }
            $distinctcourses[$c->shortname] = $c;
            $prevc = $c;
        }

        $str .= '</table>';
        $str .= '<hr width="90%"/>';
        $str .= '<table width="90%">';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= get_string('choosecoursetoreset', 'tool_sync');
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= get_string('selecteditems', 'tool_sync');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td align="center">';
        $str .= '<select style="height:200px" name="courselist" multiple OnDblClick="javascript:selectcourses(this.form.courselist,this.form.selection)" >';
        foreach ($distinctcourses as $c) {
            switch (0 + @$syncconfig->course_resetfileidentifier) {
                case 0 : $cid = $c->idnumber; break;
                case 1 : $cid = $c->shortname; break;
                case 2 : $cid = $c->id; break;
            }
            $str .= '<option value="'.$cid.'">('.$c->idnumber.') '.$c->shortname.' - '.$c->fullname.'</option>';
        }
        $str .= '</select>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= '<table>';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= '<input class="button" type="button" name="select" value=" >> " OnClick="javascript:selectcourses(this.form.courselist,this.form.selection)">';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td>';
        $str .= '<input class="button" type="button" name="deselect" value=" << " OnClick="javascript:selectcourses(this.form.selection,this.form.courselist)">';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= '<select name="selection" multiple  style="height:200px" OnDblClick="javascript:selectcourses(this.form.selection,this.form.courselist)"></select>';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '<p><input type="submit" value="'.get_string('generate', 'tool_sync').'"/></p>';
        $str .= '</center>';
        $str .= '</form>';

        return $str;
    }
}
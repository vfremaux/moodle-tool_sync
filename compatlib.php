<?php

// Prior 3.6
// require_once($CFG->dirroot.'/lib/coursecatlib.php');

function tool_sync_category_role_assignment_changed($roleid, $context) {
    return \core_course_category::role_assignment_changed($roleid, $context);
}

function tool_sync_get_category($catid) {
    return \core_course_category::get($catid);
}
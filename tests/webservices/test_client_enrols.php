<?php

class test_client {

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = '50deb409fb00c0d983fc567672d628cc'; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

    public function test_get_enrolled_users($courseidsource, $courseid) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_enrol_get_enrolled_users',
                        'moodlewsrestformat' => 'json',
                        'courseidsource' => $ocurseidsource,
                        'courseid' => $courseid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_get_enrolled_full_users($courseidsource, $courseid) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_enrol_get_enrolled_full_users',
                        'moodlewsrestformat' => 'json',
                        'courseidsource' => $courseidsource,
                        'courseid' => $courseid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_enrol_user($roleidsource, $roleid, $useridsource, $userid, $courseidsource, $courseid, $method = 'manual', $timestart = 0, $timeend = 0) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_enrol_user_enrol',
                        'moodlewsrestformat' => 'json',
                        'roleidsource' => $roleidsource,
                        'roleid' => $roleid,
                        'courseidsource' => $useridsource,
                        'courseid' => $userid,
                        'courseidsource' => $courseidsource,
                        'courseid' => $courseid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

}

// Effective test scenario.

$client = new test_client();

$client->test_create_courses();
$client->test_reset_courses();

$client->test_create_users();

$client->test_update_users();

$client->test_enrol_users();
$client->test_rolechange_users();

$client->test_suspend_users();
$client->test_delete_users();

$client->test_create_cohorts();

$client->test_delete_courses();

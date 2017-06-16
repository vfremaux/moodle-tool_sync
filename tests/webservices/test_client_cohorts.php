<?php

class test_client_cohorts {

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = ''; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

    public function test_bind_cohort($chidsource, $chid, $cidsource, $cid) {

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
                        'chidsource' => $ocurseidsource,
                        'chid' => $courseid,
                        'cidsource' => $ocurseidsource,
                        'cid' => $courseid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_get_cohort_users($chidsource, $chid) {

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
                        'chidsource' => $courseidsource,
                        'chid' => $courseid,
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

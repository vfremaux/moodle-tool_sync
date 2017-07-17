<?php

require_once('test_client_base.php');

class test_client_cohorts extends test_client {

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = 'b401cbe98bd1385c280ddbcb66856e35'; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

    public function test_bind_cohort($chidsource, $chid, $cidsource, $cid, $ridsource, $rid, $method = 'cohort') {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_cohort_bind',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'ridsource' => $ridsource,
                        'rid' => $rid,
                        'method' => $method,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_unbind_cohort($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_cohort_unbind',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'method' => $method,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_suspend_enrol($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_cohort_suspend_enrol',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'method' => $method,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_restore_enrol($chidsource, $chid, $cidsource, $cid, $method = 'cohort') {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_cohort_restore_enrol',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'method' => $method,
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
                        'wsfunction' => 'tool_sync_cohort_get_users',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_delete_cohort($chidsource, $chid) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_cohort_delete',
                        'moodlewsrestformat' => 'json',
                        'chidsource' => $chidsource,
                        'chid' => $chid,
        );

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

}

// Effective test scenario.

$client = new test_client_cohorts();

$ix = 1;

echo "\n\nTest $ix ########### GET USERS\n";
$ix++;
$client->test_get_cohort_users('idnumber', 'TESTCOHORT');

echo "\n\nTest $ix ########### BIND\n";
$ix++;
$client->test_bind_cohort('idnumber', 'TESTCOHORT', 'shortname', 'TESTBIND', 'shortname', 'student', 'cohort');

echo "\n\nTest $ix ########### UNBIND\n";
$ix++;
$client->test_unbind_cohort('idnumber', 'TESTCOHORT', 'shortname', 'TESTBIND', 'cohort');

echo "\n\nTest $ix ########### BIND NON STANDARD\n";
$ix++;
$client->test_bind_cohort('idnumber', 'TESTCOHORT', 'shortname', 'TESTBIND', 'shortname', 'student', 'cohortrestricted');

echo "\n\nTest $ix ########### SUSPEND/RESTORE\n";
$ix++;
$client->test_suspend_enrol('idnumber', 'TESTCOHORT', 'shortname', 'TESTBIND');
$client->test_restore_enrol('idnumber', 'TESTCOHORT', 'shortname', 'TESTBIND');

echo "\n\nTest $ix ########### DELETE\n";
$ix++;
$client->test_delete_cohort('idnumber', 'TESTCOHORTTODELETE');

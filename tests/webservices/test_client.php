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

    public function test_set_config($service, $key, $value) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_set_config',
                        'moodlewsrestformat' => 'json',
                        'service' => $service,
                        'confkey' => $key,
                        'confvalue' => $value);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_create_courses() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/course_create_sample.csv';

        $this->load_file($path);

        $this->test_set_config('courses', 'fileuploadlocation', 'course_create_sample.csv');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'courses',
                        'action' => 'create');

        return $this->send($serviceurl, $params);
    }

    public function test_create_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/user_create_sample.csv';

        $this->load_file($path);

        $this->test_set_config('users', 'filelocation', 'user_create_sample.csv');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'users',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_update_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/user_update_sample.csv';

        $this->load_file($path);

        $this->test_set_config('users', 'filelocation', 'user_update_sample.csv');
        $this->test_set_config('users', 'primaryidentity', 'username');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'users',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_enrol_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/enrol_sample.csv';

        $this->load_file($path);

        $this->test_set_config('enrols', 'filelocation', 'enrol_sample.csv');
        $this->test_set_config('enrols', 'courseidentifier', 'shortname');
        $this->test_set_config('enrols', 'useridentifier', 'idnumber');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'enrols',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_suspend_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/user_suspend_sample.csv';

        $this->load_file($path);

        $this->test_set_config('users', 'filelocation', 'user_suspend_sample.csv');
        $this->test_set_config('users', 'primaryidentity', 'username');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'users',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_delete_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // Add a file and commit it.
        $dir = getcwd();
        $path = $dir.'/user_deleted_sample.csv';

        $this->load_file($path);

        $this->test_set_config('users', 'filelocation', 'user_deleted_sample.csv');
        $this->test_set_config('users', 'primaryidentity', 'username');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'users',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_rolechange_users() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/enrol_change_sample.csv';

        $this->load_file($path);

        $this->test_set_config('enrols', 'fileuploadlocation', 'enrol_change_sample.csv');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'enrols',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_create_cohorts() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // Add a file and commit it.
        $dir = getcwd();
        $path = $dir.'/cohort_create_sample.csv';

        $this->load_file($path);

        $this->test_set_config('cohorts', 'filelocation', 'cohort_create_sample.csv');
        $this->test_set_config('cohorts', 'useridentifier', 'idnumber');
        $this->test_set_config('cohorts', 'cohortidentifier', 'idnumber');
        $this->test_set_config('cohorts', 'autocreate', 1);

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'cohorts',
                        'action' => 'sync');

        return $this->send($serviceurl, $params);
    }

    public function test_reset_courses() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // Add a file and commit it.
        $dir = getcwd();
        $path = $dir.'/course_reset_sample.csv';

        $this->load_file($path);

        $this->test_set_config('courses', 'fileresetlocation', 'course_reset_sample.csv');
        $this->test_set_config('courses', 'fileresetidentifier', 'shortname');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'courses',
                        'action' => 'reset');

        return $this->send($serviceurl, $params);
    }

    public function test_delete_courses() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // add a file and commit it
        $dir = getcwd();
        $path = $dir.'/course_delete_sample.csv';

        $this->load_file($path);

        $this->test_set_config('courses', 'filedeletelocation', 'course_delete_sample.csv');
        $this->test_set_config('courses', 'filedeleteidentifier', 'shortname');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'courses',
                        'action' => 'delete');

        return $this->send($serviceurl, $params);
    }

    protected function send($serviceurl, $params) {
        $ch = curl_init($serviceurl);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        echo "Firing CUrl $serviceurl ... \n";
        if (!$result = curl_exec($ch)) {
            echo "CURL Error : ".curl_errno($ch).' '.curl_error($ch)."\n";
            return;
        }

        if (preg_match('/EXCEPTION/', $result)) {
            echo $result;
            return;
        }
        echo "Pre json : $result \n";

        $result = json_decode($result);
        if (!is_scalar($result)) {
            print_r($result);
            echo "\n";
        }
        return $result;
    }

    protected function load_file($path) {
        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $uploadurl = $this->t->baseurl.$this->t->uploadservice;

        $params = array('token' => $this->t->wstoken,
                        'itemid' => 0,
                        'filearea' => 'draft');

        $ch = curl_init($uploadurl);

        $curlfile = new CURLFile($path, 'x-application/zip', basename($path));
        $params['resourcefile'] = $curlfile;

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        echo "Firing CUrl $uploadurl ... \n";
        if (!$result = curl_exec($ch)) {
            echo "CURL Error : ".curl_error($ch)."\n";
            return;
        }

        $result = json_decode($result);
        $filerec = array_pop($result);

        // Now commit the file.

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_commit_file',
                        'moodlewsrestformat' => 'json',
                        'draftitemid' => $filerec->itemid);

        $commiturl = $this->t->baseurl.$this->t->service;

        $this->send($commiturl, $params);
    }
}

// Effective test scenario.

$client = new test_client();

$client->test_create_courses();
$client->test_reset_courses();

$client->test_create_users();
/*
$client->test_update_users();

$client->test_enrol_users();
$client->test_rolechange_users();

$client->test_suspend_users();
$client->test_delete_users();
*/

$client->test_create_cohorts();

// $client->test_delete_courses();

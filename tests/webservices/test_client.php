<?php

require_once('test_client_base.php');

class test_client extends test_client_base {

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

    public function test_bind_metacourses() {

        $serviceurl = $this->t->baseurl.$this->t->service;

        // Add a file and commit it.
        $dir = getcwd();
        $path = $dir.'/course_metabindings_sample.csv';

        $this->load_file($path);

        $this->test_set_config('courses', 'filefilemetabindinglocationlocation', 'course_metabindings_sample.csv');

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_process',
                        'moodlewsrestformat' => 'json',
                        'service' => 'courses',
                        'action' => 'bindmetas');

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
        $path = $dir.'/user_delete_sample.csv';

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
}

// Effective test scenario.

echo "STARTING:\n";
$client = new test_client();

echo "COURSE CREATE:\n";
$client->test_create_courses();
echo "COURSE BIND METAS:\n";
$client->test_bind_metacourses();

echo "COURSE RESET:\n";
$client->test_reset_courses();

echo "USER CREATE:\n";
$client->test_create_users();

echo "USER UPDATE:\n";
$client->test_update_users();

echo "ENROL:\n";
$client->test_enrol_users();
// $client->test_rolechange_users();

echo "USER SUSPEND:\n";
$client->test_suspend_users();
echo "USER DELETE:\n";
$client->test_delete_users();

echo "COHORT CREATE:\n";
$client->test_create_cohorts();

echo "COURSE DELETE:\n";
$client->test_delete_courses();

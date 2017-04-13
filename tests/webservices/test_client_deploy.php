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

    public function test_deploy() {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $repls = array(
            'section0:summary:data1' => 'Test insertion1',
            'cm:{course:idnumber}_dist:docurl' => 'Test insertion2',
            'cm:{course:idnumber}_dist:giturl' => 'Test insertion3',
        );

        $replacements = json_encode($repls);

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'tool_sync_deploy_course',
                        'moodlewsrestformat' => 'json',
                        'categoryidsource' => 'idnumber',
                        'categoryid' => 'plugins',
                        'templateidsource' => 'shortname',
                        'templateid' => 'PLGSUPPORT',
                        'shortname' => 'TESTSHORTNAME',
                        'fullname' => 'Test plugin support deployment',
                        'idnumber' => 'TESTIDNUMBER',
                        'replacements' => $replacements
        );

        $service = $this->t->baseurl.$this->t->service;

        return $this->send($service, $params);
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

$result = $client->test_deploy();

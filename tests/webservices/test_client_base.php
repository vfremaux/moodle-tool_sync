<?php

<<<<<<< HEAD
class test_client {
=======
class test_client_base {
>>>>>>> MOODLE_33_STABLE

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = '50deb409fb00c0d983fc567672d628cc'; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
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
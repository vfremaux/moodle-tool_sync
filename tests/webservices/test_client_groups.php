<?php

require_once('test_client_base.php');

class test_client_groups extends test_client {

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = 'b401cbe98bd1385c280ddbcb66856e35'; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

}

// Effective test scenario.

$client = new test_client_groups();

$ix = 1;

echo "\n\nTest $ix ########### GET USERS\n";
$ix++;

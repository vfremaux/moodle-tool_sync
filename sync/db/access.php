<?php // $Id: access.php,v 1.1 2013-01-18 15:48:24 vf Exp $

$capabilities = array(

    'tool/sync:configure' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager' => CAP_ALLOW
        )
    ),

);

?>

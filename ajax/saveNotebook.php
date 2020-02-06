<?php
require_once('../config.php');
include_once('../corelib/lti.php');
include_once('../lib/minimalSecretManager.php');
include_once('../lib/callpython.php');

$userinfo = checkLTISession($errorMsg);
$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
$userRoot = $CFG['datadir'].'/'.$projectID.'/'.$userID .'/';

$status = unserialize(file_get_contents($userRoot.'status.ser'));
//$status = array('last_run'=>-1, 'plibname'=>"matplotlib\\.pyplot", 'imgnum'=>1);


$output = array();

echo "<pre>".print_r($_REQUEST, true).'</pre>';
$output['alert'] = __FILE__ .' not properly implemented yet.';

//file_put_contents($userRoot.'status.ser', serialize($status));
echo json_encode($output);

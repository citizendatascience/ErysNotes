<?php
require_once('../config.php');
include_once('../corelib/lti.php');
include_once('../lib/minimalSecretManager.php');
include_once('../lib/callpython.php');
include_once('../lib/iNotebook.php');

if(session_status()==PHP_SESSION_NONE)
    session_start(['use_only_cookies'=>0,'use_trans_sid'=>1]);
$userinfo = checkLTISession($errorMsg);
$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
$userRoot = $CFG['datadir'].'/'.$projectID.'/'.$userID .'/';

$status = unserialize(file_get_contents($userRoot.'status.ser'));
//$status = array('last_run'=>-1, 'plibname'=>"matplotlib\\.pyplot", 'imgnum'=>1);

$output = array();

$nbook = iNotebook::fromErysJson($_REQUEST['notebook']);

if((isset($status['nb']))&&(strlen($status['nb'])))
    file_put_contents($userRoot . $status['nb'], $nbook->toJSON());

$output['alert'] = "Notebook {$status['nb']} saved.";

echo json_encode($output);

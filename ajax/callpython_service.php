<?php
require_once('../config.php');
include_once('../corelib/lti.php');
include_once('../lib/minimalSecretManager.php');
include_once('../lib/callpython.php');

if(session_status()==PHP_SESSION_NONE)
    session_start(['use_only_cookies'=>0,'use_trans_sid'=>1]);
$userinfo = checkLTISession($errorMsg);
if(!isset($userinfo->params['oauth_consumer_key']))
    exit(json_encode(array('alert'=>'Failed to load session - possibly browser security settings have blocked this.'.'<pre>'.print_r($_REQUEST, true).'</pre>')));
$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
$userRoot = $CFG['datadir'].'/'.$projectID.'/'.$userID .'/';

$status = unserialize(file_get_contents($userRoot.'status.ser'));
//$status = array('last_run'=>-1, 'plibname'=>"matplotlib\\.pyplot", 'imgnum'=>1);

$output = array();

//request contains id, source, pyidx
//pyidx should be $status['last_run'] + 1
$getimages = array();

$source = updateToErys($_REQUEST['source'], $status['plibname'], $status['imgnum'], $getimages);

$result = json_decode(runCell($projectID, $userID, $source, $getimages));

$output[$_REQUEST['id'].'_output'] = '';
if(strlen($result->errors))
    $output[$_REQUEST['id'].'_output'] .= '<div class="outputPart stream"><pre style="color: #aa0000;">'.print_r($result->errors, true).'</pre><!--end-stream--></div>';

$output[$_REQUEST['id'].'_output'] .=  "<div class=\"outputPart stream\"><pre>".print_r($result->output, true)."</pre><!--end-stream--></div>";
$output[$_REQUEST['id'].'_info'] = $result->runcount;
foreach($getimages as $img)
{
    if(isset($result->$img))
        $output[$_REQUEST['id'].'_output'] .= "<div class=\"outputPart display_data\"><div class='image'><img src='data:image/png;base64, {$result->$img}'/></div><!--end-display_data--></div>";
}
if((strlen($result->errors)==0)&&(strlen($result->output)==0)&&(sizeof($getimages)==0))
{
    $output[$_REQUEST['id'].'_output'] = '<div class="outputPart stream"><span style="color: #008800; font-size:smaller;">Run OK. (No output or errors returned.)</span><!--end-stream--></div>';
}

file_put_contents($userRoot.'status.ser', serialize($status));

echo json_encode($output);


function updateToErys($source, &$plibname, &$imgnum, &$getimages)
{
    $getimages = array();
    if(preg_match('/import\s+matplotlib.pyplot(\s+as\s*(\w+))?/sm', $source, $matches))
    {
        $plibname = $matches[2];
    }
    $rex = '/^(\s*)'.$plibname.'\.show\s*\(\s*\)/sm';
    while(preg_match($rex, $source, $matches))
    {
        $source = preg_replace($rex, "{$matches[1]}{$plibname}.savefig('out{$imgnum}.png', dpi=150); {$matches[1]}plt.clf()", $source, 1);
        $getimages[] = "out{$imgnum}.png";
        $imgnum++;
    }
    return $source;
}
 
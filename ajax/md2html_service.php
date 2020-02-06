<?php
require_once('../config.php');
include('../lib/md2html.php');
include_once('../corelib/lti.php');
include_once('../lib/minimalSecretManager.php');

$userinfo = checkLTISession($errorMsg);

$imageBaseURL = $CFG['imgroot'].'/'.$_SESSION['projectID'].'/';


$md = $_REQUEST['markdown'];

$out = wiki2html($md, $imageBaseURL);

echo json_encode(array($_REQUEST['target'] => $out));

// From https://php.quicoto.com/how-to-detect-internet-explorer-with-php/
function ae_detect_ie()
{
	if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
		return true;
	else
		return false;
}

function wiki2html($src, $imageBaseURL)
{
    $mdConv = new md2html();
    $mdConv->variables['imgdir'] = $imageBaseURL;
    return $mdConv->Convert($src);
}
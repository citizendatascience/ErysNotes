<?php
require_once('../config.php');
include_once('../corelib/lti.php');
include_once('../lib/minimalSecretManager.php');
include_once('../lib/callpython.php');
include_once('../lib/iNotebook.php');

$userinfo = checkLTISession($errorMsg);
$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
$userRoot = $CFG['datadir'].'/'.$projectID.'/'.$userID .'/';


$status = unserialize(file_get_contents($userRoot.'status.ser'));
//$status = array('last_run'=>-1, 'plibname'=>"matplotlib\\.pyplot", 'imgnum'=>1);

$output = array();

if(isset($_REQUEST['notebook']))
{
    $nbook = iNotebook::fromErysJson($_REQUEST['notebook']);
    if((isset($status['nb']))&&(strlen($status['nb'])))
        file_put_contents($userRoot . $status['nb'], $nbook->toJSON());
}
//$output['alert'] = "Notebook {$status['nb']} saved.";
else
{
    $zipname = substr($status['nb'], 0, strrpos($status['nb'], '.'));
    createZip($userRoot, $zipname);

    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$zipname.zip");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header("Content-length: " . filesize($userRoot."$zipname.zip"));
    readfile($userRoot."$zipname.zip");
    exit();
}

//echo json_encode($output);

function createZip($userRoot, $name)
{
    $files = get_all_files($userRoot);
    $zip = new ZipArchive();
    $zip->open($userRoot.$name.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach($files as $file)
    {
        $zip->addFile("{$userRoot}{$file}", $file);
    }
    $zip->close();
}

function get_all_files($root, $dir='')
{
    $files = array();
    $dh = new DirectoryIterator($root . $dir);
    // Dirctary object
    foreach ($dh as $item) {
        if (!$item->isDot()) {
            if ($item->isDir()) {
                $files = array_merge($files, get_all_files($root, "$dir$item/"));
            } else {

                if((pathinfo($item)['extension'] != 'ser')&&((pathinfo($item)['extension'] != 'zip')))
                    $files[] = $dir . $item->getFilename();
            }
        }
    }
    return $files;
}
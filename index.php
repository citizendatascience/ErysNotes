<?php
require_once('config.php');
require_once('corelib/templateMerge.php');
require_once('lib/md2html.php');
require_once('lib/iNotebook.php');
include_once('corelib/lti.php');
include_once('lib/tmpDevSecretManager.php');
include_once('lib/forms.php');
include_once('lib/callpython.php');

$userinfo = checkLTISession($errorMsg);
if($userinfo == false)
    exit("LTI Launch failed.<br/>$errorMsg");

$template = new templateMerge('theme/template2.html');

$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
$_SESSION['projectID'] = $projectID;
$_SESSION['userID'] = $userID;
$template->addScript('scripts/ace/ace.js');
$template->addScript('scripts/ajax.js');
$template->addScript('scripts/nb_outlineblocks.js');
$template->addScript('scripts/ErysBlocks.js');

//echo "$projectID $userID";
if(strpos($userinfo->params['roles'], 'Instructor')!==false)
    GetInstructorView($projectID, $userID,  $template->pageData);
else
    GetLearnerView($projectID, $userID,  $template->pageData);

if(error_get_last() == null)
    echo $template->render();
else
    var_dump(error_get_last());


function GetInstructorView($projectID, $userID, &$pageData)
{
    global $CFG;
    if(!file_exists($CFG['datadir'].'/'.$projectID))
    {
        mkdir($CFG['datadir'].'/'.$projectID);
        mkdir($CFG['datadir'].'/'.$projectID.'/files');
    }
    if(!file_exists($CFG['imgdir'].'/'.$projectID))
    {
        mkdir($CFG['imgdir'].'/'.$projectID);
    }
    $projRoot = $CFG['datadir'].'/'.$projectID.'/';
    $projFilesRoot = $CFG['datadir'].'/'.$projectID.'/files/';
    $projImgRoot = $CFG['imgdir'].'/'.$projectID.'/';
    //proces any data file delete request
    if(isset($_REQUEST['del']))
        unlink($projFilesRoot.$_REQUEST['del']);
    if(isset($_REQUEST['imgdel']))
        unlink($projImgRoot.$_REQUEST['del']);

    if((isset($_REQUEST['view']))&&($_REQUEST['view']=='learner'))
    {
        $pageData['leftFull'] = "<a href='index.php'>View as instructor</a>";
        GetLearnerView($projectID, $userID, $pageData);
    }
    else
    {
        $pageData['leftFull'] = "<a href='index.php?view=learner'>View as a learner</a>";
        $nb = false; // notebook name;
        $files = array(); // other data files
        $required_images = array();
        $images = array();
        $folder = '';
        getProjFiles($projFilesRoot, $nb, $files);
        getImgFiles($projImgRoot, $nb, $images);
        $pageData['main'] = "<h2>Configure activity</h2><p>Please upload a Jupyter notebook file and (optional) data files.</p>";
        if(configureActivity_submitted())
        {
            $nbinfo = false;
            $datainfo = false;
            $newimg = false;
            if(update_from_configureActivity($nbinfo, $datainfo, $folder, $newimg))
            {
                if($nbinfo != false)
                {
                    if($nb != false) // delete old version
                    {
                        unlink($projFilesRoot.$nb);
                        $nb = false;
                    }
                    if(move_uploaded_file($nbinfo['tmp_name'], $projFilesRoot.$nbinfo['name']))
                        $nb = $nbinfo['name'];
                }
                if($datainfo != false)
                {
                    $folder = trim($folder, ' /\\');
                    if($folder != '')
                    {
                        checkDirExists($projFilesRoot.$folder);
                        $folder .= '/';
                    }
                    if(move_uploaded_file($datainfo['tmp_name'], $projFilesRoot.$folder.$datainfo['name']))
                        $files[] = $folder.$datainfo['name'];
                }
                if($newimg != false)
                {
                    if(move_uploaded_file($newimg['tmp_name'], $projImgRoot.$newimg['name']))
                        $images[] = $newimg['name'];
                }
            }
        }

        if($nb != false)
        {
            $notebook = new iNotebook(file_get_contents($projFilesRoot.$nb));
            //file_put_contents($projFilesRoot.$nb.'.2', $notebook->toJSON());
            $required_images = $notebook->checkForImages();
        }
        $pageData['main'] .= show_configureActivity($nb, $files, $folder, $required_images, $images);
        if($nb != false)
            $pageData['main'] .= $notebook->render_preview();
    }
}

function GetLearnerView($projectID, $userID, &$pageData)
{
    global $CFG;
    if(!file_exists($CFG['datadir'].'/'.$projectID.'/files'))
    {
        $pageData['main'] = "This activity needs to be set up by an instructor.";
        return;
    }
    $projFilesRoot = $CFG['datadir'].'/'.$projectID.'/files/';
    if(!file_exists($CFG['datadir'].'/'.$projectID.'/'.$userID))
    {
        mkdir($CFG['datadir'].'/'.$projectID.'/'.$userID);
    }
    $userRoot = $CFG['datadir'].'/'.$projectID.'/'.$userID .'/';
    $nb = false;
    $files = array();
    getProjFiles($projFilesRoot, $nb, $files);
    //#This needs some refactoring for efficency
    if(file_exists($userRoot.'status.ser'))
        $status = unserialize(file_get_contents($userRoot.'status.ser'));
    else
    {
        $status = array('last_run'=>-1, 'plibname'=>"matplotlib\\.pyplot", 'imgnum'=>1);
        file_put_contents($userRoot.'status.ser', serialize($status));
    }
    //Refactor to be a single call to initialisePython
    if(($nb)&&(!file_exists($userRoot.$nb)))
    {
        copy($projFilesRoot.$nb, $userRoot.$nb);
    }
    $filelist = implode(' ', $files);
    initialisePython($projectID, $userID, $filelist);
    if($nb != false)
    {
        if((!isset($status['nb']))||($status['nb']!=$nb))
        {
            $status['nb'] = $nb;
            file_put_contents($userRoot.'status.ser', serialize($status));
        }
        $notebook = new iNotebook(file_get_contents($projFilesRoot.$nb));
        $pageData['toolbar'] = "<div id='blockctrls'></div>";
        $pageData['main'] =  "<div id='blockhost'></div>";
        $pageData['scriptStart'] = 'content = '. $notebook->toErysJson() . ';';
        $pageData['scriptStart'] .= file_get_contents('scripts/nb_blocks_config.js');
    }
}

function getProjFiles($projFilesRoot, &$nb, &$files, $subdir = '')
{
    $d = dir($projFilesRoot);
    while (($file = $d->read()) !== false)
    {
        $fn = $projFilesRoot.$file;
        if(!is_dir($fn))
        {
            $ext = pathinfo($fn, PATHINFO_EXTENSION);
            if(($ext == 'ipynb')&&($subdir == ''))
                $nb= $file;
            else
                $files[] = $subdir.$file;
        }
        else
        {
            if(strpos($file, '.')===false)
            {
                $sd = $subdir . $file . '/';
                getProjFiles($projFilesRoot.'/'.$file, $nb, $files, $sd);
            }
        }
    }
    $d->close();
}

function getImgFiles($projImgRoot, &$nb, &$images)
{
    $d = dir($projImgRoot);
    while (($file = $d->read()) !== false)
    {
        $fn = $projImgRoot.$file;
        if(!is_dir($fn))
        {
            $ext = pathinfo($fn, PATHINFO_EXTENSION);
            if(in_array(strtolower($ext), array('jpg', 'gif', 'png', 'svg', 'jpeg')))
                $images[] = $file;
        }
    }
    $d->close();
}

function checkDirExists($dir)
{
	$tpos = 0;
    while($tpos < strlen($dir))
    {
	    $tpos = strpos($dir, "/", $tpos+1);
	    if($tpos == false)
        	$tpos = strlen($dir);
	    $testdir = substr($dir, 0, $tpos);
        if(!file_exists($testdir))
        {
        	//echo "Attempting to create $testdir<br/>";
        	mkdir($testdir, 0775);
        }
    }
    if((!file_exists($dir))||(!is_dir($dir)))
		return false;
    else
    	return true;
}
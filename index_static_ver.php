<?php
require_once('config.php');
require_once('corelib/templateMerge.php');
require_once('lib/ipynb_reader.php');
require_once('lib/iNotebook.php');
include_once('corelib/lti.php');
include_once('lib/tmpDevSecretManager.php');
include_once('lib/forms.php');

$userinfo = checkLTISession($errorMsg);
if($userinfo == false)
    exit("LTI Launch failed.<br/>$errorMsg");

$template = new templateMerge('theme/template2.html');

$projectID = md5($userinfo->params['oauth_consumer_key'].':'.$userinfo->params['resource_link_id']);
$userID = md5($userinfo->params['user_id']);
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
    $projRoot = $CFG['datadir'].'/'.$projectID.'/';
    $projFilesRoot = $CFG['datadir'].'/'.$projectID.'/files/';
    //proces any data file delete request
    if(isset($_REQUEST['del']))
        unlink($projFilesRoot.$_REQUEST['del']);

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
        getProjFiles($projFilesRoot, $nb, $files);
        $pageData['main'] = "<h2>Configure activity</h2><p>Please upload a Jupyter notebook file and (optional) data files.</p>";
        if(configureActivity_submitted())
        {
            $nbinfo = false;
            $datainfo = false;
            if(update_from_configureActivity($nbinfo, $datainfo))
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
                    if(move_uploaded_file($datainfo['tmp_name'], $projFilesRoot.$datainfo['name']))
                        $files[] = $datainfo['name'];
                }
            }
        }
        $pageData['main'] .= show_configureActivity($nb, $files);

        if($nb != false)
        {
            $notebook = new iNotebook(file_get_contents($projFilesRoot.$nb));
            file_put_contents($projFilesRoot.$nb.'.2', $notebook->toJSON());
            //$pageData['main'] .= $notebook->render_preview();
            $pageData['main'] .= $notebook->updateToErys();
        }
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
    if(($nb)&&(!file_exists($userRoot.$nb)))
    {
        copy($projFilesRoot.$nb, $userRoot.$nb);
    }
    //# probably other files should just get posted to the Python app, but copying here for now
    foreach($files as $f)
    {
        if(!file_exists($userRoot.$f))
        {
            copy($projFilesRoot.$f, $userRoot.$f);
        }
    }
    // Rendering
    if($nb != false)
    {
        $notebook = new iNotebook(file_get_contents($projFilesRoot.$nb));
        $pageData['main'] .= $notebook->render_preview();
        //$pageData['main'] .= $notebook->updateToErys();
    }
}

function getProjFiles($projFilesRoot, &$nb, &$files)
{
    $d = dir($projFilesRoot);
    while (($file = $d->read()) !== false)
    {
        $fn = $projFilesRoot.$file;
        if(!is_dir($fn))
        {
            $ext = pathinfo($fn, PATHINFO_EXTENSION);
            if($ext == 'ipynb')
                $nb= $file;
            else 
                $files[] = $file;
        }
    }
    $d->close(); 
}


?>
<?php
require_once('config.php');
require_once('corelib/templateMerge.php');
require_once('lib/ipynb_reader.php');
require_once('lib/iNotebook.php');
include_once('corelib/lti.php');
include_once('lib/tmpDevSecretManager.php');

$template = new templateMerge('theme/template2.html');

//Temp dev bit
if(!isset($_REQUEST['open'])) $_REQUEST['open'] = 'week_1_finalised/timeseries.ipynb';

if(isset($_REQUEST['open']))
{
    //$template->pageData['main'] = '<pre>'.file_get_contents("{$CFG['datadir']}/{$_REQUEST['open']}").'</pre>';
    // Old notebook reader - really just suitable for viewing
    $notebook = new ipynb_reader("{$CFG['datadir']}/{$_REQUEST['open']}");
    $template->pageData['main'] = $notebook->render_preview(false);

    // New (proper) notebook reader.
    //$notebook = new iNotebook(file_get_contents("{$CFG['datadir']}/{$_REQUEST['open']}"));
    //$template->pageData['main'] = '<pre>'.print_r($notebook, true).'</pre>';
    $_SESSION['notebook'] = $notebook;
}

//$template->pageData['main'] .= '<button id="button1">button1</button>';
$template->pageData['leftFull'] = '<ul><li><a href="index.php">Main page</a></li>';
$template->pageData['leftFull'] .= '<li><a href="brythontest.php">Brython Experiment</a></li>';
$template->pageData['leftFull'] .= '</ul>';
$notebooks = getGlobalNotebooks();
$template->pageData['leftFull'] .= '<ul>';
foreach($notebooks as $nb)
{
    $template->pageData['leftFull'] .= "<li><a href='index.php?open=$nb'>$nb</a></li>";
}
$template->pageData['leftFull'] .= '</ul>';


if(error_get_last() == null)
    echo $template->render();
else
    var_dump(error_get_last());

function getGlobalNotebooks()
{
    global $CFG;
    $nbs = array();

    $d = dir($CFG['datadir']);
    while (false !== ($entry = $d->read())) 
    {
        if(($entry != '.')&&($entry != '..')&&(is_dir($CFG['datadir'].'/'.$entry)))
        {
            $files = glob($CFG['datadir'].'/'.$entry.'/*.ipynb');
            foreach($files as $f)
                $nbs[] = $entry.'/'.basename($f);
        }
    }
    $d->close();
    return $nbs;
}


?>
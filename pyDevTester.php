<?php
require_once('config.php');
require_once('corelib/templateMerge.php');

include('pyDevTester_form.php');


$template = new templateMerge('theme/template2.html');
$template->pageData['main'] = "<h1>Python service tester</h1>";
$template->addScript('scripts/ajax.js');

$action = '';
$resetpickle = false;
$code = "print('hello')\n";
$picklefile = "user12345";
$workingdir = "6587293765923";
$urlupload = "http://localhost/abstracts/abstracts.sql3";

/*if(update_from_pylearn($resetpickle, $code, $picklefile, $workingdir, $urlupload))
{

    $postdata = http_build_query($_POST);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );
    $context  = stream_context_create($opts);

    $result = file_get_contents('http://localhost:8080/', false, $context);

    $template->pageData['main'] .= $result;

}
else*/
{
    $template->pageData['main'] .= "<div id='formarea' style='margin: 20px;'>".show_pylearn($action, $resetpickle, $code, $picklefile, $workingdir, $urlupload)."</div>";
    $template->pageData['main'] .= "<div id='response' style='margin: 20px;'>Response will go here</div>";
}

if(error_get_last() == null)
    echo $template->render();
else
    var_dump(error_get_last());


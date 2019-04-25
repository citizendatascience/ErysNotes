<?php
// Example ajax page - this will need extensive modification
require_once('../config.php');
include('../pyDevTester_form.php');
session_start();

//Initialise form variables to default values
$resetpickle = false;
$code = '';
$picklefile = '';
$workingdir = '';
$urlupload = '';
$action = '';
$output = array();

if(pylearn_submitted())
{
    if(update_from_pylearn($action, $resetpickle, $code, $picklefile, $workingdir, $urlupload))
    {
        // Put processing code here
    }
    $output['response'] = '<pre>'.htmlentities(json_encode(json_decode(evaluateNote('')), JSON_PRETTY_PRINT)).'</pre>';
}

echo json_encode($output);


function evaluateNote($source)
{
  /*  $postdata = http_build_query(
        array(
            'resetpickle' => '0',
            'code' => $source,
            'picklefile' => 'test.pickle',
            'workingdir' => 'data'
        )
    );*/
    if(isset($_POST['urlupload']))
        $_POST['filename'] = basename($_POST['urlupload']);

    $postdata = http_build_query($_POST);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    //return($source2);
    $context  = stream_context_create($opts);

    $result = file_get_contents('http://localhost:8080/', false, $context);
    return $result;
}
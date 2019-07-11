<?php
//require_once('../config.php');

function initialisePython($activityID, $userID, $filelist = false)
{
    global $CFG;
    $postarray = array(
            'message' => 'initialise',
            'userID' => $userID,
            'activityID' => $activityID,
        );
    if($filelist)
    {
        $fileServiceURL = isset($CFG['fileServiceURL']) ? $CFG['fileServiceURL'] :
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].str_replace('\\','/', dirname($_SERVER['REQUEST_URI'])).'service/getfile.php';

        $postarray['filelist'] = $filelist; 
        $postarray['urlupload'] =  "{$fileServiceURL}?f=" . urlencode("/{$activityID}/files/");
        //$postarray['urlupload'] =  "http://localhost:8000/data/{$activityID}/files/{$filename}";
    }

    $postdata = http_build_query($postarray);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    $result = @file_get_contents($CFG['pythonService'], false, $context);
    return $result;
}

function runCell($activityID, $userID, $source, $imgretrieve=false)
{
    global $CFG;
    $postarray = array(
            'message' => 'runblock',
            'source' => $source,
            'userID' => $userID,
            'activityID' => $activityID,
        );
    if(($imgretrieve)&&(sizeof($imgretrieve)))
    {
        $postarray['imgretrieve'] = implode(' ', $imgretrieve);
    }

    $postdata = http_build_query($postarray);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);
    try{
        $result = @file_get_contents($CFG['pythonService'], false, $context);
    } catch(Exception $e) {
        $result = json_encode(array('errors'=>'Caught exception: ' . $e->getMessage() . ' when attempting to call Python service.', 'output'=>''));
    }
    if($result == false)
        $result = json_encode(array('errors'=>'No response from Python service, is it running?', 'output'=>''));
    return $result;
}


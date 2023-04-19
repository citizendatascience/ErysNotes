<?php
require_once('../config.php');

$filename = $CFG['datadir'].$_REQUEST['f'];

file_put_contents("tmpinfo.txt", $filename);

// found at https://stackoverflow.com/questions/2882472/php-send-file-to-user/27805443
if(file_exists($filename))
{

    //Get file type and set it as Content Type
    //$finfo = finfo_open(FILEINFO_MIME_TYPE);
    //header('Content-Type: ' . finfo_file($finfo, $filename));
    //finfo_close($finfo);

    header('Content-Type: application/octet-stream');

    //Use Content-Disposition: attachment to specify the filename
    header('Content-Disposition: attachment; filename='.basename($filename));

    //No cache
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    //Define file size
    header('Content-Length: ' . filesize($filename));

    ob_clean();
    flush();
    readfile($filename);
    exit;
}
exit();

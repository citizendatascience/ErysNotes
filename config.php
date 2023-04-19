<?php
$CFG = array();
$CFG['datadir'] = dirname(__FILE__)."/data";
$CFG['dataroot'] = substr($CFG['datadir'], strlen($_SERVER['DOCUMENT_ROOT']));
$CFG['imgdir'] = dirname(__FILE__)."/images";
$CFG['imgroot'] = substr($CFG['imgdir'], strlen($_SERVER['DOCUMENT_ROOT']));
$CFG['pythonService'] = 'http://localhost:8080/';
//$CFG['fileServiceURL'] = 'http://localhost/ErysNotes/service/getFile.php';
$CFG['fileServiceURL'] = 'http://localhost:8000/service/getFile.php';

$CFG['ltikeys'] = array('12345'=>'secret');

$LTI['clientfilepath'] = __DIR__.'/clients.json';
$LTI['adminphrase'] = '3a5f0b4ec35e4fdb88a7ecd5a0c84bdbe591ed3e';

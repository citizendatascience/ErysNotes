<?php
$CFG = array();
$CFG['datadir'] = dirname(__FILE__)."/data";
$CFG['dataroot'] = substr($CFG['datadir'], strlen($_SERVER['DOCUMENT_ROOT']));
$CFG['imgdir'] = dirname(__FILE__)."/images";
$CFG['imgroot'] = substr($CFG['imgdir'], strlen($_SERVER['DOCUMENT_ROOT']));
$CFG['pythonService'] = 'http://localhost:8080/';
$CFG['fileServiceURL'] = 'http://localhost/ErysNotes/service/getFile.php';
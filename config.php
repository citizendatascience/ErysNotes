<?php
$CFG = array();
$CFG['datadir'] = dirname(__FILE__)."/data";
$CFG['dataroot'] = substr($CFG['datadir'], strlen($_SERVER['DOCUMENT_ROOT']));

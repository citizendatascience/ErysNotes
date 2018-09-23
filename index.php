<?php
require_once('config.php');
require_once('corelib/templateMerge.php');
session_start();

$template = new templateMerge('theme/template2.html');

$script = 'for i in range(10):
	print(i)';


//$template->pageData['main'] .= '<button id="button1">button1</button>';
$template->pageData['leftFull'] = '<ul><li><a href="index.php">Main page</a></li>';
$template->pageData['leftFull'] .= '<li><a href="brythontest.php">Brython Experiment</a></li>';
$template->pageData['leftFull'] .= '</ul>';


if(error_get_last() == null)
    echo $template->render();
else
    var_dump(error_get_last());


?>
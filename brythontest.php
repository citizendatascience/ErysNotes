<?php
require_once('config.php');
require_once('corelib/templateMerge.php');
session_start();

$template = new templateMerge('theme/template2.html');
$template->pageData['scriptIncludes'] .= '<script src="src/brython.js" type="text/javascript" charset="utf-8"></script>'."\n";
$template->pageData['scriptIncludes'] .= '<script src="scripts/ace/ace.js" type="text/javascript" charset="utf-8"></script>'."\n";
$template->pageData['scriptIncludes'] .= '<script src="scripts/index.js" type="text/javascript" charset="utf-8"></script>'."\n";

$template->pageData['mainInfo'] = $template->pageData['mainInfo'] .= '<a class="toolBtn" href="#" onclick=\'runScript(editor);\'>Run</a>';

$script = 'for i in range(10):
	print(i)';

$template->pageData['main'] = '<div id="editor" style="width:96%; height: 40%; margin:2%;">'.$script.'</div>';
$template->pageData['main'] .= '<pre id="output" style="width:96%; margin:2%; background-color:#cccccc;">&nbsp;</pre>';

//$template->pageData['main'] .= '<button id="button1">button1</button>';
$template->pageData['leftFull'] = '<ul><li><a href="index.php">Main page</a></li>';
$template->pageData['leftFull'] .= '<li><a href="brythontest.php">Brython Experiment</a></li>';
$template->pageData['leftFull'] .= '</ul>';


$template->pageData['bodyAttributes'] = "onload='brython(1);'";
$template->pageData['scriptStart'] = '
    //brython({debug:1});
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/python");
';
$template->pageData['brythonStart'] = '
from browser import document
import sys

class redirect:
    def write(text, text2):
        document["output"].innerHTML += text2

sys.stdout = redirect()
sys.stderr = redirect()
';
if(error_get_last() == null)
    echo $template->render();
else
    var_dump(error_get_last());


?>

<html>

<head>
  <title></title>
  <script lang="JavaScript">
function PostIt()
{
    document.getElementById('timer').innerHTML += ".";
    document.body.style.cursor = 'wait';
    //alert("posting");
    var url = "http://localhost:8080/";
    //url = "http://localhost/tmp/pytest1.php";
    //url = "http://192.168.56.102/spark";
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function ()
    {
        if (xmlHttp.readyState === 4)
        {
            if (xmlHttp.status == 200)
            {
                try
                {
                    var response = JSON.parse(xmlHttp.responseText);
                    processAjaxResponse(response);
                } catch (e)
                {
                    alert("JSON parse error in ajaxLinkClick\nURL: " + todourl + "\n" + e + "\n\n" + xmlHttp.responseText);
                    document.write(xmlHttp.responseText);
                }
            }
            else
            {
                alert("Http response code " + xmlHttp.status + " when retrieving " + url + "\n response was " + xmlHttp.responseText);
            }
            document.body.style.cursor = '';
        }
        document.getElementById('timer').innerHTML += " " + xmlHttp.readyState + ':' + xmlHttp.status + ' ' + xmlHttp.responseText + '<br/>';
    }
    xmlHttp.open("POST", url, true);
    xmlHttp.setRequestHeader('accept', 'application/json');
    xmlHttp.setRequestHeader('Connection', 'close');
    var tdf = document.getElementById("testform");
    var data = new FormData(tdf);
    xmlHttp.send(data);
}

function processAjaxResponse(response)
{
    for (name in response)
    {
        if (name == 'alert')
        {
            alert(response[name]);
        }
        else if (name == 'location')
        {
            window.location = response[name];
        }
        else if (document.getElementById(name) != null)
        {
            if (document.getElementById(name).tagName.toLowerCase() == "input")
                document.getElementById(name).value = response[name];
            else
                document.getElementById(name).innerHTML = response[name];
        }
    }
}
</script>
</head>
<body>

<?php
define('pylearn_magic', md5('pylearn'));

$resetpickle = false;
$code = "print('hello')\n";
$picklefile = "test.pickle";
$workingdir = "6587293765923";
$urlupload = "http://localhost/abstracts/abstracts.sql3";

if(update_from_pylearn($resetpickle, $code, $picklefile, $workingdir, $urlupload))
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

    echo $result;

}
else
{
    echo show_pylearn($resetpickle, $code, $picklefile, $workingdir, $urlupload);
    echo "<button onclick='PostIt(); return false;'>Post it</button>";
    echo "<div id='timer'>/</div>";
}


/*
#form pylearn;
boolean resetpickle "Reset";
memo[60,5] code "Source code";
string[20] picklefile "Pickle file name";
string[32] workingdir "Working dir";
string[50] urlupload "Grab this file (url)";
okcancel "OK" "Cancel";
*/

function show_pylearn($resetpickle, $code, $picklefile, $workingdir, $urlupload)
{
    //$out = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
    $out = '<form action="http://localhost:8080/" method="POST" id="testform">';
    $out .= '<input type="hidden" name="pylearn_code" value="'.pylearn_magic.'"/>';

    $out .= '<div class="formfield">';
    $out .= '<label for="resetpickle">Reset:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="checkbox" name="resetpickle" value="1"';
    if($resetpickle)
        $out .= ' checked="1" ';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="code">Source code:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><textarea name="code" cols="60" rows="5"/>';
    $out .= htmlentities($code);
    $out .= "</textarea></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="picklefile">Pickle file name:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="picklefile" value="'.$picklefile.'" size="20"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="workingdir">Working dir:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="workingdir" value="'.$workingdir.'" size="32"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="urlupload">Grab this file (url):';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="urlupload" value="'.$urlupload.'" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<input class="submit" name="pylearn_submit" type="submit" value="OK" />';
    $out .= '<input class="submit" name="pylearn_cancel" type="submit" value="Cancel" />';
    $out .= "</div>";

    $out .= '<form>';
    return $out;
}

function update_from_pylearn(&$resetpickle, &$code, &$picklefile, &$workingdir, &$urlupload)
{
    if((isset($_REQUEST['pylearn_code']))&&($_REQUEST['pylearn_code']==pylearn_magic))
    {
        if(isset($_REQUEST['pylearn_cancel']))
            return false;
        $resetpickle = (isset($_REQUEST['resetpickle'])&&(intval($_REQUEST['resetpickle'])>0));
        $code = strval($_REQUEST['code']);
        $picklefile = strval($_REQUEST['picklefile']);
        $workingdir = strval($_REQUEST['workingdir']);
        $urlupload = strval($_REQUEST['urlupload']);
        return true;
    }
    else
    {
        return false;
    }
}
?>

</body>

</html>


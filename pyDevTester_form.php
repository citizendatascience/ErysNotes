<?php
/*
#form pylearn;
select message "Action" {'initialise'=>'initialise', 'addfile'=>'addfile', 'enable'=>'enable', 'inituser'=>'inituser', 'runblock'=>'runblock'};
boolean resetpickle "Reset";
memo[60,5] source "Source code";
string[20] userID "Pickle file name / UserID";
string[32] activityID "Working dir / ResourceID";
string[50] urlupload "Grab this file (url)";
okcancel "OK" "Cancel";
 */
define('pylearn_magic', md5('pylearn'));

function show_pylearn($message, $resetpickle, $source, $userID, $activityID, $urlupload)
{
    //$out = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
    $out = "<form id='pylearn' action='ajax/pythonconnect.php' method='POST' onsubmit='return false;'>";
    $out .= '<input type="hidden" name="pylearn_source" value="'.pylearn_magic.'"/>';

    $out .= '<div class="formfield">';
    $out .= '<label for="message">Action:';
    $out .= '</label>';
    $message_options = array('initialise'=>'initialise', 'runblock'=>'runblock');
    $out .= '<br/><span class="forminput"><select name="message">';
    foreach($message_options as $key => $val)
    {
        $out .= "<option";
        if(trim($key)==trim($message))
            $out .= ' selected="1"';
        $out .= " value='$key'>{$val}</option>\n";
    }
    $out .= "</select></span></div>\n";
    $out .= '<div class="formfield">';
    $out .= '<label for="resetpickle">Reset:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="checkbox" name="resetpickle" value="1"';
    if($resetpickle)
        $out .= ' checked="1" ';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="source">Source code:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><textarea name="source" cols="60" rows="5"/>';
    $out .= htmlentities($source);
    $out .= "</textarea></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="userID">Pickle file name / UserID:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="userID" value="'.$userID.'" size="20"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="activityID">Working dir / ResourceID:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="activityID" value="'.$activityID.'" size="32"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="urlupload">Grab this file (url):';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="text" name="urlupload" value="'.$urlupload.'" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<input class="submit" name="pylearn_submit" type="submit" value="OK" onclick=\'submitForm("pylearn", this);\' />';
    $out .= '<input class="submit" name="pylearn_cancel" type="submit" value="Cancel" onclick=\'submitForm("pylearn", this);\' />';
    $out .= "</div>";

    $out .= '<form>';
    return $out;
}

function pylearn_submitted()
{
    if((isset($_REQUEST['pylearn_source']))&&($_REQUEST['pylearn_source']==pylearn_magic))
        return true;
    else
        return false;
}

function update_from_pylearn(&$message, &$resetpickle, &$source, &$userID, &$activityID, &$urlupload)
{
    if((isset($_REQUEST['pylearn_source']))&&($_REQUEST['pylearn_source']==pylearn_magic))
    {
        if(isset($_REQUEST['pylearn_cancel']))
            return false;
        $message = $_REQUEST['message'];
        $resetpickle = (isset($_REQUEST['resetpickle'])&&(intval($_REQUEST['resetpickle'])>0));
        $source = strval($_REQUEST['source']);
        $userID = strval($_REQUEST['userID']);
        $activityID = strval($_REQUEST['activityID']);
        $urlupload = strval($_REQUEST['urlupload']);
        return true;
    }
    else
    {
        return false;
    }
}

//USERCODE-SECTION-extra-functions
// Put code here.
//ENDUSERCODE-SECTION-extra-functions

<?php
/*
#form configureActivity;
upload notebook "Upload/Change ipynb notebook";
upload data "Add a data file";
okcancel "OK" "Cancel";
*/

define('configureActivity_magic', md5('configureActivity'));

// Function hand modified to display current files.
function show_configureActivity($nb, $files)
{
    $out = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" enctype="multipart/form-data">';
    $out .= '<input type="hidden" name="configureActivity_code" value="'.configureActivity_magic.'"/>';

    if($nb)
    {
        $out .= "<div>Current notebook: $nb</div>";
    }

    $out .= '<div class="formfield">';
    $out .= '<label for="notebook">Upload/Change ipynb notebook:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="file" name="notebook" size="50" ';
    $out .= "/></span></div>\n";

    if(sizeof($files))
    {
        $out .= "<div>Current data files:<ul>";
        foreach($files as $f)
            $out .= "<li>$f <a href='?del=$f'>Delete</a></li>";
        $out .= "</ul></div>";
    }

    $out .= '<div class="formfield">';
    $out .= '<label for="data">Add a data file:';
    $out .= '</label>';
    $out .= '<br/><span class="forminput"><input type="file" name="data" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<input class="submit" name="configureActivity_submit" type="submit" value="OK" />';
    $out .= '<input class="submit" name="configureActivity_cancel" type="submit" value="Cancel" />';
    $out .= "</div>";

    $out .= '<form>';
    return $out;
}

function configureActivity_submitted()
{
    if((isset($_REQUEST['configureActivity_code']))&&($_REQUEST['configureActivity_code']==configureActivity_magic))
        return true;
    else
        return false;
}

function update_from_configureActivity(&$notebook, &$data)
{
    if((isset($_REQUEST['configureActivity_code']))&&($_REQUEST['configureActivity_code']==configureActivity_magic))
    {
        if(isset($_REQUEST['configureActivity_cancel']))
            return false;
        if((isset($_FILES['notebook']))&&($_FILES['notebook']['name']!=""))
            $notebook = $_FILES['notebook'];
        else
            $notebook = false;
        if((isset($_FILES['data']))&&($_FILES['data']['name']!=""))
            $data = $_FILES['data'];
        else
            $data = false;
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
?>
<html>
<head>
    <title>NBWebSites.com LTI-A Client editor</title>
    <!--<link rel="stylesheet" href="LTIAEdit.css">-->

    <style>
body{margin:3%;}
.formfield{margin-bottom:10px;margin-top:10px}.errormsg,.required_indicator{color:red}legend{font:90% Arial,Helvetica,sans-serif}label{display:block;font:100% Arial,Helvetica,sans-serif;width:90%}input[type=text],textarea{border:1px solid #aaa;box-shadow:0 0 15px 4px rgba(0,0,0,.06);padding:5px}fieldset{display:block;margin:0 0 3em;background:#cfe;border:1px solid #8d8;border-radius:5px;box-shadow:inset 0 0 15 #cfc;margin:0 0 10;padding:20px}fieldset legend{background:#cfe;border-left:1px solid #8d8;border-radius:5px 5px 0 0;border-right:1px solid #8d8;border-top:1px solid #8d8;box-shadow:0 -1px 2px #f1f1f1;color:#5a7;font-size:12px;font-weight:400;padding:0 8px 3px}input,select,textarea{background:#effff6;border-radius:3px;padding:5px 8px}input[type=file]{border:1px solid #8a8}input[type=submit]{background:#ccc;min-width:30%}form{background-color:#fff;box-shadow:0 0 15px 4px rgba(0,0,0,.8);margin-left:auto;margin-right:auto;max-width:800px;padding:15px}
    </style>
</head><body>
<h1>LTI-Advantage (LTI 1.3) Client configuration.</h1>
<p>Use this script to edit a JSON file containing LTI client configuration. The JSON file should be in a location where it cannot be
retrieved by http, and its path should be set in a variable $LTI['clientfilepath'] in your config.php file. For security this script 
uses a pass phrase. Type in your intended pass phrase, and then copy the hash displated into a variable, $LTI['adminphrase'] in your
config file.</p>
           <?php
/*
#form editLTIAClient;
hidden key;
string[50] platformID "Platform ID (iss)";
string[50] clientID "Client ID";
string[50] deploymentID "Deployment ID ";
string[80] publicKeysetURL "Public keyset URL";
string[80] accessTokenURL "Access token URL";
string[80] authenticationURL "Authentication request URL";
okcancel "Create/Update" "Cancel";

#form loginPhraseForm;
password[90] passphrase "Pass phrase for admin access.";
okcancel "Submit";
 */

require_once('corelib/ltia.php');
require_once('config.php');

define('editLTIAClient_magic', md5('editLTIAClient'));

session_start();

if(loggedIn())
{
    $clientManager = new simpleClientManager($LTI['clientfilepath']);
    if(isset($_REQUEST['del']))
    {
        $clientManager->deleteClient($_REQUEST['del']);
    }

    if(!isset($_REQUEST['key']))
    {
        listClients($clientManager);
        echo "<p><a href='?key='>Add new client</a></p>";
    }
    else
    {
        if(editLTIAClient_submitted())
        {
            if(isset($_REQUEST['editLTIAClient_cancel']))
            {
                listClients($clientManager);
                echo "<p><a href='?key='>Add new client</a></p>";
            }
            else
            {
                $details = (object)array('key'=>'', 'platformID'=>'', 'clientID'=>'', 'deploymentID'=>'', 'publicKeysetURL'=>'', 'accessTokenURL'=>'', 'authenticationURL'=>'');
                update_from_editLTIAClient($details->key, $details->platformID, $details->clientID, $details->deploymentID, $details->publicKeysetURL, $details->accessTokenURL, $details->authenticationURL);
                $validationMsgs = validate_editLTIAClient_data($details->key, $details->platformID, $details->clientID, $details->deploymentID, $details->publicKeysetURL, $details->accessTokenURL, $details->authenticationURL);
                if(sizeof($validationMsgs))
                {
                    echo show_editLTIAClient($details->key, $details->platformID, $details->clientID, $details->deploymentID, $details->publicKeysetURL,
                                                $details->accessTokenURL, $details->authenticationURL, $validationMsgs);
                }
                else
                {
                    $clientManager->addClientDetails($details->platformID, $details->clientID, $details->deploymentID, $details->publicKeysetURL,
                                                $details->accessTokenURL, $details->authenticationURL);
                    listClients($clientManager);
                    echo "<p><a href='?key='>Add new client</a></p>";
                }
            }
        }
        else
        {
            $details = $clientManager->getClientDetailsFromKey($_REQUEST['key']);
            if($details == false)
                $details = (object)array('key'=>'', 'platformID'=>'', 'clientID'=>'', 'deploymentID'=>'', 'publicKeysetURL'=>'', 'accessTokenURL'=>'', 'authenticationURL'=>'');
            echo show_editLTIAClient($details->key, $details->platformID, $details->clientID, $details->deploymentID, $details->publicKeysetURL,
                                        $details->accessTokenURL, $details->authenticationURL);
        }
    }
}

function loggedIn()
{
    global $LTI;
    $message = array();
    if((isset($_SESSION['activeTil']))&&($_SESSION['activeTil']>time()))
        $_SESSION['activeTil'] = time()+3000; // 5 minutes of inactivity = logout;
    else if(isset($_REQUEST['passphrase']))
    {
        $passcode = passcodeFromPhrase($_REQUEST['passphrase']);
        if($passcode == $LTI['adminphrase'])
            $_SESSION['activeTil'] = time()+3000; // 5 minutes of inactivity = logout;
        else
            $message['passphrase'] = "Incorrect passphrase.";
    }
    else
        $_SESSION['activeTil'] = 0;
    $loggedIn =  $_SESSION['activeTil']>time();
    if($loggedIn)
        return true;
    else
    {
        echo show_loginPhraseForm('', $message);
        if($LTI['adminphrase'] == '')
        {
            if(isset($passcode))
                echo "<p>The use the pass phrase entered, set \$LTI['adminphrase'] to '{$passcode}' in the config file.</p>";
            else
                echo "No pass phrase has been set up - enter a new pass phrase to view the hash code to add to the config file.";
        }
        return false;
    }
}

function passcodeFromPhrase($phrase)
{
    // Server address is added as salt to reduce risk when config files are accidentally uploaded to GitHub etc.
    $salt = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME'];
    return sha1(trim(preg_replace('/\s+/', ' ', $phrase)).$salt);
}

function listClients($clientManager)
{
    $curClients = $clientManager->getClientList();
    foreach($curClients as $key=>$name)
    {
        echo "<p><a href='?key={$key}'>{$name}</a> (<a href='?del={$key}'>Delete</a>)</p>";
    }
}

function show_editLTIAClient($key, $platformID, $clientID, $deploymentID, $publicKeysetURL, $accessTokenURL, $authenticationURL, $validationMsgs=array())
{
    $out = "<form action='{$_SERVER['PHP_SELF']}' method='POST'>";
    $out .= '<input type="hidden" name="editLTIAClient_code" value="'.editLTIAClient_magic.'"/>';

    $out .= '<input type="hidden" name="key" value="'.$key.'"';
    $out .= "/>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="platformID">Platform ID (iss)';
    if(isset($validationMsgs['platformID']))
        $out .= "<span class='validation_error'>{$validationMsgs['platformID']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="platformID" value="'.htmlspecialchars($platformID).'" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="clientID">Client ID';
    if(isset($validationMsgs['clientID']))
        $out .= "<span class='validation_error'>{$validationMsgs['clientID']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="clientID" value="'.htmlspecialchars($clientID).'" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="deploymentID">Deployment ID';
    if(isset($validationMsgs['deploymentID']))
        $out .= "<span class='validation_error'>{$validationMsgs['deploymentID']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="deploymentID" value="'.htmlspecialchars($deploymentID).'" size="50"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="publicKeysetURL">Public keyset URL';
    if(isset($validationMsgs['publicKeysetURL']))
        $out .= "<span class='validation_error'>{$validationMsgs['publicKeysetURL']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="publicKeysetURL" value="'.htmlspecialchars($publicKeysetURL).'" size="80"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="accessTokenURL">Access token URL';
    if(isset($validationMsgs['accessTokenURL']))
        $out .= "<span class='validation_error'>{$validationMsgs['accessTokenURL']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="accessTokenURL" value="'.htmlspecialchars($accessTokenURL).'" size="80"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<label for="authenticationURL">Authentication request URL';
    if(isset($validationMsgs['authenticationURL']))
        $out .= "<span class='validation_error'>{$validationMsgs['authenticationURL']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="text" name="authenticationURL" value="'.htmlspecialchars($authenticationURL).'" size="80"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<input class="submit" name="editLTIAClient_submit" type="submit" value="Create/Update" />';
    $out .= '<input class="submit" name="editLTIAClient_cancel" type="submit" value="Cancel" />';
    $out .= "</div>";

    $out .= '<form>';
    return $out;
}

function validate_editLTIAClient_data($key, $platformID, $clientID, $deploymentID, $publicKeysetURL, $accessTokenURL, $authenticationURL)
{
    $validationMsgs=array();
    $urlRegex = '/^https?:\/\/(\w+\.)*(\w+)(\/([a-z0-9\-._~%!$&\'()*+,;=:@]+)+)*\/?(\?[\w&=\+%#]*)?$/imx';
    // Add validation check for key here, if there is problem indicate it in $validationMsgs['key']
    //USERCODE-SECTION-field_editLTIAClient_key_validation
    // Put code here.
    //ENDUSERCODE-SECTION-field_editLTIAClient_key_validation

    // Add validation check for platformID here, if there is problem indicate it in $validationMsgs['platformID']
    //USERCODE-SECTION-field_editLTIAClient_platformID_validation
    if(!preg_match($urlRegex, $platformID))
        $validationMsgs['platformID'] = "Platform ID must be an http(s) URI";
    //ENDUSERCODE-SECTION-field_editLTIAClient_platformID_validation

    // Add validation check for clientID here, if there is problem indicate it in $validationMsgs['clientID']
    //USERCODE-SECTION-field_editLTIAClient_clientID_validation
    if(!strlen(trim($clientID)))
        $validationMsgs['clientID'] = "Client ID must be set.";
    //ENDUSERCODE-SECTION-field_editLTIAClient_clientID_validation

    // Add validation check for deploymentID here, if there is problem indicate it in $validationMsgs['deploymentID']
    //USERCODE-SECTION-field_editLTIAClient_deploymentID_validation
    if(!strlen(trim($deploymentID)))
        $validationMsgs['deploymentID'] = "Deployment ID must be set.";
    //ENDUSERCODE-SECTION-field_editLTIAClient_deploymentID_validation

    // Add validation check for publicKeysetURL here, if there is problem indicate it in $validationMsgs['publicKeysetURL']
    //USERCODE-SECTION-field_editLTIAClient_publicKeysetURL_validation
    if(!preg_match($urlRegex, $publicKeysetURL))
        $validationMsgs['publicKeysetURL'] = "Public keyset URL must be a full valid URL";
    //ENDUSERCODE-SECTION-field_editLTIAClient_publicKeysetURL_validation

    // Add validation check for accessTokenURL here, if there is problem indicate it in $validationMsgs['accessTokenURL']
    //USERCODE-SECTION-field_editLTIAClient_accessTokenURL_validation
    if(!preg_match($urlRegex, $accessTokenURL))
        $validationMsgs['accessTokenURL'] = "Access token URL must be a full valid URL";
    //ENDUSERCODE-SECTION-field_editLTIAClient_accessTokenURL_validation

    // Add validation check for authenticationURL here, if there is problem indicate it in $validationMsgs['authenticationURL']
    //USERCODE-SECTION-field_editLTIAClient_authenticationURL_validation
    if(!preg_match($urlRegex, $authenticationURL))
        $validationMsgs['authenticationURL'] = "Authentication URL must be a full valid URL";
    //ENDUSERCODE-SECTION-field_editLTIAClient_authenticationURL_validation

    return $validationMsgs;
}

function editLTIAClient_submitted()
{
    if((isset($_REQUEST['editLTIAClient_code']))&&($_REQUEST['editLTIAClient_code']==editLTIAClient_magic))
        return true;
    else
        return false;
}

function update_from_editLTIAClient(&$key, &$platformID, &$clientID, &$deploymentID, &$publicKeysetURL, &$accessTokenURL, &$authenticationURL)
{
    if((isset($_REQUEST['editLTIAClient_code']))&&($_REQUEST['editLTIAClient_code']==editLTIAClient_magic))
    {
        if(isset($_REQUEST['editLTIAClient_cancel']))
            return false;
        $key = strval($_REQUEST['key']);
        $platformID = strval($_REQUEST['platformID']);
        $clientID = strval($_REQUEST['clientID']);
        $deploymentID = strval($_REQUEST['deploymentID']);
        $publicKeysetURL = strval($_REQUEST['publicKeysetURL']);
        $accessTokenURL = strval($_REQUEST['accessTokenURL']);
        $authenticationURL = strval($_REQUEST['authenticationURL']);
        return true;
    }
    else
    {
        return false;
    }
}

function show_loginPhraseForm($passphrase, $validationMsgs=array())
{
    $out = "<form action='{$_SERVER['PHP_SELF']}' method='POST'>";
    $out .= '<div class="formfield">';
    $out .= '<label for="passphrase">Pass phrase for admin access.';
    if(isset($validationMsgs['passphrase']))
        $out .= "<span class='validation_error'>{$validationMsgs['passphrase']}</span>";
    $out .= '</label>';
    $out .= '<span class="forminput"><input type="password" name="passphrase" value="'.htmlspecialchars($passphrase).'" size="90"';
    $out .= "/></span></div>\n";

    $out .= '<div class="formfield">';
    $out .= '<input class="submit" name="loginPhraseForm_submit" type="submit" value="Submit" />';
    $out .= "</div>";

    $out .= '<form>';
    return $out;
}


//USERCODE-SECTION-extra-functions
// Put code here.
//ENDUSERCODE-SECTION-extra-functions


           ?>
</body>
</html>
<?php
$config = json_decode(file_get_contents('launchconfig.json'));

//echo file_put_contents('launchconfig.json', json_encode($config, JSON_PRETTY_PRINT));

//exit('<pre>'.print_r($config, true).'</pre>');
//echo '<pre>'.print_r($_SERVER, true).'</pre>';
if(isset($_REQUEST['launch']))
{
    $userid = showUserSelect(false);
    list($contextid, $resourceid) = explode('_', $_REQUEST['launch']);
    $context = $config->contexts[$contextid];
    $resource = $context->resources[$resourceid];
    $ltidata = array();
    $ltidata["tool_consumer_info_product_family_code"] = "ims";
    $ltidata["tool_consumer_info_version"] = "1.1";
    $ltidata["tool_consumer_instance_guid"] = "ltitesttool.niallbarr.me.uk";
    $thispoint = getRequestURL();
    $ltidata["launch_presentation_return_url"] = $thispoint;
    $endpoint = substr($thispoint, 0, strrpos($thispoint, '/')) . '/index.php';

    //Temp 
    $ltidata["resource_link_id"] = $resource->id;
    $ltidata["resource_link_title"] = $resource->title;
    $ltidata["resource_link_description"] = $resource->description;
    $ltidata["user_id"] = md5($userid);
    $ltidata["roles"] = $config->users[$userid]->role;
    $ltidata["lis_person_name_full"] = $config->users[$userid]->name;
    $ltidata["lis_person_sourcedid"] = "nbltiTest:{$ltidata["resource_link_id"]}:{$ltidata["user_id"]}";
    $ltidata["context_id"] = $contextid;
    $ltidata["context_title"] = $context->name;

    $params = signParameters($ltidata, $endpoint, "POST", $config->key, $config->secret);
    echo "<form action='$endpoint' method='post'>";
    foreach($params as $k=>$v)
    {
        if($k != "ext_submit")
            echo "<input type='hidden' name='$k' value='$v'/>\n";
    }
    echo "<input type='submit' name='ext_submit' id='ext_submit' value='{$params['ext_submit']}'/>\n</form>";
    echo "<script lang='JavaScript'>document.getElementById('ext_submit').click();</script>";
}
else
{
    $userid = showUserSelect();
    checkAddContextOrResource();
    foreach($config->contexts as $i=>$c)
    {
        echo "<h3>{$c->name}</h3><ul>";
        foreach($c->resources as $ir=>$r)
        {
            echo "<li><b><a href='?launch={$i}_{$ir}'>{$r->title}</a></b><br/>{$r->description}</li>";
        }
        echo "</ul>";
        if((isset($_REQUEST['action']))&&($_REQUEST['action']=='addresource')&&($_REQUEST['context']==$i))
        {
            echo "<h4>New resource</h4><form><input type='hidden' name='action' value='addresource2'/><input type='hidden' name='context' value='$i'/>";
            echo "Resource title: <input type='text' name='title'/> Description: <input type='text' name='description'/>";   
            echo '<input type="submit" name="s" value="Add"/></form>';
        }
        else
        {
            echo "<a href='?action=addresource&context=$i'>Add a resource</a>";
        }
    }

    echo '<h3>New context</h3><form><input type="hidden" name="action" value="addcontext"/>';
    echo "Context name: <input type='text' name='name'/> Context label: <input type='text' name='label'/> ID: <input type='text' name='id'/>";   
    echo '<input type="submit" name="s" value="Add"/></form>';
}

function signParameters($params, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret)
{
    //oauth_signature_method
    $params["oauth_signature_method"] = "HMAC-SHA1";
    //oauth_callback
    $params["oauth_callback"] = "about:blank";
    //oauth_consumer_key
    $params["oauth_consumer_key"] = $oauth_consumer_key;
    //oauth_nonce
    $params["oauth_nonce"] = md5(time()."salt");
    //oauth_version
    $params["oauth_version"] = "1.0";
    //oauth_timestamp
    $params["oauth_timestamp"] = time();
    //lti_version
    $params["lti_version"] = "LTI-1p0";
    //lti_message_type
    $params["lti_message_type"] = "basic-lti-launch-request";
    //The submit button text - as in PHP demo, always ext_submit
    $params["ext_submit"] = "Submit";
    //oauth_signature
    $sighash = getOAuthSignature($params, $endpoint, $method, $oauth_consumer_secret);
    $params["oauth_signature"] = $sighash;
    return $params;
}


function getOAuthSignature($params, $endpoint, $method, $oauth_consumer_secret)
{
    $basestring = $method.'&';
    //IMS code uses str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input))); for RFC 3986
    if(strpos($endpoint,'?'))
    {
    	// get params have to be put into the OAuth parameters rather than the URL for sdigning.
        list($endpoint, $getparams) = explode('?', $endpoint,2);
        $getparams = explode('&',$getparams);
        foreach($getparams as $p)
        {
        	list($k,$v) = explode('=',$p,2);
            $params[$k] = rawurldecode($v);
        }
    }
    $basestring .= rfc3986encode($endpoint).'&'; // PHP manual says rawurlencode is RFC 3986, need to check.
    ksort($params);
    foreach($params as $k=>$v)
    {
    	$basestring .= rfc3986encode($k.'='.rfc3986encode($v).'&');
    }
    // Strip away last encoded '&';
    $basestring = substr($basestring, 0, strlen($basestring)-3);
    //echo '<br/><b>My Userinfo structure contains:</b><pre>'.print_r($userinfo,1).'</pre>';
    //echo "\n<p>\n$basestring\n</p>\n";
    $signingkey = rfc3986encode($oauth_consumer_secret).'&';
    $computed_signature = base64_encode(hash_hmac('sha1', $basestring, $signingkey, true));
    return $computed_signature;
}

function rfc3986encode($input)
{
	return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
}

function getRequestURL()
{
	$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80")
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["SCRIPT_NAME"];
	}
	else
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
	}
	return $pageURL;
}

function checkAddContextOrResource()
{
    global $config;
    if((isset($_REQUEST['action']))&&($_REQUEST['action']=='addcontext'))
    {
        $config->contexts[] = (object) array('name'=>$_REQUEST['name'], 'label'=>$_REQUEST['label'], 'id'=>$_REQUEST['id'], 'resources'=>array());
        file_put_contents('launchconfig.json', json_encode($config, JSON_PRETTY_PRINT));
    }
    if((isset($_REQUEST['action']))&&($_REQUEST['action']=='addresource2'))
    {
        $config->contexts[$_REQUEST['context']]->resources[] = (object) array('title'=>$_REQUEST['title'], 'description'=>$_REQUEST['description'], 'id'=>sha1(time()));
        file_put_contents('launchconfig.json', json_encode($config, JSON_PRETTY_PRINT));
    }
}

function showUserSelect($show=true)
{
    global $config;
    $form = 'sel';
    $userid = isset($_COOKIE['launchtool_user']) ? $_COOKIE['launchtool_user'] : 0;
    if(isset($_REQUEST['action']))
    {
        switch($_REQUEST['action'])
        {
            case 'setuser':
                $userid = $_REQUEST['user'];
                if($userid == -1)
                    $form = 'add';
                else
                    setcookie('launchtool_user', $userid);
                break;
            case 'adduser':
                $config->users[] = (object) array('name'=>$_REQUEST['name'], 'role'=>$_REQUEST['role']);
                file_put_contents('launchconfig.json', json_encode($config, JSON_PRETTY_PRINT));
                $userid = sizeof($config->users)-1;
                setcookie('launchtool_user', $userid);
                break;
        }
    }
    if($show)
    {
        echo '<form>';
        if($form=='sel')
        {
            echo '<input type="hidden" name="action" value="setuser"/><select name="user"><option value="-1">Add a user</option>';
            foreach($config->users as $i=>$u)
            {
                $selstr = ($i==$userid) ? " selected='1'" : '';
                echo "<option value='$i'{$selstr}>{$u->name} ({$u->role})</option>";
            }
            echo '</select>';
        }
        else
        {
            echo '<input type="hidden" name="action" value="adduser"/>';
            echo "New user name: <input type='text' name='name'/> Role: <input type='text' name='role'/>";   
        }
        echo '<input type="submit" name="s" value="Set"/></form>';
    }
    return $userid;
}

<?php

function checkLTIASession($clientManager)
{
    if(isset($_REQUEST['login_hint']))
    {
        if((isset($_REQUEST['iss']))&&(isset($_REQUEST['client_id'])))
        {
            //echo "<h1>Request was:</h1><pre>".print_r($_REQUEST, true).'</pre>';
            $clientInfo = $clientManager->getClientDetails($_REQUEST['iss'], $_REQUEST['client_id'], $_REQUEST['lti_deployment_id']);
            if($clientInfo == false)
                return false;
            $step2Request = $clientInfo->authenticationURL.'?';
            $step2Request .= "scope=openid";
            $step2Request .= "&response_type=id_token";
            $step2Request .= "&client_id=".urlencode($clientInfo->clientID);
            $step2Request .= "&login_hint=".urlencode($_REQUEST['login_hint']);
            $step2Request .= "&response_mode=form_post";
            $step2Request .= "&lti_message_hint=".urlencode($_REQUEST['lti_message_hint']);
            $step2Request .= "&nonce=".time();
            $step2Request .= "&state=CheckIdStateWorks";

            $step2Request .= "&prompt=none";
            $step2Request .= "&redirect_uri=".urlencode($_REQUEST['target_link_uri']);

            header("Location: $step2Request\r\n");
            echo "<p><a href='$step2Request'>Continue</a></p>";

            //echo "<p><a href='$step2Request'>Step 2</a></p>";
            //echo "<p><a href='http://moodlebox.home/mod/lti/certs.php'>Certificates</a></p>";
            return false; // Not really fail, but login incomplete.
        }
        else
        {
            return false;
        }

    }
    elseif(isset($_REQUEST['id_token']))
    {
        $segs = explode(".",$_REQUEST['id_token']);
        $hdr = json_decode(base64_decode($segs[0]));
        //$id = json_decode(base64_decode($segs[1]));
        $id = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $segs[1])));
        $sig = base64_decode(strtr($segs[2], '-_', '+/'));
        if(!is_object($id))
            exit("Something went wrong during sign-in, please try again.");
        //echo '<pre style="color:blue;">'.print_r($id->iss, true).'</pre>';
        $deploymentID = $id->{'https://purl.imsglobal.org/spec/lti/claim/deployment_id'};
        $pk2 = openssl_get_publickey($clientManager->getPublicKey($id->iss, $id->aud, $deploymentID, $hdr->kid));

        $payload = $segs[0] . '.' . $segs[1];

        $ok = openssl_verify($payload, $sig, $pk2, OPENSSL_ALGO_SHA256);
        //if ($ok == 1) {
        //    echo "good";
        //} elseif ($ok == 0) {
        //    echo "bad h";
        //} else {
        //    echo "ugly, error checking signature";
        //}
        openssl_free_key($pk2);

        //echo '<h2>Header</h2><pre>'.print_r($hdr, true).'<pre>';
        //echo '<h2>Token</h2><pre>'.print_r($id, true).'<pre><hr/>';
        if($ok == 1)
        {
            $userinfo = new stdClass();
            $userinfo->ltiaparams = $id;
            $userinfo->params = getPseudoLTI1p1Params($id);
            $userinfo->params['oauth_consumer_key'] = $id->iss.'.'.$deploymentID;
            return $userinfo;
        }
        else
            return false;
    }
}


class ltiaSession
{
    private $id_token;

    private function __construct($id_token)
    {
        $this->id_token = $id_token;
    }

    static function Create($clientManager, $id_token, &$error)
    {

    }
}

class clientInfo
{
    var $key;
    var $publicKeysetURL;
    var $accessTokenURL;
    var $authenticationURL;
    var $platformID; //iss
    var $clientID;
    var $deploymentID; // Moodle has this, but not sure it's important
    //var $cachedPublicKeys; // maybe retrieve with second call

    function __construct($data)
    {
        $this->publicKeysetURL = $data->publicKeysetURL;
        $this->accessTokenURL = $data->accessTokenURL;
        $this->authenticationURL = $data->authenticationURL;
        $this->platformID = $data->platformID; //iss
        $this->clientID = $data->clientID;
        $this->deploymentID = $data->deploymentID;
        $this->key = sha1($this->platformID.$this->clientID);
    }
}

interface clientManager
{
    function getClientDetails($iss, $clientID, $deploymentID); //returns clientInfo or false
    function getPublicKey($iss, $clientID, $deploymentID, $keyID);
    function addClientDetails($iss, $clientID, $deploymentID, $publicKeysetURL, $accessTokenURL, $authenticationURL);
    function updatePublicKey($iss, $clientID, $deploymentID, $keyID, $key=null); // key==null removes key
}

class simpleClientManager implements clientManager
{
    private $path;
    private $data;

    function __construct($jsonPath)
    {
        $this->path = $jsonPath;
        if(file_exists($this->path))
        {
            $json = file_get_contents($this->path);
            $this->data = json_decode($json);
        }
        else
            $this->data = new stdClass();
    }

    function getClientDetails($iss, $clientID, $deploymentID)
    {
        $key = sha1($iss.$clientID.$deploymentID);
        return $this->getClientDetailsFromKey($key);
    }

    function getClientDetailsFromKey($key)
    {
        if(isset($this->data->$key))
            return new clientInfo($this->data->$key);
        else
            return false;
    }

    function getPublicKey($iss, $clientID, $deploymentID, $keyID)
    {
        $key = sha1($iss.$clientID.$deploymentID);
        if(isset($this->data->$key->cachedPublicKeys->$keyID))
            return $this->data->$key->cachedPublicKeys->$keyID;
        else
        {
            //echo '<p style="color:red;">Getting key remotely...</p>';
            $this->retrieveAndUpdatePublicKeys($iss, $clientID, $deploymentID);
            if(isset($this->data->$key->cachedPublicKeys->$keyID))
                return $this->data->$key->cachedPublicKeys->$keyID;
            else
                return false;
        }
    }

    function getClientList()
    {
        $out = array();
        foreach($this->data as $key=>$info)
        {
            $out[$key] = "{$info->platformID} ({$info->deploymentID})";
        }
        return $out;
    }

    function addClientDetails($iss, $clientID, $deploymentID, $publicKeysetURL, $accessTokenURL, $authenticationURL)
    {
        $key = sha1($iss.$clientID.$deploymentID);
        $this->data->$key = new stdClass();
        $this->data->$key->platformID = $iss;
        $this->data->$key->clientID = $clientID;
        $this->data->$key->deploymentID = $deploymentID;
        $this->data->$key->publicKeysetURL = $publicKeysetURL;
        $this->data->$key->accessTokenURL = $accessTokenURL;
        $this->data->$key->authenticationURL = $authenticationURL;
        $this->data->$key->cachedPublicKeys = new stdClass();
        $this->save();
    }

    function deleteClient($key)
    {
        unset($this->data->$key);
        $this->save();
    }

    function updatePublicKey($iss, $clientID, $deploymentID, $keyID, $publickey=null)
    {
        $key = sha1($iss.$clientID.$deploymentID);
        $this->data->$key->cachedPublicKeys->$keyID = $publickey;
        $this->save();
    }

    function retrieveAndUpdatePublicKeys($iss, $clientID, $deploymentID)
    {
        $key = sha1($iss.$clientID.$deploymentID);
        $jsonPKeys = file_get_contents($this->data->$key->publicKeysetURL);
        $keyset = convertJSONPKeysetToOpenSSLPKeyset($jsonPKeys);
        foreach($keyset as $keyID=>$OpenSSLPKey)
        {
            $this->data->$key->cachedPublicKeys->$keyID = $OpenSSLPKey;
        }
        $this->save();
    }

    private function save()
    {
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT));
    }
}

function convertJSONPKeysetToOpenSSLPKeyset($jsonPKeys)
{
    $keyset = array();
    $keysContainer = json_decode($jsonPKeys);

    foreach($keysContainer->keys as $key)
    {
        if($key->kty == 'RSA')
        {
            // First get the two factors in to arrays of hex excoded bytes
            $decodedN = base64_decode(str_replace(array('-', '_'), array('+', '/'), $key->n));
            $byteArrayN = array('00');  // ASN.1 seems to need big integers to start with 0x00, not sure why, maybe to show all bits used...
            foreach(str_split($decodedN) as $byte)
            {
                $byteArrayN[] = sprintf("%02X", ord($byte));
            }
            $decodedE = base64_decode(str_replace(array('-', '_'), array('+', '/'), $key->e));
            $byteArrayE = array();
            foreach(str_split($decodedE) as $byte)
            {
                $byteArrayE[] = sprintf("%02X", ord($byte));
            }
            // Encode the identifier, this could just be a constant array, but doing it this way makes it clearer what's going on.
            $obid = "1.2.840.113549.1.1.1";

            $idparts = explode('.', $obid);
            $hdrBytes = array();
            $hdrBytes[] = sprintf("%02X", 40 * $idparts[0] + $idparts[1]);
            for($n=2; $n<sizeof($idparts); $n++)
            {
                $hdrBytes = array_merge($hdrBytes, integerToASN1($idparts[$n]));
            }
            $hdrBytes = addASN1HdrToData(6, $hdrBytes);
            $hdrBytes = buildASN1Seq($hdrBytes, array('05','00'));
            // Put the two factors into an ASN.1 structre
            $byteArrayN = addASN1HdrToData(2, $byteArrayN);
            $byteArrayE = addASN1HdrToData(2, $byteArrayE);
            $byteArrayNE = buildASN1Seq($byteArrayN, $byteArrayE);

            //Prepend a 0 to show all bits used, since this is going into a DER BIT STRING
            $byteArrayNE = array_merge(array('00'), $byteArrayNE);
            $byteArrayNE = addASN1HdrToData(3, $byteArrayNE);

            $pubkey = buildASN1Seq($hdrBytes, $byteArrayNE);
            $binary = '';
            foreach($pubkey as $byte)
            {
                $binary .= chr(hexdec($byte));
            }
            $pubkey64 = base64_encode($binary);

            $pubkeyShare = "-----BEGIN PUBLIC KEY-----";
            for($n=0; $n<strlen($pubkey64); $n+=64)
            {
                $pubkeyShare .= "\n";
                $pubkeyShare .= substr($pubkey64, $n, 64);
            }
            $pubkeyShare .= "\n-----END PUBLIC KEY-----";
            $keyset[$key->kid] = $pubkeyShare;
        }
    }
    return $keyset;
}

function integerToASN1($len)
{
    $bv = array();
    $sv = intval($len);
    while($sv > 0)
    {
        $bv[] = ($sv % 128) + 128;
        $sv = floor($sv / 128);
    }
    $bv[0] -= 128;
    $bv = array_reverse($bv);
    $bytes = array();
    foreach($bv as $b)
        $bytes[] = sprintf("%02X", $b);
    return $bytes;
}

function lengthToASN1($len)
{
    $bytes = array();
    if($len < 128)
    {
        $bytes[] = sprintf("%02X", $len);
        return $bytes;
    }

    $bv = array();
    $sv = $len;
    while($sv > 0)
    {
        $bv[] = ($sv % 256);
        $sv = floor($sv / 256);
    }
    $bv[] = 128+sizeof($bv);
    $bv = array_reverse($bv);
    foreach($bv as $b)
        $bytes[] = sprintf("%02X", $b);
    return $bytes;
}

function addASN1HdrToData($type, $data)
{
    $bytes = array(sprintf("%02X", $type));
    $bytes = array_merge($bytes, lengthToASN1(sizeof($data)), $data);
    return $bytes;
}

function buildASN1Seq(...$part)
{
    $bytes = array();
    foreach($part as $p)
        $bytes = array_merge($bytes, $p);
    $bytes = addASN1HdrToData(0x30, $bytes);
    return $bytes;
}

function getPseudoLTI1p1Params($ltiaparams)
{
    $params = array();
    $params['user_id'] = $ltiaparams->sub;
    $params['lis_person_name_given'] = isset($ltiaparams->given_name) ? $ltiaparams->given_name : '';
    $params['lis_person_name_family'] = isset($ltiaparams->family_name) ? $ltiaparams->family_name : '';
    $params['lis_person_name_full'] = isset($ltiaparams->name) ? $ltiaparams->name : '';
    $params['lis_person_contact_email_primary'] = isset($ltiaparams->email) ? $ltiaparams->email : '';
    $params['resource_link_id'] = isset($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/resource_link'}->id) ?
                                  $ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/resource_link'}->id : '';
    $params['resource_link_title'] = isset($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/resource_link'}->title) ?
                                     $ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/resource_link'}->title : '';
    $params['roles'] = '';
    if(isset($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/roles'}))
    {
        foreach($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/roles'} as $role)
            $params['roles'] .= ' '.substr($role, strpos($role, '#')+1);
        $params['roles'] = trim($params['roles']);
    }
    $params['context_id'] = isset($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/context'}->id) ?
                            $ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/context'}->id : '';
    $params['launch_presentation_return_url'] = isset($ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/launch_presentation'}->return_url) ?
                                                $ltiaparams->{'https://purl.imsglobal.org/spec/lti/claim/launch_presentation'}->return_url : '';
    return $params;
}
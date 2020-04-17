<?php
function checkLTISession(&$errorMsg)
{
    $secretManager = new minimalSecretManager();
    $errorMsg = false;
    session_start();
	if((isset($_REQUEST['lti_message_type']))||(!isset($_SESSION['ltisession'])))
    {
	    session_destroy();
        session_start();
    	$_SESSION['ltisession'] = ltiSession::Create($secretManager, $_POST, $errorMsg);
        //echo'<pre>Session: '.print_r($_SESSION['ltisession'],1).'</pre>';
    }
    return $_SESSION['ltisession'];
}

function getRequestURL()
{
	$pageURL = ((isset($_SERVER["HTTPS"]))&&(@$_SERVER["HTTPS"] == "on")) ? "https://" : "http://";
    $defaultPort = ((isset($_SERVER["HTTPS"]))&&(@$_SERVER["HTTPS"] == "on")) ? 443 : 80;
	if ($_SERVER["SERVER_PORT"] != $defaultPort)
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	}
	else
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

/* Still needs to ensure everything is UTF8 encoded */
class ltiSession
{
	var $params;

	function __construct($params)
    {
        $this->params = $params;
    }

    static function Create($secretManager, $params, &$error)
    {
    	if(!is_object($secretManager))
        {
            $error = "Error in applicaton (Invalid secretManager.)";
        	return false;
        }
        if(!is_array($params))
        {
            $error = "This page must be accessed from an LTI host.";
        	return false;
        }
        if(!isset($params["oauth_signature"]))
        {
            $error = "This page must be accessed from an LTI host. (missing oauth_signature)";
        	return false;
        }
        if((!isset($params["oauth_signature_method"]))||($params["oauth_signature_method"]!="HMAC-SHA1"))
        {
            $error = "This page must be accessed from an LTI host. (missing or invalid oauth_signature_method)";
        	return false;
        }
        if(!isset($params["oauth_nonce"]))
        {
            $error = "This page must be accessed from an LTI host. (missing oauth_nonce)";
        	return false;
        }
        if(!isset($params["oauth_consumer_key"]))
        {
            $error = "This page must be accessed from an LTI host. (missing oauth_consumer_key)";
        	return false;
        }
        if(!$secretManager->registerNonce($params["oauth_nonce"], $params["oauth_consumer_key"]))
        {
            $error = "Duplicate launch attempt detected.";
        	return false;
        }
        if((!isset($params["oauth_timestamp"]))||($params["oauth_timestamp"] < time()-3600))
        {
            $error = "This page must be accessed from an LTI host. (Missing or invalid oauth_timestamp.)";
        	return false;
        }
        $oldsig = $params["oauth_signature"];
        unset($params["oauth_signature"]);
        $sig = ltiSession::getOAuthSignature($params, getRequestURL(), $_SERVER['REQUEST_METHOD'], $secretManager->getSecret($params["oauth_consumer_key"]));
        if($sig == $oldsig)
        	return new ltiSession($params);
        else
        {
            $error = "LTI signature mismatch.";
        	return false;
        }
    }

    function getResourceKey()
    {
    	if(isset($this->params['oauth_consumer_key']) && isset($this->params['resource_link_id']))
        	return $this->params['oauth_consumer_key'].':'.$this->params['resource_link_id'];
        else
        	return false;
    }

    function isInstructor()
    {
    	if(isset($this->params['roles']) && (strpos($this->params['roles'], 'Instructor')!==false))
        	return true;
        else
        	return false;
    }

    static function getOAuthSignature($params, $endpoint, $method, $oauth_consumer_secret)
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

    /* Old Moodle 1.9 prototype version. Needs replaced with LTI Advantage equivalents.
    function retrieveRosterAvailable()
    {
    	if((isset($this->params['ext_ims_lis_memberships_url']))&&(isset($this->params['ext_ims_lis_memberships_id'])))
        	return true;
        else
        	return false;
    }

    function retriveRoster($secretManager)
    {
		$message = 'basic-lis-readmembershipsforcontext';
		$url = $this->params['ext_ims_lis_memberships_url'];
		$id = $this->params['ext_ims_lis_memberships_id'];

		$data = array(
          'oauth_version' => '1.0',
          'oauth_nonce' => md5(time().$this->params['user_id']),
          'oauth_timestamp' => time(),
          'lti_version' => 'LTI-1p0',
          'oauth_callback' => 'about:blank',
          'oauth_signature_method' => 'HMAC-SHA1',
		  'lti_message_type' => $message,
		  'id' => $id,
          'oauth_consumer_key' => $this->params["oauth_consumer_key"],
        );

    	$data['oauth_signature'] = ltiSession::getOAuthSignature($data, $url, "POST", $secretManager->getSecret($this->params["oauth_consumer_key"]));
		$retval = do_post_request($url, http_build_query($data));
        $response = new cls_message_response($retval);
        if($response->m_memberships)
            return $response->m_memberships->m_member;  // an array of cls_member
    }
    */

    function gradeMethodsAvailable()
    {
    	if((isset($this->params['lis_outcome_service_url']))&&(isset($this->params['lis_result_sourcedid'])))
        	return true;
    	elseif((isset($this->params['ext_ims_lis_basic_outcome_url']))&&(isset($this->params['lis_result_sourcedid'])))
        	return true;
        else
        	return false;
    }

    function setGrade($secretManager, $grade)
    {
    	if(isset($this->params['ext_ims_lis_basic_outcome_url']))
        	return $this->set_v1p0ext_Grade($secretManager, $grade);
        else
        	return $this->set_lis_Grade($secretManager, $grade);
    }

    private function set_v1p0ext_Grade($secretManager, $grade)
    {
		$message = 'basic-lis-updateresult';
		$url = $this->params['ext_ims_lis_basic_outcome_url'];
		$id = $this->params['lis_result_sourcedid'];

		$data = array(
          'oauth_version' => '1.0',
          'oauth_nonce' => md5(time().$this->params['user_id']),
          'oauth_timestamp' => time(),
          'lti_version' => 'LTI-1p0',
          'oauth_callback' => 'about:blank',
          'oauth_signature_method' => 'HMAC-SHA1',
		  'lti_message_type' => $message,
		  'sourcedid' => $id,
          'oauth_consumer_key' => $this->params["oauth_consumer_key"],
          'result_resultscore_textstring' => $grade,
        );
    	$data['oauth_signature'] = ltiSession::getOAuthSignature($data, $url, "POST", $secretManager->getSecret($this->params["oauth_consumer_key"]));
		$retval = do_post_request($url, http_build_query($data));
        return $retval;
    }

    private function set_lis_Grade($secretManager, $grade)
    {
		$message = 'basic-lis-updateresult';
		$url = $this->params['lis_outcome_service_url'];
		$id = $this->params['lis_result_sourcedid'];

    	$oauth_nonce = md5(time().$this->params['user_id']);


    	$msgbody = '<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
	<imsx_POXHeader>
		<imsx_POXRequestHeaderInfo>
			<imsx_version>V1.0</imsx_version>
			<imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
		</imsx_POXRequestHeaderInfo>
	</imsx_POXHeader>
	<imsx_POXBody>
		<OPERATION>
			<resultRecord>
				<sourcedGUID>
					<sourcedId>SOURCEDID</sourcedId>
				</sourcedGUID>
				<result>
					<resultScore>
						<language>en-us</language>
						<textString>GRADE</textString>
					</resultScore>
				</result>
			</resultRecord>
		</OPERATION>
	</imsx_POXBody>
</imsx_POXEnvelopeRequest>';
		$msgbody = trim(str_replace(array('MESSAGE','OPERATION','SOURCEDID','GRADE'), array($oauth_nonce, 'replaceResultRequest', $id, $grade) ,$msgbody));
	    $hash = base64_encode(sha1($msgbody, TRUE));

		$data = array(
          'oauth_version' => '1.0',
          'oauth_nonce' => $oauth_nonce,
          'oauth_timestamp' => time(),
          'oauth_signature_method' => 'HMAC-SHA1',
          'oauth_consumer_key' => $this->params["oauth_consumer_key"],
          'oauth_body_hash' => $hash,
        );

        //echo "<h5>Grade URL is $url</H5>";
    	$data['oauth_signature'] = ltiSession::getOAuthSignature($data, $url, "POST", $secretManager->getSecret($this->params["oauth_consumer_key"]));
        $header = "Authorization: OAuth ";
        foreach($data as $k => $v)
        	$header .= "$k=\"".rfc3986encode($v)."\",";
        $header = substr($header, 0, strlen($header)-1);
        $header .= "\r\nContent-type: application/xml\r\n";

		$retval = do_post_request($url, $msgbody, $header);
        return $retval;
        $POXResponse = new cls_imsx_POXEnvelopeResponse($retval);
        if(isset($POXResponse->m_imsx_POXHeader->m_imsx_POXResponseHeaderInfo->m_imsx_statusInfo->m_imsx_description))
        	return $POXResponse->m_imsx_POXHeader->m_imsx_POXResponseHeaderInfo->m_imsx_statusInfo->m_imsx_description;
        else
        	return "Error (IMS POX response message not found)";
    }
};

function do_post_request($url, $data, $optional_headers = null)
{
	$header = '';
	$params = array('http' => array(
	            'method' => 'POST',
	            'content' => $data
	          ));
    // To use a proxy, somthing like this, or maybe use curl...
	//$params['http']['proxy'] = 'tcp://127.0.0.1:8080'; $params['http']['request_fulluri'] = true;


	if ($optional_headers !== null)
    {
		$header = $optional_headers . "\r\n";
	}
	//$header = $header . "Content-type: application/x-www-form-urlencoded\r\n";
	$params['http']['header'] = $header;
	$ctx = stream_context_create($params);
	$fp = @fopen($url, 'rb', false, $ctx);
	if (!$fp)
    {
		throw new Exception("Problem with $url, $php_errormsg");
	}
	$response = @stream_get_contents($fp);
	if ($response === false)
    {
		throw new Exception("Problem reading data from $url, $php_errormsg");
	}
	return $response;
}


abstract class ltiSecretManager
{
    abstract function getSecret($consumerKey); // String
    abstract function getDomain($consumerKey); // String
    abstract function registerNonce($nonce, $consumerKey); // boolean
    // Methods for admin interface
    abstract function getKeyList();
    abstract function deleteKeyAndSecret($consumerKey);
    abstract function addOrUpdateKeyAndSecret($consumerKey, $secret, $domain);
};

function rfc3986encode($input)
{
	return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
}

/**************** From lti_xml.php *******************************/
/*
//xdef2 can be used to generate PHP or Java classes for an XML file.

element imsx_POXEnvelopeRequest
attributes
content imsx_POXHeader, imsx_POXBody

element imsx_POXBody
attributes
content readResultRequest?, replaceResultResponse?, replaceResultRequest?, deleteResultResponse?, readResultResponse?

element imsx_POXHeader
attributes
content imsx_POXResponseHeaderInfo?, imsx_POXRequestHeaderInfo?

element readResultRequest
attributes
content resultRecord

element replaceResultRequest
attributes
content resultRecord

element imsx_POXRequestHeaderInfo
attributes
content imsx_version, imsx_messageIdentifier as string

element resultRecord
attributes
content result/resultScore, sourcedGUID

element sourcedGUID
attributes
content sourcedId as string

element resultScore
attributes
content textString as float, language as string

element imsx_POXEnvelopeResponse
attributes
content imsx_POXBody, imsx_POXHeader

element replaceResultResponse
attributes

element deleteResultResponse
attributes

element readResultResponse
attributes
content result/resultScore

element imsx_POXResponseHeaderInfo
attributes
content imsx_version as string, imsx_messageIdentifier as string, imsx_statusInfo

element imsx_statusInfo
attributes
content imsx_codeMajor as string, imsx_description as string, imsx_severity as string, imsx_messageRefIdentifier as string, imsx_operationRefIdentifier as string
*/

//message_response
class cls_message_response //extends
{
	//vars for attributes
	//vars for elements
	var $m_lti_message_type;
	var $m_statusinfo;
	var $m_memberships;

    function __construct($xml=false)
    {
        //initialise
        $this->m_memberships = false;
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'lti_message_type':
                    if($this->m_lti_message_type==false)
                        $this->m_lti_message_type = strval($cxml);
                    break;
                case 'statusinfo':
                    if($this->m_statusinfo==false)
                        $this->m_statusinfo = new cls_statusinfo($cxml);
                    break;
                case 'memberships':
                    if($this->m_memberships==false)
                        $this->m_memberships = new cls_memberships($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
            $pad = str_repeat('    ',$indent);
        $out = '<?xml version="1.0"?>';
        if($neat) $out .= "\n$pad";
        $out .= '<message_response';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<lti_message_type>'.htmlentities($this->m_lti_message_type).'</lti_message_type>';
        $out .= $this->m_statusinfo->toXML($neat, $indent+1);
        if($this->m_memberships !== false)
        {
            $out .= $this->m_memberships->toXML($neat, $indent+1);
        }
        if($neat) $out .= "\n$pad";
        $out .= '</message_response>';
        return $out;
    }

};

//statusinfo
class cls_statusinfo //extends
{
	//vars for attributes
	//vars for elements
	var $m_codemajor;
	var $m_severity;
	var $m_description;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'codemajor':
                    if($this->m_codemajor==false)
                        $this->m_codemajor = strval($cxml);
                    break;
                case 'severity':
                    if($this->m_severity==false)
                        $this->m_severity = strval($cxml);
                    break;
                case 'description':
                    if($this->m_description==false)
                        $this->m_description = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<statusinfo';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<codemajor>'.htmlentities($this->m_codemajor).'</codemajor>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<severity>'.htmlentities($this->m_severity).'</severity>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<description>'.htmlentities($this->m_description).'</description>';
        if($neat) $out .= "\n$pad";
        $out .= '</statusinfo>';
        return $out;
    }

};

//memberships
class cls_memberships //extends
{
	//vars for attributes
	//vars for elements
	var $m_member;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        $this->m_member = array();
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'member':
                    $this->m_member[] = new cls_member($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<memberships';
        $out .= '>';
        foreach($this->m_member as $m_member)
        {
            $out .= $m_member->toXML($neat, $indent+1);
        }
        if($neat) $out .= "\n$pad";
        $out .= '</memberships>';
        return $out;
    }

};

//member
class cls_member //extends
{
	//vars for attributes
	//vars for elements
	var $m_user_id;
	var $m_roles;
	var $m_person_name_given;
	var $m_person_name_family;
	var $m_person_contact_email_primary;
	var $m_lis_result_sourcedid;

    function __construct($xml=false)
    {
        //initialise
        $this->m_person_name_given = false;
        $this->m_person_name_family = false;
        $this->m_person_contact_email_primary = false;
        $this->m_lis_result_sourcedid = false;
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'user_id':
                    if($this->m_user_id==false)
                        $this->m_user_id = strval($cxml);
                    break;
                case 'roles':
                    if($this->m_roles==false)
                        $this->m_roles = strval($cxml);
                    break;
                case 'person_name_given':
                    if($this->m_person_name_given==false)
                        $this->m_person_name_given = strval($cxml);
                    break;
                case 'person_name_family':
                    if($this->m_person_name_family==false)
                        $this->m_person_name_family = strval($cxml);
                    break;
                case 'person_contact_email_primary':
                    if($this->m_person_contact_email_primary==false)
                        $this->m_person_contact_email_primary = strval($cxml);
                    break;
                case 'lis_result_sourcedid':
                    if($this->m_lis_result_sourcedid==false)
                        $this->m_lis_result_sourcedid = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<member';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<user_id>'.htmlentities($this->m_user_id).'</user_id>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<roles>'.htmlentities($this->m_roles).'</roles>';
        if($this->m_person_name_given !== false)
        {
            if($neat) $out .= "\n$pad    ";
            $out .= '<person_name_given>'.htmlentities($this->m_person_name_given).'</person_name_given>';
        }
        if($this->m_person_name_family !== false)
        {
            if($neat) $out .= "\n$pad    ";
            $out .= '<person_name_family>'.htmlentities($this->m_person_name_family).'</person_name_family>';
        }
        if($this->m_person_contact_email_primary !== false)
        {
            if($neat) $out .= "\n$pad    ";
            $out .= '<person_contact_email_primary>'.htmlentities($this->m_person_contact_email_primary).'</person_contact_email_primary>';
        }
        if($this->m_lis_result_sourcedid !== false)
        {
            if($neat) $out .= "\n$pad    ";
            $out .= '<lis_result_sourcedid>'.htmlentities($this->m_lis_result_sourcedid).'</lis_result_sourcedid>';
        }
        if($neat) $out .= "\n$pad";
        $out .= '</member>';
        return $out;
    }

};

//imsx_POXEnvelopeRequest
class cls_imsx_POXEnvelopeRequest //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_POXHeader;
	var $m_imsx_POXBody;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_POXHeader':
                    if($this->m_imsx_POXHeader==false)
                        $this->m_imsx_POXHeader = new cls_imsx_POXHeader($cxml);
                    break;
                case 'imsx_POXBody':
                    if($this->m_imsx_POXBody==false)
                        $this->m_imsx_POXBody = new cls_imsx_POXBody($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
            $pad = str_repeat('    ',$indent);
        $out = '<?xml version="1.0"?>';
        if($neat) $out .= "\n$pad";
        $out .= '<imsx_POXEnvelopeRequest';
        $out .= '>';
        $out .= $this->m_imsx_POXHeader->toXML($neat, $indent+1);
        $out .= $this->m_imsx_POXBody->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXEnvelopeRequest>';
        return $out;
    }

};

//imsx_POXBody
class cls_imsx_POXBody //extends
{
	//vars for attributes
	//vars for elements
	var $m_readResultRequest;
	var $m_replaceResultResponse;
	var $m_replaceResultRequest;
	var $m_deleteResultResponse;
	var $m_readResultResponse;

    function __construct($xml=false)
    {
        //initialise
        $this->m_readResultRequest = false;
        $this->m_replaceResultResponse = false;
        $this->m_replaceResultRequest = false;
        $this->m_deleteResultResponse = false;
        $this->m_readResultResponse = false;
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'readResultRequest':
                    if($this->m_readResultRequest==false)
                        $this->m_readResultRequest = new cls_readResultRequest($cxml);
                    break;
                case 'replaceResultResponse':
                    if($this->m_replaceResultResponse==false)
                        $this->m_replaceResultResponse = new cls_replaceResultResponse($cxml);
                    break;
                case 'replaceResultRequest':
                    if($this->m_replaceResultRequest==false)
                        $this->m_replaceResultRequest = new cls_replaceResultRequest($cxml);
                    break;
                case 'deleteResultResponse':
                    if($this->m_deleteResultResponse==false)
                        $this->m_deleteResultResponse = new cls_deleteResultResponse($cxml);
                    break;
                case 'readResultResponse':
                    if($this->m_readResultResponse==false)
                        $this->m_readResultResponse = new cls_readResultResponse($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<imsx_POXBody';
        $out .= '>';
        if($this->m_readResultRequest !== false)
        {
            $out .= $this->m_readResultRequest->toXML($neat, $indent+1);
        }
        if($this->m_replaceResultResponse !== false)
        {
            $out .= $this->m_replaceResultResponse->toXML($neat, $indent+1);
        }
        if($this->m_replaceResultRequest !== false)
        {
            $out .= $this->m_replaceResultRequest->toXML($neat, $indent+1);
        }
        if($this->m_deleteResultResponse !== false)
        {
            $out .= $this->m_deleteResultResponse->toXML($neat, $indent+1);
        }
        if($this->m_readResultResponse !== false)
        {
            $out .= $this->m_readResultResponse->toXML($neat, $indent+1);
        }
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXBody>';
        return $out;
    }

};

//imsx_POXHeader
class cls_imsx_POXHeader //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_POXResponseHeaderInfo;
	var $m_imsx_POXRequestHeaderInfo;

    function __construct($xml=false)
    {
        //initialise
        $this->m_imsx_POXResponseHeaderInfo = false;
        $this->m_imsx_POXRequestHeaderInfo = false;
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_POXResponseHeaderInfo':
                    if($this->m_imsx_POXResponseHeaderInfo==false)
                        $this->m_imsx_POXResponseHeaderInfo = new cls_imsx_POXResponseHeaderInfo($cxml);
                    break;
                case 'imsx_POXRequestHeaderInfo':
                    if($this->m_imsx_POXRequestHeaderInfo==false)
                        $this->m_imsx_POXRequestHeaderInfo = new cls_imsx_POXRequestHeaderInfo($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<imsx_POXHeader';
        $out .= '>';
        if($this->m_imsx_POXResponseHeaderInfo !== false)
        {
            $out .= $this->m_imsx_POXResponseHeaderInfo->toXML($neat, $indent+1);
        }
        if($this->m_imsx_POXRequestHeaderInfo !== false)
        {
            $out .= $this->m_imsx_POXRequestHeaderInfo->toXML($neat, $indent+1);
        }
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXHeader>';
        return $out;
    }

};

//readResultRequest
class cls_readResultRequest //extends
{
	//vars for attributes
	//vars for elements
	var $m_resultRecord;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'resultRecord':
                    if($this->m_resultRecord==false)
                        $this->m_resultRecord = new cls_resultRecord($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<readResultRequest';
        $out .= '>';
        $out .= $this->m_resultRecord->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</readResultRequest>';
        return $out;
    }

};

//replaceResultRequest
class cls_replaceResultRequest //extends
{
	//vars for attributes
	//vars for elements
	var $m_resultRecord;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'resultRecord':
                    if($this->m_resultRecord==false)
                        $this->m_resultRecord = new cls_resultRecord($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<replaceResultRequest';
        $out .= '>';
        $out .= $this->m_resultRecord->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</replaceResultRequest>';
        return $out;
    }

};

//imsx_POXRequestHeaderInfo
class cls_imsx_POXRequestHeaderInfo //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_version;
	var $m_imsx_messageIdentifier;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_version':
                    if($this->m_imsx_version==false)
                        $this->m_imsx_version = ($cxml);
                    break;
                case 'imsx_messageIdentifier':
                    if($this->m_imsx_messageIdentifier==false)
                        $this->m_imsx_messageIdentifier = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<imsx_POXRequestHeaderInfo';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_version>'.htmlentities($this->m_imsx_version).'</imsx_version>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_messageIdentifier>'.htmlentities($this->m_imsx_messageIdentifier).'</imsx_messageIdentifier>';
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXRequestHeaderInfo>';
        return $out;
    }

};

//resultRecord
class cls_resultRecord //extends
{
	//vars for attributes
	//vars for elements
	var $m_resultScore;
	var $m_sourcedGUID;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'resultScore':
                    if($this->m_resultScore==false)
                        $this->m_resultScore = new cls_resultScore($cxml);
                    break;
                case 'sourcedGUID':
                    if($this->m_sourcedGUID==false)
                        $this->m_sourcedGUID = new cls_sourcedGUID($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<resultRecord';
        $out .= '>';
        $out .= $this->m_resultScore->toXML($neat, $indent+1);
        $out .= $this->m_sourcedGUID->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</resultRecord>';
        return $out;
    }

};

//sourcedGUID
class cls_sourcedGUID //extends
{
	//vars for attributes
	//vars for elements
	var $m_sourcedId;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'sourcedId':
                    if($this->m_sourcedId==false)
                        $this->m_sourcedId = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<sourcedGUID';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<sourcedId>'.htmlentities($this->m_sourcedId).'</sourcedId>';
        if($neat) $out .= "\n$pad";
        $out .= '</sourcedGUID>';
        return $out;
    }

};

//resultScore
class cls_resultScore //extends
{
	//vars for attributes
	//vars for elements
	var $m_textString;
	var $m_language;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'textString':
                    if($this->m_textString==false)
                        $this->m_textString = floatval($cxml);
                    break;
                case 'language':
                    if($this->m_language==false)
                        $this->m_language = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<resultScore';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<textString>'.htmlentities($this->m_textString).'</textString>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<language>'.htmlentities($this->m_language).'</language>';
        if($neat) $out .= "\n$pad";
        $out .= '</resultScore>';
        return $out;
    }

};

//imsx_POXEnvelopeResponse
class cls_imsx_POXEnvelopeResponse //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_POXBody;
	var $m_imsx_POXHeader;

    function __construct($xml=false)
    {
        //initialise
		$this->m_imsx_POXBody=false;
		$this->m_imsx_POXHeader=false;
		try
        {
	        if((is_string($xml))&&(strpos(trim($xml),'<')===0))
            {
	            $xml=new SimpleXMLElement($xml);
	        	if($xml)
	            	$this->parseIn($xml);
            }
        }
        catch(Exception $e)
        {
        	echo $e->getMessage();
		}
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_POXBody':
                    if($this->m_imsx_POXBody==false)
                        $this->m_imsx_POXBody = new cls_imsx_POXBody($cxml);
                    break;
                case 'imsx_POXHeader':
                    if($this->m_imsx_POXHeader==false)
                        $this->m_imsx_POXHeader = new cls_imsx_POXHeader($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
            $pad = str_repeat('    ',$indent);
        $out = '<?xml version="1.0"?>';
        if($neat) $out .= "\n$pad";
        $out .= '<imsx_POXEnvelopeResponse';
        $out .= '>';
        $out .= $this->m_imsx_POXBody->toXML($neat, $indent+1);
        $out .= $this->m_imsx_POXHeader->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXEnvelopeResponse>';
        return $out;
    }

};

//replaceResultResponse
class cls_replaceResultResponse //extends
{
	//vars for attributes
	//vars for elements

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<replaceResultResponse';
        $out .= '>';
        if($neat) $out .= "\n$pad";
        $out .= '</replaceResultResponse>';
        return $out;
    }

};

//deleteResultResponse
class cls_deleteResultResponse //extends
{
	//vars for attributes
	//vars for elements

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<deleteResultResponse';
        $out .= '>';
        if($neat) $out .= "\n$pad";
        $out .= '</deleteResultResponse>';
        return $out;
    }

};

//readResultResponse
class cls_readResultResponse //extends
{
	//vars for attributes
	//vars for elements
	var $m_resultScore;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'resultScore':
                    if($this->m_resultScore==false)
                        $this->m_resultScore = new cls_resultScore($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<readResultResponse';
        $out .= '>';
        $out .= $this->m_resultScore->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</readResultResponse>';
        return $out;
    }

};

//imsx_POXResponseHeaderInfo
class cls_imsx_POXResponseHeaderInfo //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_version;
	var $m_imsx_messageIdentifier;
	var $m_imsx_statusInfo;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_version':
                    if($this->m_imsx_version==false)
                        $this->m_imsx_version = strval($cxml);
                    break;
                case 'imsx_messageIdentifier':
                    if($this->m_imsx_messageIdentifier==false)
                        $this->m_imsx_messageIdentifier = strval($cxml);
                    break;
                case 'imsx_statusInfo':
                    if($this->m_imsx_statusInfo==false)
                        $this->m_imsx_statusInfo = new cls_imsx_statusInfo($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<imsx_POXResponseHeaderInfo';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_version>'.htmlentities($this->m_imsx_version).'</imsx_version>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_messageIdentifier>'.htmlentities($this->m_imsx_messageIdentifier).'</imsx_messageIdentifier>';
        $out .= $this->m_imsx_statusInfo->toXML($neat, $indent+1);
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_POXResponseHeaderInfo>';
        return $out;
    }

};

//imsx_statusInfo
class cls_imsx_statusInfo //extends
{
	//vars for attributes
	//vars for elements
	var $m_imsx_codeMajor;
	var $m_imsx_description;
	var $m_imsx_severity;
	var $m_imsx_messageRefIdentifier;
	var $m_imsx_operationRefIdentifier;

    function __construct($xml=false)
    {
        //initialise
        if(is_string($xml))
            $xml=new SimpleXMLElement($xml);
        if($xml)
            $this->parseIn($xml);
    }

    function parseIn($xml)
    {
        foreach($xml->xpath("*") as $cxml)
        {
            $ename = $cxml->getName();
            switch($ename)
            {
                case 'imsx_codeMajor':
                    if($this->m_imsx_codeMajor==false)
                        $this->m_imsx_codeMajor = strval($cxml);
                    break;
                case 'imsx_description':
                    if($this->m_imsx_description==false)
                        $this->m_imsx_description = strval($cxml);
                    break;
                case 'imsx_severity':
                    if($this->m_imsx_severity==false)
                        $this->m_imsx_severity = strval($cxml);
                    break;
                case 'imsx_messageRefIdentifier':
                    if($this->m_imsx_messageRefIdentifier==false)
                        $this->m_imsx_messageRefIdentifier = strval($cxml);
                    break;
                case 'imsx_operationRefIdentifier':
                    if($this->m_imsx_operationRefIdentifier==false)
                        $this->m_imsx_operationRefIdentifier = strval($cxml);
                    break;
            }
        }
    }

    function toXML($neat=false, $indent=0)
    {
        if($neat)
        {
            $pad = str_repeat('    ',$indent);
            $out .= "\n$pad";
        }
        $out .= '<imsx_statusInfo';
        $out .= '>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_codeMajor>'.htmlentities($this->m_imsx_codeMajor).'</imsx_codeMajor>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_description>'.htmlentities($this->m_imsx_description).'</imsx_description>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_severity>'.htmlentities($this->m_imsx_severity).'</imsx_severity>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_messageRefIdentifier>'.htmlentities($this->m_imsx_messageRefIdentifier).'</imsx_messageRefIdentifier>';
        if($neat) $out .= "\n$pad    ";
        $out .= '<imsx_operationRefIdentifier>'.htmlentities($this->m_imsx_operationRefIdentifier).'</imsx_operationRefIdentifier>';
        if($neat) $out .= "\n$pad";
        $out .= '</imsx_statusInfo>';
        return $out;
    }

};
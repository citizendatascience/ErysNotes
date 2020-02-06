<?php

// This is a very basic secret manager for tools that will only require LTI configuraton by the site manager.

class minimalSecretManager extends ltiSecretManager
{
	var $data;
    var $filename;

	function __construct()
    {
        global $CFG;
        if((!isset($CFG['ltikeys']))||(!is_array($CFG['ltikeys'])))
            exit("Associative array \$CFG['ltikeys'] needs included before ".__FILE__." with form array('key'=>'secret', ...);");
        $this->data = array();
        foreach($CFG['ltikeys'] as $key=>$secret)
        {
            $this->data[$key] = array('domain'=>'/', 'secret'=>$secret);
        }
    }

    function getSecret($key)
    {
        if(!isset($this->data[$key]))
        	return false;
        else
        	return $this->data[$key]['secret'];
    }

    function getDomain($key)
    {
        if(!isset($this->data[$key]))
        	return false;
        else
        	return $this->data[$key]['domain'];
    }

    function registerNonce($nonce, $consumerKey)
    {
    	return true;
    }

    function getKeyList()
    {
        return array_keys($this->data);
    }

    function deleteKeyAndSecret($consumerKey)
    {
        exit("This LTI tool uses minimalSecretManager, which means that key/secret combinations need edited in the configuration.");
    }

    function addOrUpdateKeyAndSecret($consumerKey, $secret, $domain)
    {
        exit("This LTI tool uses minimalSecretManager, which means that key/secret combinations need edited in the configuration.");
    }

}



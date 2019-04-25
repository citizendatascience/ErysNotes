<?php
include_once('config.php');

// Do not use this in production (or even public demo) sites. 
// It is a quick start for development behind a very secure firewall only.

class tmpDevSecretManager extends ltiSecretManager
{
	var $data;
    var $filename;

	function __construct()
    {
        $this->filename = dirname(__FILE__, 2).'/tmpsecrets.ser';
        if(file_exists($this->filename))
            $this->data = unserialize(file_get_contents($this->filename));
        else
        {
            $this->data = array('12345'=>array('domain'=>'/', 'secret'=>'secret'));
            file_put_contents($this->filename, serialize($this->data));
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
        unset($this->data[$consumerKey]);
        file_put_contents($this->filename, serialize($this->data));
    }

    function addOrUpdateKeyAndSecret($consumerKey, $secret, $domain)
    {
        $this->data[$consumerKey] = array('domain'=>$domain, 'secret'=> $secret);
        file_put_contents($this->filename, serialize($this->data));
    }

}



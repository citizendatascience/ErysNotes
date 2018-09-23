<?php

class dataConnection
{
    private static $dbconn=null;

    public static function connect()
    {
    	global $DBCFG;
        self::$dbconn = new SQLite3($DBCFG['file']);
        self::$dbconn->busyTimeout(100);
    }

    public static function runSimpleQuery($query)
    {
        if(self::$dbconn==null)
            self::connect();
        $err = "";
        $result = self::$dbconn->query($query);
    }

   public static function runQuery($query)
    {
    	//echo "$query<br/>";
        if(self::$dbconn==null)
            self::connect();
        if(strtolower(substr(trim($query),0,6))=='insert')
            return self::$dbconn->exec($query);
         $result = self::$dbconn->query($query);
        if ($result === false)
        {
            die(self::$dbconn->lastErrorMsg());
        }
        $output = array();
        if($result !== true)
        {
	        while ($row = $result->fetchArray())
	        {
	            $output[] = $row;
	        }
        }
        return $output;  
    }

    public static function close()
    {
        if(self::$dbconn!=null)
            self::$dbconn->close();
        self::$dbconn = null;
    }

    public static function safe($in)
    {
		if (self::$dbconn==NULL)
    	{
	    	dataConnection::connect();
		}
	  	return self::$dbconn->escapeString($in);
	}

	public static function db2date($in)
	{
	    list($y,$m,$d) = explode("-",$in);
	    return mktime(0,0,0,$m,$d,$y);
	}

	public static function date2db($in)
	{
	    return strftime("%Y-%m-%d", $in);
	}

	public static function db2time($in)
	{
    	//echo "db2time($in) ";
        list($dt, $ti) = explode(" ",$in);
	    list($y,$m,$d) = explode("-",$dt);
	    list($hh,$mm,$ss) = explode(":",$ti);
	    return mktime($hh,$mm,$ss,$m,$d,$y);
	}

	public static function time2db($in)
	{
    	//echo "time2db($in)<br/>";
	    return strftime("%Y-%m-%d %H:%M:%S", $in);
	}
};





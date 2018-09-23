<?php

//Class will replace link2html function. Refactoring in progress...
class wikiLink
{
	var $src, $srcpage;

    var $wpath, $wpage; // the full dir.subdir. and dir.subdir.wikiName of the target
    var $forpage; // the full wname of the page being rendered
    var $srpath; // the wpath of the page where the link is defined.
    var $pgdepth; // the depth from site root of the page being rendered

	function __construct($linkSrc, $sourcepage=false)
    {
    	$this->src = $linkSrc;
        $this->srcpage = $sourcepage;


 	    global $cmsGlobals;
	    $link = $linkSrc;
        $this->forpage = $cmsGlobals['page'];

	    if(strrpos($cmsGlobals['page'],'.')===false)
	    {
	        $pgpath = "";
	        $this->pgdepth = 0;
	    }
	    else
	    {
	        $pgpath = substr($cmsGlobals['page'],0,strrpos($cmsGlobals['page'],'.'));
	        $this->pgdepth = 0;
	        $pos = 0;
	        while($pos !== false)
	        {
	            $this->pgdepth++;
	            $pos = strpos($pgpath. ".", $pos+1);
	        }
	        $pgpath .= ".";
	    }
	    if($this->srcpage !== false)
	    {
	        if(strrpos($this->srcpage,'.')===false)
	            $this->srpath = "";
	        else
	            $this->srpath = substr($this->srcpage,0,strrpos($this->srcpage,'.')+1);
	    }
	    else
	        $this->srpath = $pgpath;

/*	    $link = trim($link);
	    if((substr($link, 0, 2)=="[[")&&(substr($link, strlen($link)-2)=="]]"))
	        $link = trim(substr($link,2,strlen($link)-4));
	    if(strpos($link, "|")==false)
	    {
	        $text = $link;
	        if((substr($text, strlen($text)-1)==".")||(substr($text, strlen($text)-1)=="/"))
	            $text = substr($text, 0, strlen($text)-1);
	    }
	    else
	        list($link,$text) = explode("|",$link,2);
	    $link = preg_replace('/\\s+(\\w)/e', "strtoupper('$1')", $link);   */
   }

    function getLink($preview, $cssclass="")
    {
    	return $this->_link2html($this->src, $preview, $this->srcpage, $cssclass);
    }

	function _link2html($linkSrc, $preview, $sourcepage=false, $cssclass="")
	{
	    //echo "<p>$linkSrc, $preview, $sourcepage<br/>";
	    global $cmsGlobals;

	    $link = $linkSrc;

     	$pgtype = substr($cmsGlobals['indexpage'],strpos($cmsGlobals['indexpage'],"."));
    	$extra = "";

	    if(strrpos($cmsGlobals['page'],'.')===false)
	    {
	        $pgpath = "";
	        $pgdepth = 0;
	    }
	    else
	    {
	        $pgpath = substr($cmsGlobals['page'],0,strrpos($cmsGlobals['page'],'.'));
	        $pgdepth = 0;
	        $pos = 0;
	        while($pos !== false)
	        {
	            $pgdepth++;
	            $pos = strpos($pgpath. ".", $pos+1);
	        }
	        $pgpath .= ".";
	    }
	    if($sourcepage !== false)
	    {
	        if(strrpos($sourcepage,'.')===false)
	            $srpath = "";
	        else
	            $srpath = substr($sourcepage,0,strrpos($sourcepage,'.')+1);
	    }
	    else
	        $srpath = $pgpath;

	    $link = trim($link);
	    if((substr($link, 0, 2)=="[[")&&(substr($link, strlen($link)-2)=="]]"))
	        $link = trim(substr($link,2,strlen($link)-4));
	    if(strpos($link, "|")==false)
	    {
	        $text = $link;
	        if((substr($text, strlen($text)-1)==".")||(substr($text, strlen($text)-1)=="/"))
	            $text = substr($text, 0, strlen($text)-1);
	    }
	    else
	        list($link,$text) = explode("|",$link,2);
	    $link = preg_replace('/\\s+(\\w)/e', "strtoupper('$1')", $link);
	    // Check if it's an absolute or file link
	    $abs = false;
	    if(substr($link,0,1)=="@")
	    {
	        $abs = true;
	        $link = substr($link, 1);
	        if($text == "") $text = $link;
	        $link = $this->makeLinkRel(str_replace(".","/",$pgpath), str_replace(".","/",$srpath).$link);
	    }
	    if(substr($link,0,7)=="Attach:")
	    {
	        $abs = true;
	        $link = substr($link, 7);
	        if($text == "") $text = $link;
	        if(!checkUploadExists($link))
	        {
	            if($preview)
	            {
	                //$link = buildGetURL("uploads");
                    $link = '#';
	                $extra = "<a href='$link'>&Delta;</a>";
	            }
	        }
	    }
	    if(preg_match('/^(https?|ftp):\/\/([-A-Z0-9.]+)(\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)/i',$link))
	    {
	        $abs = true;
	    }
	    if(!$abs)
	    {
	        if($preview)
	        {
	            $link = str_replace("/",".",$link);
	            if(substr($link, strlen($link)-1)==".")
	                $link .= $cmsGlobals['defaultpage'];
	            if(substr($link, 0,1)==".")
	                $link = substr($link,1);
	            else
	                $link = $srpath . $link;
	            if(!pageExists($link))
	                $text .= "<span style='color: red;'>?</span>";
	            $link = GetPagelinkURL($link);
	        }
	        else
	        {
	            //echo "<p>Building a link from $sourcepage in $pgpath to $link ($linkSrc)<br/>";
	            //# still to fix this for source page is not in root dir
	            $link = str_replace(".","/",$link);
	            if(substr($link, strlen($link)-1)=="/")
	                $link .= $cmsGlobals['indexpage'];
	            else
	                $link .= $pgtype;
	            if(substr($link, 0,1)=="/")
	            {
	                $link = substr($link,1);
	                for($n=0; $n<$pgdepth; $n++)
	                    $link = "../".$link;
	            }
	            else
	            {
	                //echo "$sourcepage - ";
	                $link = $this->makeLinkRel(str_replace(".","/",$pgpath), str_replace(".","/",$srpath).$link);
	            }
	        }
	    }
	    //if($text == "") $text = $link;
	    //echo "$link $text</p>";
	    if($cssclass != "")
	        $cssclass = "class=\"$cssclass\"";
	    if($preview==true)
	        return "<a href=\"?page=$link\" $cssclass>$text</a>".$extra;
	    else
	        return "<a href=\"?page=$link\" $cssclass>$text</a>";

	}

/*	function getLinkDir($linkSrc, $sourcepage=false)
	{
	    global $cmsGlobals;
	    $link = trim($linkSrc);
	    if((substr($link, 0, 2)=="[[")&&(substr($link, strlen($link)-2)=="]]"))
	        $link = trim(substr($link,2,strlen($link)-4));
	    if(strpos($link, "|")!==false)
	        $link = substr($link, 0, strpos($link,"|"));
	    $link = preg_replace('/\\s+(\\w)/e', "strtoupper('$1')", $link);
	    if((substr($link,0,1)=="@")
	            ||(substr($link,0,7)=="Attach:")
	            ||(preg_match('/^(https?|ftp):\/\/([-A-Z0-9.]+)(\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)/i',$link)))
	    {
	        // Absolute link, not relevent, this code is only for Wiki links.
	        return false;
	    }

	    if($sourcepage !== false)
	    {
	        if(strrpos($sourcepage,'.')===false)
	            $srpath = "";
	        else
	            $srpath = substr($sourcepage,0,strrpos($sourcepage,'.')+1);
	    }
	    else
	    {
	        if(strrpos($cmsGlobals['page'],'.')===false)
	            $srpath = "";
	        else
	            $srpath = substr($cmsGlobals['page'],0,strrpos($cmsGlobals['page']+1,'.'));
	    }

	    $link = str_replace("/",".",$link);
	    if(substr($link, 0,1)==".")
	        $link = substr($link,1);
	    else
	        $link = $srpath . $link;
	    if(strpos($link,".")!==false)
	        $linkDir = substr($link, 0, strrpos($link,"."));
	    else
	        $linkDir = "";
	    return $linkDir;
	}*/

	function makeLinkRel($srcPath, $target)
	{
	    $srcparts = explode("/", $srcPath);
	    $targparts = explode("/", $target);
	    $identCount = 0;
	    while(($identCount < sizeof($srcparts))&&($identCount < sizeof($targparts))&&($srcparts[$identCount]==$targparts[$identCount]))
	        $identCount++;
	    $out = "";
	    for($n = $identCount; $n<sizeof($srcparts); $n++)
	    {
	        if($srcparts[$n] != "")
	            $out .= "../";
	    }
	    for($n = $identCount; $n<sizeof($targparts)-1; $n++)
	    {
	        $out .= $targparts[$n]."/";;
	    }
	    $out .= $targparts[sizeof($targparts)-1];
	    return $out;
	}



};

function link2html2($linkSrc, $preview, $sourcePage=false, $cssclass="")
{
    $link = new wikiLink($linkSrc, $sourcePage);
    return $link->getLink($preview, $cssclass);
}

?>

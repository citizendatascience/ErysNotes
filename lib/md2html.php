<?php

define("actionType_replace", 1);
define("actionType_split", 2);
define("actionType_join", 3);
define("actionType_joinBuildingBlocks", 4);
define("actionType_remove", 5);
define("actionType_restore", 6);
define("actionType_calldelegate", 7);
define("actionType_repeatreplace", 8);
define("actionType_repeatcalldelegate", 9);

class md2html
{
    var $rules;
    var $customProcessing;

    function __construct($rulesFile=false)
    {
        $this->customProcessing = array();
        if(($rulesFile != false)&&(file_exists($rulesFile)))
            $ruleDefs = file($rulesFile);
        elseif(file_exists(dirname(__FILE__).'/defaultRules.txt'))
            $ruleDefs = file(dirname(__FILE__).'/defaultRules.txt');
        else
            exit("Attempt to construct class md2html with no rules.");
        if(!$this->attemptParse($ruleDefs))
        {
            exit("Error parsing md2html rules.");
        }

        $this->customProcessing['image'] = 'imageProcess';
        $this->customProcessing['linkbuilder'] = 'wikiLinkProcess';
    }

    function attemptParse($ruleDefs)
    {
        $this->rules = array();
        if(!is_array($ruleDefs))
            $ruleDefs = explode("\n", $ruleDefs);
        foreach($ruleDefs as $rule)
        {
            $rule = trim($rule, "\t\n\r\0\x08\xEF\xBB\xBF"); // Trim UTF Byte order marks and whitespace.
            if(preg_match('/^\s*(?<type>\w+)\s*("(?<find>(""|[^"])+)")?\s*(("(?<replace>([^"]|\\\\")+)")|(?<label>\w+))?/', $rule, $match))
            {
                //echo '<pre>'.print_r($match, true).'</pre>';
                switch($match['type'])
                {
                    case 'replace':
                        if(isset($match['label']))
                            $this->rules[] = array('type'=>actionType_calldelegate, 'rx'=>$this->sort_regex($match['find']), 'label'=>$match['label']);
                        else
                            $this->rules[] = array('type'=>actionType_replace, 'rx'=>$this->sort_regex($match['find']), 'replace'=>$match['replace']);
                        break;
                    case 'repeatreplace':
                        if(isset($match['label']))
                            $this->rules[] = array('type'=>actionType_repeatcalldelegate, 'rx'=>$this->sort_regex($match['find']), 'label'=>$match['label']);
                        else
                            $this->rules[] = array('type'=>actionType_repeatreplace, 'rx'=>$this->sort_regex($match['find']), 'replace'=>$match['replace']);
                        break;
                    case 'remove':
                        $this->rules[] = array('type'=>actionType_remove, 'rx'=>$this->sort_regex($match['find']), 'label'=>$match['label']);
                        break;
                    case 'split':
                        $this->rules[] = array('type'=>actionType_split);
                        break;
                    case "ProcessBlocks":
                        $this->rules[] = array('type'=>actionType_joinBuildingBlocks);
                        break;
                    case "restoreAll":
                        $this->rules[] = array('type'=>actionType_restore, 'label'=>null);
                        break;
                    default:
                        exit("Failed to process rule $rule");
                        break;
                }
            }
            else
            {
                if(!preg_match('/^\s*[;#\/].*/', $rule)) // comments
                    exit( "Can't parse rule:<br/>$rule");
            }
        }
        return true;
    }

    function Convert($wikiText)
    {
        $wikiText = trim($wikiText, "\x08\xEF\xBB\xBF"); // Trim UTF Byte order marks and whitespace.
        $this->savedBlocks = array();
        $this->lineProcessingMode = false;
        $this->textBeingProcessed = str_replace("\r\n", "\n", $wikiText);
        foreach($this->rules as $rule)
        {
            //echo '<pre style="color:blue;">'.htmlentities(print_r($rule,true)).'</pre>';
            switch($rule['type'])
            {
                case actionType_remove:
                    $this->findAndSaveBlocks($rule['rx'], $rule['label']);
                    break;
                case actionType_replace:
                    if ($this->lineProcessingMode)
                        $this->linesBeingProcessed = preg_replace($rule['rx'], $rule['replace'], $this->linesBeingProcessed);
                    else
                        $this->textBeingProcessed = preg_replace($rule['rx'], $rule['replace'], $this->textBeingProcessed);
                    break;
                case actionType_repeatreplace:
                    $count = 1;
                    while($count)
                    {
                        if ($this->lineProcessingMode)
                            $this->linesBeingProcessed = preg_replace($rule['rx'], $rule['replace'], $this->linesBeingProcessed, -1, $count);
                        else
                            $this->textBeingProcessed = preg_replace($rule['rx'], $rule['replace'], $this->textBeingProcessed, -1, $count);
                    }
                    break;
                case actionType_calldelegate:
                    if (isset($this->customProcessing[$rule['label']]))
                    {
                        if ($this->lineProcessingMode)
                            $this->linesBeingProcessed = preg_replace_callback($rule['rx'], $this->customProcessing[$rule['label']], $this->linesBeingProcessed);
                        else
                            $this->textBeingProcessed = preg_replace_callback($rule['rx'], $this->customProcessing[$rule['label']], $this->textBeingProcessed);
                        break;
                    }
                    break;
                case actionType_split:
                    $this->linesBeingProcessed = explode("\n", $this->textBeingProcessed);
                    $this->lineProcessingMode = true;
                    break;
                case actionType_joinBuildingBlocks:
                    $this->joinAndBuildBlocks();
                    break;
                case actionType_restore:
                    if (!$this->lineProcessingMode)
                        $this->restoreBlocks($rule['label']);
                    break;
            }
            //if ($this->lineProcessingMode)
            //    echo '<pre>'.htmlentities(implode('\n',$this->linesBeingProcessed)).'</pre><hr/>';
            //else
            //    echo '<pre>'.htmlentities($this->textBeingProcessed).'</pre><hr/>';
        }
        return $this->textBeingProcessed;
    }

    function findAndSaveBlocks($rx, $label)
    {
        while(preg_match($rx, $this->textBeingProcessed, $match, PREG_OFFSET_CAPTURE))
        {
            $replace = $this->saveBlock($match[0][0], $label);
            $this->textBeingProcessed = substr_replace($this->textBeingProcessed, $replace, $match[0][1], strlen($match[0][0]));
            //exit('<pre>'.print_r($match, true).'</pre>');
        }
    }

    function saveBlock($block, $blockTypeName)
    {
        $sz = sizeof($this->savedBlocks);
        $this->savedBlocks[] = $block;
        return "[--" . $blockTypeName . "_" . $sz . "--]";
    }

    function joinAndBuildBlocks()
    {
        if ($this->lineProcessingMode)
        {
            //Process blocks
            $this->textBeingProcessed = "";
            $lastBlock = "";
            $blockType = "";
            $blockExtras = "";
            $listtypes = "";

            // Ideally this should be created from a list of block rules, so that the code is easier to customize
            $blkRegex = '/^<:(\w+)\s*([^>]*)>(.*)/';

            for ($n = 0; $n < sizeof($this->linesBeingProcessed); $n++)
            {
                if(preg_match($blkRegex, $this->linesBeingProcessed[$n], $blkmatch))
                {
                    $this->linesBeingProcessed[$n] = $blkmatch[3];
                    $blockType = $blkmatch[1];
                    $blockExtras = $blkmatch[2];
                }
                else
                {
                    if (strlen($this->linesBeingProcessed[$n]) > 0)
                        $blockType = "p";
                    else
                        $blockType = "";
                    $blockExtras = "";
                }
                if ($lastBlock != $blockType)
                {
                    if (($lastBlock == "p") || ($lastBlock == "pre") || ($lastBlock == "table"))
                        $this->textBeingProcessed .= "</" . $lastBlock . ">";
                }
                // First process lists
                if (($blockType =="ul") || ($blockType =="ol"))
                {
                    $cdepth = strlen($blkmatch[2]);
                    $lt = substr($blkmatch[2], 0, 1);
                    $prevdepth = strlen($listtypes);
                    $this->linesBeingProcessed[$n] = "<li>" . $this->linesBeingProcessed[$n] . "</li>";
                    if (($prevdepth != $cdepth) || ($blockType != $lastBlock))
                    {
                        $wdepth = $prevdepth;
                        while (($wdepth > 0) && (!$this->getLT($listtypes, $wdepth) == $lt) && ($wdepth >= $cdepth))
                        {
                            $wtype = $this->getLT($listtypes, $wdepth);
                            if ($wtype == "*")
                                $this->textBeingProcessed .= "\n</ul>";
                            else
                                $this->textBeingProcessed .= "\n</ol>";
                            $wdepth--;
                        }
                        $listtypes = substr($listtypes, 0, $wdepth);
                        while ($wdepth < $cdepth)
                        {
                            if ($lt == "*")
                                $this->textBeingProcessed .= "<ul>";
                            else
                                $this->textBeingProcessed .= "<ol>";
                            $listtypes .= $lt;
                            $wdepth++;
                        }
                    }
                }
                else
                {
                    $wdepth = strlen($listtypes);
                    while ($wdepth > 0)
                    {
                        $wtype = $this->getLT($listtypes, $wdepth);
                        if ($wtype == "*")
                            $this->textBeingProcessed .= "\n</ul>";
                        else
                            $this->textBeingProcessed .= "\n</ol>";
                        $wdepth--;
                    }
                    $listtypes = "";
                }
                if ($lastBlock != $blockType)
                {
                    if (($blockType =="p") || ($blockType == "pre") || ($blockType == "table"))
                        $this->textBeingProcessed .= "<" . $blockType . " " . $blockExtras . ">";
                }
                switch ($blockType)
                {
                    case "ul":
                    case "ol":
                    case "":
                    case "p":
                    case "pre":
                    case "table":
                        break;
                    default:
                        $this->linesBeingProcessed[$n] = "<" . $blockType . " " . $blockExtras . ">" . $this->linesBeingProcessed[$n] . "</" . $blockType . ">";
                        break;
                }
                $lastBlock = $blockType;
                $this->textBeingProcessed .= $this->linesBeingProcessed[$n] . "\n";

            }
            $this->lineProcessingMode = false;
        }
    }

    function getLT($liststr, $depth)
    {
        if (strlen($liststr) >= $depth)
            return substr($liststr, $depth - 1, 1);
        else
            return " ";
    }


    function restoreBlocks($blockTypeName)
    {
        if ($blockTypeName != null)
            $blockReturn = '/\[--' . $blockTypeName . '_(\d+)--\]/ms';
        else
            $blockReturn = '/\[--\w+?_(\d+)--\]/ms';
        while (preg_match($blockReturn, $this->textBeingProcessed, $m))
        {
            $savedIdx = intval($m[1]);
            $this->textBeingProcessed = str_replace($m[0], $this->savedBlocks[$savedIdx], $this->textBeingProcessed);
        }
    }

    function sort_regex($rx)
    {
        return '/'.str_replace('/', '\\/', str_replace('""', '"', $rx)).'/ms';
    }
}

function imageProcess($m)
{
    $alt = isset($m['alttext']) ? " alt='{$m['alttext']}'" : '';
    return "<img src='{$m['filename']}'{$alt}/>";
}

function wikiLinkProcess($m)
{
    if(isset($m['label']))
        $label = $m['label'];
    else
        $label = $m['linkto'];
    $link = preg_replace('/\s+/', '', ucwords($m['linkto']));
    return "<a href='?n={$link}'>$label</a>";
}
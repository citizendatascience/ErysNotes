<?php
require_once('lib/md2html.php');
require_once('lib/geshi.php');
require_once('lib/callNoteEval.php');

/**
 * ipynb_reader short summary.
 *
 * ipynb_reader description.
 *
 * @version 1.0
 * @author nsb2x
 */
class ipynb_reader
{
    function __construct($path)
    {
        $this->data = json_decode(file_get_contents($path));
    }

    function render_preview($run = true)
    {
        $mdConv = new md2html();
        $output = '<div class="notebook">';
        foreach($this->data->cells as $cell)
        {
            switch($cell->cell_type)
            {
                case 'markdown':
                    $mdsource = implode("", $cell->source);
                    $output .= "<div class='notes'>".$mdConv->Convert($mdsource)."</div>";
                    break;
                case 'code':
                    $source = implode("", $cell->source);
                    $geshi = new GeSHi($source, 'python'); 
                    $output .= "<div class='code'>".$geshi->parse_code()."</div>";
                    if($run && (strlen(trim($source))))
                    {
                        $output .= "<div class='notes'><pre style='color:red;'>".evaluateNote($source)."</pre></div>";
                    }
                    elseif(sizeof($cell->outputs))
                    {
                        foreach($cell->outputs as $op)
                        {
                            $output .= $this->displayOutput($op);
                        }
                    }
                    break;
                default:
                    $output .= "<div style='color:green;'>I don't know how to render '{$cell->cell_type}' cells yet.</div>";
            }
        }
        return $output.'</div>';
    }

    function displayOutput($op)
    {
        $output = '<div class="output">';
        switch($op->output_type)
        {
            case 'execute_result':
                $texthtml = 'text/html';
                if(isset($op->data->$texthtml))
                {
                    $output .=  implode("",$op->data->$texthtml);
                }
                break;
            case 'stream':
                $output .=  '<pre>'.implode("",$op->text).'</pre>';
                break;
            case 'display_data':
                $imagepng = 'image/png';
                if(isset($op->data->$imagepng))
                {
                    $output .=  '<img src="data:image/png;base64,' . $op->data->$imagepng .'"/>';
                }
                break;
            default:
                $output .= '<div style="color:red;"><pre>'.htmlentities(print_r($op, true)).'</pre></div>';
                break;
        }
        return $output.'</div>';
    }
}
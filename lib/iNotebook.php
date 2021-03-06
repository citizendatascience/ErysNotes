<?php
// Classes to support ipynb style interactive notebooks.
// (Missing Py from the name as I expect this library to also support non-Python notebooks eventually.)
require_once(__DIR__.'/geshi.php');

class iNotebook
{
    var $cells; // Erysnotes extended format will have nested cells, ipynb just has root cells.
    var $metadata; // For now just the JSON data from an ipynb file

    function __construct($json_source = false)
    {
        $this->cells = array();

        if($json_source)
        {
            $data = json_decode($json_source);
            if(isset($data->metadata))
                $this->metadata = $data->metadata;
            foreach($data->cells as $cell)
            {
                switch($cell->cell_type)
                {
                    case 'markdown':
                        $this->cells[] = new iNotebook_markdown($cell);
                        break;
                    case 'code':
                        $this->cells[] = new iNotebook_pycode($cell);
                        break;
                    default:
                        exit("<div style='color:green;'>I don't know how to read '{$cell->cell_type}' cells yet.</div>");
                }
            }
        }
    }

    // This makes the JSON identical to an iPython 4.2 notebook - useful for testing, but it will need to evolve
    function toJSON()
    {
        $prep = new stdClass();
        $prep->cells = array();
        foreach($this->cells as $cell)
            $prep->cells[] = $cell->dataForJson();
        $prep->metadata = $this->metadata;
        $prep->nbformat = 4;
        $prep->nbformat_minor = 2;
        return json_encode($prep, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    function toErysJson()
    {
        $prep = new stdClass();
        $prep->children = array();
        foreach($this->cells as $cell)
        {
            $prep->children[] = $cell->dataForErysJson();
        }
        $prep->metadata = $this->metadata;
        $prep->nbformat = 4;
        $prep->nbformat_minor = 2;
        return json_encode($prep, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    static function fromErysJson($json)
    {
        $nb = new iNotebook();
        $data = json_decode($json);
        foreach($data->children as $cellid=>$cell)
        {
            switch($cell->contentType)
            {
                case 'nb_markdown':
                    $tmpCell = new iNotebook_markdown((object)array('cell_type' => 'markdown'));
                    $tmpCell->source = $cell->source;
                    $nb->cells[] = $tmpCell;
                    break;

                case 'pythonCode':
                    $tmpCell = new iNotebook_pycode((object)array('cell_type' => 'code'));
                    $tmpCell->source = $cell->source;

                    $output_count = preg_match_all('%<div class\s*=\s*["\']outputPart\s+(?<type>\w+)["\']\s*>(?<content>.*?)<!--end-\k<type>-->\s*</div>%sm', $cell->content, $matches);

                    //$output_count = preg_match_all('%<pre[^>]*>(?<content>.*?)</pre>|src\s*=\s*[\'"]data:image/png;base64,\s*(?<pngimage>[a-zA-Z0-9+/]+=*)%sim', $cell->content, $matches);
                    $tmpCell->outputs = array();
                    //file_put_contents("C:\\Users\\niall_000\\Desktop\\tmp\\{$cellid}.txt", $cell->content."\r\n\r\n".print_r( $matches, true));
                    $tmpCell->execution_count = intval($cell->execution_count);
                    for($n=0; $n<$output_count; $n++)
                    {
                        $part_count = preg_match_all('%<pre[^>]*>(?<content>.*?)</pre>|src\s*=\s*[\'"]data:image/png;base64,\s*(?<pngimage>[a-zA-Z0-9+/]+=*)%sim', $matches['content'][$n], $matches2);
                        $tmpOutput = new iNotebook_pyoutput();

                        switch($matches['type'][$n])
                        {
                            case 'stream':
                                $tmpOutput->text = $matches2['content'][0];
                                $tmpOutput->output_type = "stream";
                                $tmpOutput->stream_name = "stdout";
                                //exit("I need to deal with saving stream content. Line ".__LINE__." in ".__FILE__);
                                break;
                            case 'display_data':
                                $tmpOutput->output_type = "display_data";
                                for($n2=0; $n2<$part_count; $n2++)
                                {
                                    if(strlen($matches2['pngimage'][$n2]))
                                    {
                                        $tmpOutput->png = $matches2['pngimage'][$n2];
                                    }
                                    else
                                    {
                                        $tmpOutput->text = $matches2['content'][$n2];
                                    }
                                }
                             //   echo '<pre>'.htmlentities(print_r($matches2, true)).'</pre>';
                             //   exit("I need to deal with saving display_data content. Line ".__LINE__." in ".__FILE__);
                                break;
                            default:
                                //exit("I need to deal with saving {$matches['type'][$n]} content.");
                                break;
                        }

                        /*$tmpOutput->text = $matches['content'][$n];

                        for($n2=0; $n2<$part_count; $n2++)
                        {
                            if(strlen($matches2['pngimage'][$n]))
                            {
                                $tmpOutput->png = $matches2['pngimage'][$n];
                            }
                            else
                            {
                                $tmpOutput->output_type = "stream";
                            }
                        }//*/
                        if((strlen($tmpOutput->text))||(strlen($tmpOutput->png)))
                            $tmpCell->outputs[] = $tmpOutput;
                    }
                    $nb->cells[] = $tmpCell;
                    break;
            }
        }
        return $nb;
    }

    function checkForImages()
    {
        $images = array();
        foreach($this->cells as $cell)
        {
            if('markdown' == $cell->cell_type)
            {
                preg_match_all('/!\[[^\]]*\]\((?<url>\S+)(\s+"[^"]+")?\)/', $cell->source, $matches);
                foreach($matches['url'] as $url)
                {
                    $images[] = $url;
                }
            }
        }
        return $images;
    }


    function render_preview()
    {
        global $CFG;
        $mdConv = new md2html();
        $imageBaseURL = $CFG['imgroot'].'/'.$_SESSION['projectID'].'/';
        $mdConv->variables['imgdir'] = $imageBaseURL;
        $output = '<div class="notebook">';
        foreach($this->cells as $cell)
        {
            $output .= $cell->render_preview($mdConv);
        }
        return $output.'</div>';
    }

    function updateToErys()
    {
        $output = '<div class="notebook">';
        $codeStarted = false;
        $plibname = "matplotlib\\.pyplot";
        $n=1;
        foreach($this->cells as $cell)
        {
            if(is_a($cell, 'iNotebook_pycode'))
            {
                if(!$codeStarted)
                {
                    $cell->source = "import matplotlib\nmatplotlib.use('Agg')\n".$cell->source;
                    $codeStarted = true;
                }
                if(preg_match('/import\s+matplotlib.pyplot(\s+as\s*(\w+))?/sm', $cell->source, $matches))
                {
                    $plibname = $matches[2];
                }
                $rex = '/^(\s*)'.$plibname.'\.show\s*\(\s*\)/sm';
                while(preg_match($rex, $cell->source, $matches))
                {
                    $cell->source = preg_replace($rex, "{$matches[1]}{$plibname}.savefig('out{$n}.png', dpi=150)\n{$matches[1]}plt.clf()", $cell->source, 1);
                    $n++;
                }
                $output .= $cell->render_preview(null);
            }
        }
        return $output.'</div>';
    }
}

abstract class iNotebook_cell
{
    var $cell_type;
    var $metadata;
    var $source;

    function __construct($data)
    {
        $this->cell_type = $data->cell_type;
        if(isset($data->metadata))
            $this->metadata = $data->metadata;
        else
            $this->metadata = new stdClass();
        if(isset($data->source))
        {
            if(is_array($data->source))
                $this->source = implode('', $data->source);
            else
                $this->source = $data->source;
        }
        else
        {
            $this->source = '';
        }
    }

    function render_preview($mdConv)
    {
        return "<div style='color:green;'>I don't know how to render this cell yet.<pre>{$this->source}</pre></div>";
    }

    function dataForJson()
    {
        $data = new stdClass();
        $data->cell_type = $this->cell_type;
        $data->metadata = $this->metadata;
        $data->source = explode("//remove\n", str_replace("\n", "\n//remove\n", $this->source));
        if(strlen($data->source[sizeof($data->source)-1]) == 0)
            array_splice($data->source, sizeof($data->source)-1);
        return $data;
    }

    function dataForErysJson()
    {
        $data = new stdClass();
        $data->contentType = $this->cell_type;
        $data->source = $this->source;
        return $data;
    }
}

class iNotebook_markdown extends iNotebook_cell
{
    function __construct($data = null)
    {
        parent::__construct($data);
    }

    function render_preview($mdConv)
    {
        //$mdsource = implode("", $this->source);
        return "<div class='notes'>".$mdConv->Convert($this->source)."</div>";
    }

    function dataForJson()
    {
        $data = parent:: dataForJson();
        return $data;
    }

    function dataForErysJson()
    {
        global $CFG;
        $mdConv = new md2html();
        $imageBaseURL = $CFG['imgroot'].'/'.$_SESSION['projectID'].'/';
        $mdConv->variables['imgdir'] = $imageBaseURL;
        $data = parent:: dataForErysJson();
        $data->contentType = "nb_markdown";
        $data->content = "<div class='notes'>".$mdConv->Convert($this->source)."</div>";
        return $data;
    }

}

class iNotebook_pycode extends iNotebook_cell
{
    var $execution_count;
    var $outputs;
    var $collapsed; // from metadata, output collapsed true|false
    var $autoscroll; // from metadata, true|false|'auto'

    function __construct($data = null)
    {
        parent::__construct($data);
        $this->collapsed = true; // default value
        $this->autoscroll = false; // default value
        $this->execution_count = isset($data->execution_count) ? $data->execution_count : 0;
        $this->outputs = array();
        if((isset($data->outputs))&&(sizeof($data->outputs)))
        {
            foreach($data->outputs as $op)
            {
                 $this->outputs[] = new iNotebook_pyoutput($op);
            }
        }
    }

    function render_preview($mdConv)
    {
        $geshi = new GeSHi($this->source, 'python');
        $output = "<div class='code'>".$geshi->parse_code()."</div>";
        if(sizeof($this->outputs))
        {
            foreach($this->outputs as $op)
            {
               // $output .= $this->displayOutput($op);
                $output .= '<div class="output">'.$op->render().'</div>';
            }
        }
        return $output;
    }

    function dataForJson()
    {
        $data = new stdClass();
        $data->cell_type = $this->cell_type;
        $data->execution_count = $this->execution_count;
        $data->metadata = $this->metadata;
        $data->outputs = array();
        foreach($this->outputs as $op)
        {
            $data->outputs[] = $op->dataForJson();
        }
        $data->source = explode("//remove\n", str_replace("\n", "\n//remove\n", $this->source));
        if(strlen($data->source[sizeof($data->source)-1]) == 0)
            array_splice($data->source, sizeof($data->source)-1);
        return $data;
    }

    function dataForErysJson()
    {
        $data = new stdClass();
        $data->contentType = "pythonCode";
        $data->execution_count = $this->execution_count;
        $data->outputs = array();
        $data->content = '';
        foreach($this->outputs as $op)
        {
           //# $data->outputs[] = $op->dataForJson();
            $data->content .= $op->render();
        }
        $data->source = $this->source;
        return $data;
    }

    /*
    function displayOutput($op)
    {
        $output = '<div class="output">';
        switch($op->output_type)
        {
            case 'execute_result':
                $texthtml = 'text/html';
                if(isset($op->data->$texthtml))
                {
                    $output .=  $op->data->$texthtml;
                }
                break;
            case 'stream':
                $output .=  '<pre>'.$op->text.'</pre>';
                break;
            case 'display_data':
                $imagepng = 'image/png';
                if(isset($op->data->$imagepng))
                {
                    $output .=  '<img src="data:image/png;base64, ' . $op->data->$imagepng .'"/>';
                }
                break;
            default:
                $output .= '<div style="color:red;"><pre>'.htmlentities(print_r($op, true)).'</pre></div>';
                break;
        }
        return $output.'</div>';
    }
    */

}

class iNotebook_pyoutput
{
    var $metadata;
    var $output_type; //'stream' | 'display_data' | 'execute_result'

    // All the following are false if not used or not needed for the output_type
    var $execution_count; // only used for execute_result
    var $stream_name; // 'stdout' | 'stderror';
    var $text; // text or data->['text/plain']
    var $png;
    var $data;

    function __construct($data=false)
    {
        if($data)
        {
            if(isset($data->metadata))
                $this->metadata = $data->metadata;
            $this->output_type = $data->output_type;
            if($this->output_type == 'stream')
            {
                $this->stream_name = $data->name;
                $this->text = implode('', $data->text);
            }
            else
            {
                // the ipynb notebook data names (mimetypes) are not allowed in PHP, so use these vars.
                $textplain = 'text/plain';
                $imgpng = 'image/png';
                $json = 'application/json';

                $this->stream_name = false;
                $this->text = isset($data->data->$textplain) ? implode('', $data->data->$textplain) : false;
                $this->png = isset($data->data->$imgpng) ? $data->data->$imgpng : false;
                $this->data = isset($data->data->$json) ? $data->data->$json : false;
            }
        }
        else
        {
            $this->metadata = new stdClass();
            $this->output_type = false;
            $this->execution_count = false; // only used for execute_result
            $this->stream_name = false; // 'stdout' | 'stderror';
            $this->text = false; // text or data->['text/plain']
            $this->png = false;
            $this->data = false;
        }
    }

    function render()
    {
        $out = "<div class=\"outputPart {$this->output_type}\">";
        if($this->text)
            $out .= '<pre>'.$this->text.'</pre>';
        if($this->png)
            $out .= "<div class='image'><img src='data:image/png;base64, {$this->png}'/></div>";
        if($this->data)
            $out .= '<pre>'.$this->data.'</pre>'; //# should be pretty printed I think.
        $out .= "<!--end-{$this->output_type}--></div>";
        return $out;
    }

    function dataForJson()
    {
        $data = new stdClass();
        if($this->stream_name)
        {
            $data->name = $this->stream_name;
            $data->output_type = $this->output_type;
            $data->text = explode("//remove\n", str_replace("\n", "\n//remove\n", $this->text));
            if(strlen($data->text[sizeof($data->text)-1]) == 0)
                array_splice($data->text, sizeof($data->text)-1);
        }
        else
        {
            $data->data = array();
            if($this->png)
                $data->data['image/png'] = $this->png;
            if($this->text)
            {
                $data->data['text/plain'] = explode("//remove\n", str_replace("\n", "\n//remove\n", $this->text));
                if(strlen($data->data['text/plain'][sizeof($data->data['text/plain'])-1]) == 0)
                    array_splice($data->data['text/plain'], sizeof($data->data['text/plain'])-1);
            }
            $data->metadata = $this->metadata;
            $data->output_type = $this->output_type;
        }
        return $data;
    }

    //# No longer used? Replaced by render for sending ErysJson notebook
    function dataForErysJson()
    {
        $data = new stdClass();
        if($this->stream_name)
        {
            $data->name = $this->stream_name;
            $data->output_type = $this->output_type;
            $data->text = $this->text;
        }
        else
        {
            $data->data = array();
            if($this->png)
                $data->data['image_png'] = $this->png;
            if($this->text)
            {
                $data->data['text_plain'] = $this->text;
            }
            $data->output_type = $this->output_type;
        }
        return $data;
    }
}
<?php

function evaluateNote($source)
{
    $source2 = preProcessSource($source, $pics);
    $postdata = http_build_query(
        array(
            'resetpickle' => '0',
            'code' => $source2,
            'picklefile' => 'test.pickle',
            'workingdir' => 'data'
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    //return($source2);
    $context  = stream_context_create($opts);

    $result = file_get_contents('http://localhost:8080/', false, $context);
    return $result;
}

// currently this does one note at a time - need to do full file
function preProcessSource($source, &$pics)
{
    $pics = array();
    if(preg_match('/import\s+matplotlib.pyplot(\s+as\s*(\w+))?/sm', $source, $matches))
    {
        $plibname = $matches[2];
    }
    else
    {
        $plibname = "matplotlib\\.pyplot";
    }

    $rex = '/^(\s*)'.$plibname.'\.show\s*\(\s*\)/sm';

    $n=1;
    while(preg_match($rex, $source, $matches))
    {
        $source = preg_replace($rex, "{$matches[1]}{$plibname}.savefig('out{$n}.png', dpi=150)\n{$matches[1]}plt.clf()", $source, 1);
        $pics[] = "out{$n}.png";
        $n++;
    }
    //echo "{$plibname} and $n replacements<br/>";
    return $source;
}


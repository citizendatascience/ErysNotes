<?php

$data = json_decode(file_get_contents("summer_school_test/intro.ipynb"));

//echo '<pre>'.print_r($data, true).'</pre>';

$fullsource = "import matplotlib\nmatplotlib.use('Agg')\n\n"; // This is needed to allow ploting to file

foreach($data->cells as $cell)
{
    if($cell->cell_type == "code")
    {
        //echo "<h2>{$cell->cell_type}</h2>";
        //echo '<pre>'.print_r($cell, true).'</pre>';
        $source = implode("\n", $cell->source);
        //echo '<pre>'.$source.'</pre>';
        $fullsource .= $source."\n\n";
    }

}
$fullsource = preProcessSource($fullsource);

echo '<pre>'.$fullsource.'</pre>';
//file_put_contents('nb.py', $fullsource);


// Change matplotlib.pyplot.plot
function preProcessSource($source)
{
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
        $n++;
    }
    echo "{$plibname} and $n replacements<br/>";
    return $source;
}




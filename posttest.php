<?php

$postdata = http_build_query(
    array(
        'resetpickle' => '0',
        'code' => 'from os import walk

f = []
mypath = "."
for (dirpath, dirnames, filenames) in walk(mypath):
    f.extend(filenames)
    break
for f2 in f:
    print(f2)',
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

$context  = stream_context_create($opts);

$result = file_get_contents('http://localhost:8080/', false, $context);
//$result = file_get_contents('http://localhost:8080/');

echo "<pre>$result</pre>";
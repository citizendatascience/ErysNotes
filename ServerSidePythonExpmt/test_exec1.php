<?php
$command = escapeshellcmd('python noteeval.py -p 1234567890abcfd.pickle -r nb.py');


$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("pipe", "w") // stderr is a pipe that the child will write to
);

$cwd = '/var/www/html/notebookview';
$env = array('DISPLAY'=>'localhost:0.0');

$process = proc_open($command, $descriptorspec, $pipes, $cwd);

if (is_resource($process)) {

    fclose($pipes[0]);

   echo "<p>Output: ".stream_get_contents($pipes[1]).'</p>';
   fclose($pipes[1]);

    echo "<p>Errors: ".stream_get_contents($pipes[2]).'</p>';
    fclose($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);

    echo "command returned $return_value\n";
}



#$output = shell_exec($command);
#echo $output;



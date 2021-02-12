<?php

function readLog()
{
    // $linecount = 0;
    // $handle = fopen(LOGPATH, "r");
    // while (!feof($handle)) {
    //     $line = fgets($handle, 4096);
    //     $linecount += substr_count($line, PHP_EOL);
    // }
    // fclose($handle);
    // $linhas = $linecount;

    // $f = fopen(LOGPATH, 'rb');
    $lines = 0;
    if ($f = fopen(LOGPATH, "r+")) {
        while (!feof($f)) {
            $lines += substr_count(fread($f, 8192), "\n");
        }
    }
    fclose($f);
    $linhas = $lines;

    $logfile = fopen(LOGPATH, "r") or die("Unable to open file!");
    $log = fread($logfile, filesize(LOGPATH));
    fclose($logfile);

    return array('log' => $log, 'linhas' => $linhas);
}

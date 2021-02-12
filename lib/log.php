<?php

function readLog()
{
    $linecount = 0;
    if ($handle = fopen(LOGPATH, "r")) {
        while (!feof($handle)) {
            $line = fgets($handle, 4096);
            $linecount += substr_count($line, PHP_EOL);
        }
        fclose($handle);
    }
    $linhas = $linecount;

    // $lines = 0;
    // if ($f = fopen(LOGPATH, "r")) {
    //     while (!feof($f)) {
    //         $lines = $lines + substr_count(fread($f, 8192), PHP_EOL);
    //     }
    // }
    // fclose($f);
    // $linhas = $lines;

    $logfile = fopen(LOGPATH, "r") or die("Unable to open file!");
    $log = fread($logfile, filesize(LOGPATH));
    fclose($logfile);

    return array('log' => $log, 'linhas' => $linhas);
}

<?php

function readLog()
{
    $linecount = 0;
    $handle = fopen(LOGPATH, "r");
    while (!feof($handle)) {
        $line = fgets($handle, 4096);
        $linecount = $linecount + substr_count($line, PHP_EOL);
    }
    fclose($handle);


    // $linecount = 0;
    // $handle = fopen(LOGPATH, "r");
    // while (!feof($handle)) {
    //     $line = fgets($handle);
    //     $linecount++;
    // }
    // fclose($handle);
    $linhas = $linecount;

    // Lê os logs
    $logfile = fopen(LOGPATH, "r") or die("Unable to open file!");
    $log = fread($logfile, filesize(LOGPATH));
    fclose($logfile);

    return array('log' => $log, 'linhas' => $linhas);
}

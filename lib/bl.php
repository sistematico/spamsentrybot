<?php

function blacklistAdd($file_db) {
    $group = '-1001189033145';
    $word = 'asgdfsfs';
    $sql = 'INSERT OR IGNORE INTO blacklist (group, word, level) values ((SELECT group FROM blacklist WHERE group = :group), :word, 1);';
    $file_db->prepare($sql);
    $file_db->execute([':group' => $group, ':word' => $word]);
    return $file_db->lastInsertId();
}
<?php

function blacklistAdd($database) {
    $group = '-1001189033145';
    //$group = preg_replace( '/[^0-9]/', '', $group );
    $group = preg_replace("/\D+/", "", $group);
    $word = 'asgdfsfs';
    // $sql = 'INSERT OR IGNORE INTO blacklist (group, word, level) values ((SELECT group FROM blacklist WHERE group = :group), :word, 1);';
    $sql = 'INSERT INTO blacklist (group, word, level) values (:group, :word, 1);';
    $stmt = $database->prepare($sql);
    $stmt->execute([':group' => $group, ':word' => $word]);
    return $stmt->lastInsertId();
}
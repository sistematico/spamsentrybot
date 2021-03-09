<?php

try {
    $file_db = new \PDO("sqlite:" . DATABASE);
 } catch (\PDOException $e) {
    // handle the exception here
 }
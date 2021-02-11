<?php

try {
    $file_db = new \PDO("sqlite:../db/banco.db");
 } catch (\PDOException $e) {
    // handle the exception here
 }
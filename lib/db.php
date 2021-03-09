<?php

try {
    $database = new PDO("sqlite:" . DATABASE);
 } catch (PDOException $e) {
    $database = null;
 }
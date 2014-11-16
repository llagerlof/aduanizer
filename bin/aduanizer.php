<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $cli = new Aduanizer\Cli;
    $cli->run();
} catch (\Aduanizer\Exception $e) {
    echo $e->getMessage(), "\n";
    echo $e->getTraceAsString(), "\n";
    exit(1);
}

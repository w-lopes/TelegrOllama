<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';

use App\Config;
use App\Database;

// Load configuration
Config::load(__DIR__ . '/../.env');

try {
    $dbPath = __DIR__ . '/../data/app.sqlite';
    
    // Ensure directory exists and is writable
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    chmod($dir, 0777);

    $db = new Database($dbPath);
    // Ensure the database file is writable by the web server
    chmod($dbPath, 0666);
    echo "Database initialized successfully at $dbPath\n";
} catch (\Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}

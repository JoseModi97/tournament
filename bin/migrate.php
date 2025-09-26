#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Support\Migration;

if (!is_dir(__DIR__ . '/../storage')) {
    mkdir(__DIR__ . '/../storage', 0777, true);
}

touch(__DIR__ . '/../storage/database.sqlite');

try {
    Migration::run();
    echo "Database migrated\n";
} catch (RuntimeException $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}

#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Support\Migration;

if (!is_dir(__DIR__ . '/../storage')) {
    mkdir(__DIR__ . '/../storage', 0777, true);
}

touch(__DIR__ . '/../storage/database.sqlite');

Migration::run();

echo "Database migrated\n";

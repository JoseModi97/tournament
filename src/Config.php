<?php

namespace App;

class Config
{
    public const DB_PATH = __DIR__ . '/../storage/database.sqlite';
    public const TOKEN_TTL_MINUTES = 60 * 24; // 24 hours
}

<?php

namespace App\Core;

class Request
{
    private array $body;

    public function __construct()
    {
        $input = file_get_contents('php://input');
        $this->body = $input ? (json_decode($input, true) ?? []) : [];
    }

    public function json(): array
    {
        return $this->body;
    }

    public function getHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}

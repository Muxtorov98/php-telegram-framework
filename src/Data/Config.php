<?php
namespace App\Data;

use Dotenv\Dotenv;

class Config
{
    private static array $env = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (empty(self::$env)) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            self::$env = $_ENV;
        }

        return self::$env[$key] ?? $default;
    }

    public static function getChatIds(): array
    {
        $ids = self::get('CHAT_IDS', '');
        return array_filter(array_map('trim', explode(',', $ids)));
    }

    public static function getAdminIds(): array
    {
        return array_filter(array_map('trim', explode(',', self::get('ADMIN_IDS', ''))));
    }

    public static function getManagerIds(): array
    {
        return array_filter(array_map('trim', explode(',', self::get('MANAGER_IDS', ''))));
    }
}

<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;

class ThrottleMiddleware implements MiddlewareInterface
{
    private static array $lastMessage = [];

    public function process(array $update, callable $next): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        if (!$chatId) {
            return;
        }

        $now = microtime(true);
        $last = self::$lastMessage[$chatId] ?? 0;

        // 1 soniyada 1 marta yuborish cheklovi
        if ($now - $last < 1) {
            echo "⚠️ Throttled user $chatId\n";
            return;
        }

        self::$lastMessage[$chatId] = $now;
        $next($update);
    }
}

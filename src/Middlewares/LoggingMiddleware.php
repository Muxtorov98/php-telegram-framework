<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;
use App\Data\Config;

class LoggingMiddleware implements MiddlewareInterface
{
    public function process(array $update, callable $next): void
    {
        $logFile = Config::get('LOG_PATH', '/tmp/telegram-bot.log');
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), json_encode($update));

        file_put_contents($logFile, $line, FILE_APPEND);
        $next($update);
    }
}

<?php
namespace App\Services;

use App\Data\Config;

class LoggerService
{
    private bool $debug;
    private string $logFile;

    public function __construct()
    {
        $this->debug = filter_var(Config::get('APP_DEBUG', false), FILTER_VALIDATE_BOOL);

        $logDir = 'storage/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/bot.log';
    }

    public function info(string $message): void
    {
        $this->write('ℹ️ INFO', $message, "\033[34m");
    }

    public function success(string $message): void
    {
        $this->write('✅ SUCCESS', $message, "\033[32m");
    }

    public function warning(string $message): void
    {
        $this->write('⚠️ WARNING', $message, "\033[33m");
    }

    public function error(string $message): void
    {
        $this->write('❌ ERROR', $message, "\033[31m");
    }

    public function debug(string $message): void
    {
        if ($this->debug) {
            $this->write('🪲 DEBUG', $message, "\033[35m");
        }
    }

    private function write(string $level, string $message, string $color = "\033[0m"): void
    {
        $date = date('Y-m-d H:i:s');
        $formatted = "[$date] $level: $message";

        // 🖥️ Terminal chiqishi (rangli)
        echo "{$color}{$formatted}\033[0m\n";

        // 📁 Faylga yozish
        file_put_contents($this->logFile, $formatted . PHP_EOL, FILE_APPEND);
    }
}

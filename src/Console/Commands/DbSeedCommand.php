<?php

namespace App\Console\Commands;

class DbSeedCommand
{
    public function handle(array $args): void
    {
        $dir = __DIR__ . '/../../../database/seeders';
        if (!is_dir($dir)) {
            echo "❌ Seeder papkasi topilmadi.\n";
            return;
        }

        foreach (glob("{$dir}/*.php") as $file) {
            include $file;
            echo "🌱 Seeder ishga tushdi: " . basename($file) . "\n";
        }
    }
}

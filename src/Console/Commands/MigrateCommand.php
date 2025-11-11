<?php
namespace App\Console\Commands;

use Illuminate\Database\Capsule\Manager as Capsule;
use Carbon\Carbon; // ✅ Qo‘shamiz


/**
 * docker compose exec telegram-bot php bin/console migrate
 * docker compose exec telegram-bot php bin/console migrate down
 * docker compose exec telegram-bot php bin/console migrate refresh
 */
class MigrateCommand
{
    public function handle(array $args): void
    {
        $dir = __DIR__ . '/../../../database/migrations';
        if (!is_dir($dir)) {
            echo "❌ Migrations papkasi topilmadi.\n";
            return;
        }

        $action = $args[0] ?? 'up';
        $files = glob("{$dir}/*.php");
        sort($files);

        // Jadval mavjudligini tekshiramiz (migrations)
        if (!Capsule::schema()->hasTable('migrations')) {
            echo "ℹ️ 'migrations' jadvali topilmadi. Yaratilmoqda...\n";
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration', 255)->unique();
                $table->integer('batch')->default(1);
                $table->timestamp('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
            });
        }

        match ($action) {
            'refresh' => $this->refresh($files),
            'down' => $this->down($files),
            default => $this->up($files),
        };
    }

    private function up(array $files): void
    {
        $lastBatch = Capsule::table('migrations')->max('batch') ?? 0;
        $batch = $lastBatch + 1;

        foreach ($files as $file) {
            $filename = basename($file);

            if (Capsule::table('migrations')->where('migration', $filename)->exists()) {
                echo "⏭️ O‘tkazildi (allaqachon bajarilgan): {$filename}\n";
                continue;
            }

            $migration = include $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
                Capsule::table('migrations')->insert([
                    'migration' => $filename,
                    'batch' => $batch,
                    'created_at' => Carbon::now(), // ✅ To‘g‘rilangan
                ]);
                echo "✅ Migration bajarildi: {$filename}\n";
            }
        }
    }

    private function down(array $files): void
    {
        $lastBatch = Capsule::table('migrations')->max('batch');
        if (!$lastBatch) {
            echo "⚠️ Hech qanday migration topilmadi.\n";
            return;
        }

        $migrated = Capsule::table('migrations')->where('batch', $lastBatch)->pluck('migration')->toArray();

        foreach (array_reverse($files) as $file) {
            $filename = basename($file);

            if (!in_array($filename, $migrated, true)) {
                continue;
            }

            $migration = include $file;
            if (is_object($migration) && method_exists($migration, 'down')) {
                $migration->down();
                Capsule::table('migrations')->where('migration', $filename)->delete();
                echo "🗑️  Orqaga qaytarildi: {$filename}\n";
            }
        }
    }

    private function refresh(array $files): void
    {
        echo "🔄 Refreshing all migrations...\n";
        $this->downAll();
        $this->up($files);
        echo "✅ Refresh tugallandi!\n";
    }

    private function downAll(): void
    {
        $migrated = Capsule::table('migrations')->pluck('migration')->toArray();
        $dir = __DIR__ . '/../../../database/migrations';
        $files = glob("{$dir}/*.php");

        foreach (array_reverse($files) as $file) {
            $filename = basename($file);
            if (!in_array($filename, $migrated, true)) continue;

            $migration = include $file;
            if (is_object($migration) && method_exists($migration, 'down')) {
                $migration->down();
                Capsule::table('migrations')->where('migration', $filename)->delete();
                echo "🧹 Tozalandi: {$filename}\n";
            }
        }
    }
}
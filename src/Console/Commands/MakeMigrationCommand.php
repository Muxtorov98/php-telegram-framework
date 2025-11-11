<?php
namespace App\Console\Commands;

class MakeMigrationCommand
{
    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "❌ Migration nomini kiriting: php bin/console make:migration create_users_table\n";
            return;
        }

        $dir = __DIR__ . '/../../../database/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $timestamp = date('Y_m_d_His');
        $file = "{$dir}/{$timestamp}_{$name}.php";

        // Agar shunday nomdagi migration mavjud bo‘lsa — ogohlantiramiz
        $existing = glob("{$dir}/*_{$name}.php");
        if (!empty($existing)) {
            echo "⚠️ Migration allaqachon mavjud: " . basename($existing[0]) . "\n";
            return;
        }

        // Jadval nomini avtomatik aniqlaymiz
        $tableName = $this->extractTableName($name);

        // nowdoc format (<<<'PHP') — PHP kodni toza saqlaydi
        $content = <<<'PHP'
<?php
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up() {
        if (!Capsule::schema()->hasTable('TABLE_NAME')) {
            Capsule::schema()->create('TABLE_NAME', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
            echo "✅ Jadval yaratildi: TABLE_NAME\n";
        } else {
            echo "⚠️ Jadval allaqachon mavjud: TABLE_NAME\n";
        }
    }

    public function down() {
        if (Capsule::schema()->hasTable('TABLE_NAME')) {
            Capsule::schema()->drop('TABLE_NAME');
            echo "🗑️ Jadval o‘chirildi: TABLE_NAME\n";
        } else {
            echo "⚠️ Jadval mavjud emas: TABLE_NAME\n";
        }
    }
};
PHP;

        // Jadval nomini haqiqiy nom bilan almashtiramiz
        $content = str_replace('TABLE_NAME', $tableName, $content);

        file_put_contents($file, $content);

        echo "✅ Migration yaratildi: database/migrations/{$timestamp}_{$name}.php\n";
    }

    /**
     * create_users_table => users
     * add_products_table => products
     * users => users
     */
    private function extractTableName(string $migrationName): string
    {
        if (preg_match('/create_(.*?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        if (preg_match('/add_(.*?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        return $migrationName;
    }
}

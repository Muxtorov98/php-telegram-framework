<?php
namespace App\Console\Commands;

class MakeModelCommand
{
    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "❌ Model nomini kiriting: php bin/console make:model Role\n";
            return;
        }

        $dir = __DIR__ . '/../../../src/Models';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = "{$dir}/{$name}.php";
        if (file_exists($path)) {
            echo "⚠️ Model allaqachon mavjud: {$name}\n";
            return;
        }

        // 1️⃣ Jadval nomi
        $tableName = strtolower($this->pluralize($name));

        // 2️⃣ Migration topamiz
        $migrationPath = $this->findMigrationForTable($tableName);

        $columns = [
            'id' => 'int',
            'created_at' => 'string',
            'updated_at' => 'string'
        ];

        // 3️⃣ Agar migration topilsa — columnlarni o‘qib chiqamiz
        if ($migrationPath) {
            $parsed = $this->parseMigration($migrationPath);
            $columns = array_merge($columns, $parsed);
            echo "📦 Migration topildi: " . basename($migrationPath) . "\n";
        } else {
            echo "⚠️ Migration topilmadi, default ustunlar ishlatildi.\n";
        }

        // 4️⃣ @property PHPDoc’lar
        $properties = [];
        foreach ($columns as $col => $type) {
            $properties[] = " * @property {$type} \${$col}";
        }
        $properties = implode("\n", $properties);

        // 5️⃣ Fillable massiv
        $fillable = array_filter(array_keys($columns), fn($c) => !in_array($c, ['id', 'created_at', 'updated_at']));
        $fillableArray = "['" . implode("', '", $fillable) . "']";

        // 6️⃣ Validation rules
        $rules = [];
        foreach ($columns as $col => $type) {
            if (in_array($col, ['id', 'created_at', 'updated_at'])) continue;

            switch ($type) {
                case 'int':
                case 'integer':
                    $rules[$col] = 'integer';
                    break;
                case 'bool':
                case 'boolean':
                    $rules[$col] = 'boolean';
                    break;
                case 'date':
                    $rules[$col] = 'date';
                    break;
                default:
                    $rules[$col] = 'string';
                    break;
            }
        }

        $ruleArray = implode(",\n            ", array_map(fn($k, $v) => "'{$k}' => '{$v}'", array_keys($rules), $rules));

        // 7️⃣ Model content
        $content = <<<PHP
<?php
namespace App\Models;

use Illuminate\\Database\\Eloquent\\Model;

/**
 * Class {$name}
 *
 * @package App\\Models
 *
 * @property int \$id
 * @property string \$name
 * @property string|null \$description
 * @property string \$created_at
 * @property string \$updated_at
 *
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$name} query()
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$name} whereId(\$value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$name} whereName(\$value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$name} whereCreatedAt(\$value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$name} whereUpdatedAt(\$value)
 */
class {$name} extends Model
{
    protected \$table = '{$tableName}';
    protected \$fillable = {$fillableArray};
    public \$timestamps = true;

    /**
     * 🔍 Validation rules
     */
    public static function validateRules(): array
    {
        return [
            {$ruleArray}
        ];
    }

    /**
     * 🔎 Oddiy query misollar
     */
    public static function byName(string \$name)
    {
        return static::where('name', \$name)->first();
    }

    public static function recent(int \$limit = 10)
    {
        return static::orderByDesc('created_at')->limit(\$limit)->get();
    }
}
PHP;

        file_put_contents($path, $content);
        echo "✅ Model yaratildi: src/Models/{$name}.php\n";
    }

    /**
     * 🔹 Migration faylni topish
     */
    private function findMigrationForTable(string $table): ?string
    {
        $dir = __DIR__ . '/../../../database/migrations';
        if (!is_dir($dir)) return null;

        foreach (glob("{$dir}/*.php") as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, "create('{$table}'") || str_contains($content, "create(\"{$table}\"")) {
                return $file;
            }
        }
        return null;
    }

    /**
     * 🔹 Migration fayldan columnlarni aniqlash
     */
    private function parseMigration(string $path): array
    {
        $columns = [];
        $content = file_get_contents($path);

        // $table->string('name')
        preg_match_all("/\\\$table->(\\w+)\\(['\"](.*?)['\"]/", $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = match ($match[1]) {
                'id', 'bigIncrements' => 'int',
                'integer', 'bigInteger' => 'int',
                'string', 'text' => 'string',
                'boolean' => 'bool',
                'timestamp', 'date', 'datetime' => 'date',
                default => 'string'
            };
            $columns[$match[2]] = $type;
        }

        return $columns;
    }

    /**
     * 🔹 Oddiy pluralizatsiya
     */
    private function pluralize(string $word): string
    {
        $word = trim($word);
        if (str_ends_with(strtolower($word), 'y')) {
            return substr($word, 0, -1) . 'ies';
        }
        if (preg_match('/(s|x|z|ch|sh)$/i', $word)) {
            return $word . 'es';
        }
        return $word . 's';
    }
}

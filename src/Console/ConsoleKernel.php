<?php
namespace App\Console;

use App\Console\Commands\MakeModelCommand;
use App\Console\Commands\MakeMigrationCommand;
use App\Console\Commands\MigrateCommand;
use App\Console\Commands\DbSeedCommand;
use App\Console\Commands\ResetBotCommands;
use App\Console\Commands\SetBotCommands;
use Illuminate\Database\Console\Migrations\ResetCommand;

class ConsoleKernel
{
    private array $commands = [];

    public function __construct()
    {
        $this->commands = [
            'make:model' => MakeModelCommand::class,
            'make:migration' => MakeMigrationCommand::class,
            'migrate' => MigrateCommand::class,
            'db:seed' => DbSeedCommand::class,
            'reset:commands' => ResetBotCommands::class,
        ];
    }

    public function handle(?string $command, array $args = []): void
    {
        if (!$command) {
            echo "⚙️  Foydalanish:\n";
            foreach ($this->commands as $name => $class) {
                echo "  php bin/console {$name}\n";
            }
            return;
        }

        if (!isset($this->commands[$command])) {
            echo "❌  Noma’lum buyruq: {$command}\n";
            return;
        }

        $class = new ($this->commands[$command]);
        $class->handle($args);
    }
}

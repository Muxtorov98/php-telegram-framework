<?php
namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Data\Config;

class Database
{
    public static function connect(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => Config::get('DB_CONNECTION', 'mysql'),
            'host'      => Config::get('DB_HOST', 'localhost'),
            'port'      => Config::get('DB_PORT', '3306'),
            'database'  => Config::get('DB_DATABASE', 'telegram_bot'),
            'username'  => Config::get('DB_USERNAME', 'root'),
            'password'  => Config::get('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}

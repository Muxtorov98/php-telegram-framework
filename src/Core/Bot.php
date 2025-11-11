<?php
namespace App\Core;

use Telegram\Bot\Api;
use App\Data\Config;
use App\Middlewares\LoggingMiddleware;

class Bot
{
    private Api $telegram;
    private Router $router;
    private MiddlewarePipeline $pipeline;

    public function __construct()
    {
        $this->telegram = new Api(Config::get('BOT_TOKEN'));
        $this->router = new Router($this->telegram);
        $this->pipeline = new MiddlewarePipeline();

        // Middlewarelarni qo‘shish
        $this->pipeline->add(new LoggingMiddleware());
    }

    public function run(array $update): void
    {
        $this->pipeline->handle($update, function ($update) {
            $this->router->dispatch($update);
        });
    }
}

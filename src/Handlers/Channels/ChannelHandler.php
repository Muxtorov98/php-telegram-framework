<?php
namespace App\Handlers\Channels;

use Telegram\Bot\Api;
use App\Core\Attributes\Handler;

class ChannelHandler
{
    public function __construct(private Api $bot) {}

    #[Handler(type: 'message', query_param: "channel_message")]
    public function handle(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;

        if ($chatId) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "📢 Kanal xabari qabul qilindi!"
            ]);
        }
    }
}

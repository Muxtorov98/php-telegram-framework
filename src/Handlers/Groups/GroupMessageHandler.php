<?php
namespace App\Handlers\Groups;

use Telegram\Bot\Api;
use App\Core\Attributes\Handler;
use Telegram\Bot\Exceptions\TelegramSDKException;

class GroupMessageHandler
{
    public function __construct(private Api $bot) {}

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: "group_message")]
    public function handle(array $update): void
    {
        $chat = $update['message']['chat'] ?? [];
        $chatId = $chat['id'] ?? null;
        $from = $update['message']['from']['first_name'] ?? 'unknown';

        if ($chatId) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "💬 Guruh xabari qabul qilindi: {$from}"
            ]);
        }
    }
}

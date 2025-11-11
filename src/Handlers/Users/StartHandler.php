<?php
namespace App\Handlers\Users;

use App\Keyboards\Default\DefaultReplyKeyboard;
use App\Core\Attributes\{Handler, Permission};
use App\Services\StartService;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class StartHandler
{
    public function __construct(
        private Api $bot,
        private StartService $service
    ) {}

    /**
     * 🟢 /start komandasi
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/start')]
    public function start(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $user = $update['message']['from']['first_name'] ?? 'do‘stim';

        $text = "👋 Salom, *{$user}!* \n E-commerce botiga xush kelibsiz!\n\n" .
            "Quyidagi menyudan kerakli bo‘limni tanlang.";

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => DefaultReplyKeyboard::build(),
        ]);
    }

    /**
     * ✅ "Ha" bosilganda
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'confirm_yes')]
    #[Permission(role: ['admin', 'manager'])]
    public function confirmYes(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $callbackId = $update['callback_query']['id'];

        $data = $this->service->confirmYes();

        $this->bot->answerCallbackQuery([
            'callback_query_id' => $callbackId,
            'text' => $data['callback_text']
        ]);

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $data['message']
        ]);
    }

    /**
     * ❌ "Yo‘q" bosilganda
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'confirm_no')]
    public function confirmNo(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $callbackId = $update['callback_query']['id'];

        $data = $this->service->confirmNo();

        $this->bot->answerCallbackQuery([
            'callback_query_id' => $callbackId,
            'text' => $data['callback_text']
        ]);

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $data['message']
        ]);
    }
}
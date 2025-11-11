<?php
namespace App\Handlers\Users;

use App\Core\Attributes\Handler;
use App\Services\AuthService;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class AuthHandler
{
    public function __construct(
        private Api $bot,
        private AuthService $service
    ) {}

    // 🔐 /login

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/login')]
    public function startLogin(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? 0;

        // Eski formani tozalash (agar boshqa contextdan kelsa)
        $this->service->getForm()->ensureContext($chatId, 'auth');

        $msg = $this->service->startLogin($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    public function stepPassword(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? 0;
        $text = trim($update['message']['text'] ?? '');

        if (str_starts_with($text, '/')) return;

        $msg = $this->service->stepPassword($chatId, $text);
        if ($msg) {
            $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
        }
    }


    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/logout')]
    public function logout(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? 0;
        $msg = $this->service->logout($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/me')]
    public function profile(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? 0;
        $msg = $this->service->profile($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }
}
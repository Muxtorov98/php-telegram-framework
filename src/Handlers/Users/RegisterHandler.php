<?php
namespace App\Handlers\Users;

use Telegram\Bot\Api;
use App\Core\Attributes\{Handler, FormStep};
use App\Services\RegisterService;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class RegisterHandler
{
    public function __construct(
        private Api $bot,
        private RegisterService $service
    ) {}

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/register')]
    public function start(array $update): void
    {
        $chatId = $update['message']['chat']['id'];

        $this->service->getForm()->ensureContext($chatId, 'register');

        $msg = $this->service->start($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'name', next: 'age')]
    public function stepName(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $msg = $this->service->stepName($chatId, $text);
        if ($msg) $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'age', next: 'password')]
    public function stepAge(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $msg = $this->service->stepAge($chatId, $text);
        if ($msg) $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'password', next: 'confirm')]
    public function stepPassword(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $data = $this->service->stepPassword($chatId, $text);

        if ($data) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $data['text'],
                'reply_markup' => json_encode(['inline_keyboard' => $data['keyboard']])
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'confirm_yes')]
    #[FormStep(name: 'confirm', final: true)]
    public function confirmYes(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $msg = $this->service->confirmYes($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'confirm_no')]
    public function confirmNo(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $msg = $this->service->confirmNo($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }
}
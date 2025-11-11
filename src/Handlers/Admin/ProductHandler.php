<?php
namespace App\Handlers\Admin;

use Telegram\Bot\Api;
use App\Core\Attributes\Handler;
use App\Core\Attributes\FormStep;
use App\Core\Attributes\Permission;
use App\Services\ProductService;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class ProductHandler
{
    public function __construct(
        private Api $bot,
        private ProductService $service
    ) {}

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/add_product')]
    #[Permission(role: ['admin'])]
    public function start(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->start($chatId)]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'name', next: 'price')]
    public function stepName(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->stepName($chatId, $update['message']['text'])]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'price', next: 'description')]
    public function stepPrice(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->stepPrice($chatId, $update['message']['text'])]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'description', next: 'image')]
    public function stepDescription(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->stepDescription($chatId, $update['message']['text'])]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'photo')]
    #[FormStep(name: 'image', next: 'confirm')]
    public function stepImage(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'];
        $data = $this->service->stepImage($chatId, $file);
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
    #[Handler(type: 'callback_query', query_param: 'product_confirm_yes')]
    #[FormStep(name: 'confirm', final: true)]
    public function confirmYes(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->confirmYes($chatId)]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'product_confirm_no')]
    public function confirmNo(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $this->service->confirmNo($chatId)]);
    }

    #[Handler(type: 'message', query_param: '/products')]
    #[Permission(role: ['admin'])]
    public function listAll(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->service->sendAllProducts($chatId);
    }
}
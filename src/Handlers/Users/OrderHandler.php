<?php
namespace App\Handlers\Users;

use App\Core\Attributes\{Handler, FormStep, Permission};
use App\Services\OrderService;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class OrderHandler
{
    public function __construct(
        private Api $bot,
        private OrderService $service
    ) {}

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/order')]
    public function start(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->service->getForm()->ensureContext($chatId, 'order');
        $msg = $this->service->start($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'product', next: 'quantity')]
    public function stepProduct(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $msg = $this->service->stepProduct($chatId, $text);
        if ($msg) $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'quantity', next: 'address')]
    public function stepQuantity(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $msg = $this->service->stepQuantity($chatId, $text);
        if ($msg) $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message')]
    #[FormStep(name: 'address', next: 'confirm')]
    public function stepAddress(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);
        $data = $this->service->stepAddress($chatId, $text);
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
    #[Handler(type: 'callback_query', query_param: 'order_confirm_yes')]
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
    #[Handler(type: 'callback_query', query_param: 'order_confirm_no')]
    public function confirmNo(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $msg = $this->service->confirmNo($chatId);
        $this->bot->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
    }

    /**
     * 📦 Foydalanuvchi o‘z buyurtmalarini ko‘radi
     */
    #[Handler(type: 'message', query_param: '/my_orders')]
    #[Handler(type: 'message', query_param: '📦 Buyurtmalarim')]
    public function showUserOrders(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->service->showUserOrders($chatId);
    }

    /**
     * 🧾 Admin barcha buyurtmalarni ko‘radi
     */
    #[Handler(type: 'message', query_param: '/admin_orders')]
    #[Permission(role: ['admin', 'manager'])]
    public function showAllOrders(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->service->showAllOrders($chatId);
    }

    /**
     * ✅ Admin buyurtma holatini o‘zgartiradi
     * Misol: callback_data = order_status:15:done
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'order_status')]
    #[Permission(role: ['admin', 'manager'])]
    public function updateStatus(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $data = explode(':', $update['callback_query']['data']);

        if (count($data) < 3) return;

        [, $orderId, $status] = $data;
        $success = $this->service->updateStatus((int)$orderId, $status);

        if ($success) {
            $this->bot->answerCallbackQuery([
                'callback_query_id' => $update['callback_query']['id'],
                'text' => "✅ Buyurtma #$orderId holati yangilandi: $status"
            ]);
        } else {
            $this->bot->answerCallbackQuery([
                'callback_query_id' => $update['callback_query']['id'],
                'text' => "❌ Buyurtma topilmadi!"
            ]);
        }
    }
}
<?php
namespace App\Handlers\Users;

use App\Core\Attributes\Handler;
use App\Services\CartService;

final class CartHandler
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * /cart buyrug‘i
     */
    #[Handler(type: 'message', query_param: '/cart')]
    public function showCartByCommand(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->cartService->showCart($chatId);
    }

    /**
     * “🛒 Savatim” menyusi
     */
    #[Handler(type: 'message', query_param: '🛒 Savatim')]
    public function showCartByButton(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->cartService->showCart($chatId);
    }

    /**
     * “✅ Buyurtma berish”
     */
    #[Handler(type: 'callback_query', query_param: 'order_create')]
    public function createOrder(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $this->cartService->createOrder($chatId);
    }

    /**
     * “🗑 Savatni tozalash”
     */
    #[Handler(type: 'callback_query', query_param: 'cart_clear')]
    public function clearCart(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $this->cartService->clearCart($chatId);
    }
}
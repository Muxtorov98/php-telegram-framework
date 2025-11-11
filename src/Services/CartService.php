<?php
namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use Telegram\Bot\Api;

final class CartService
{
    public function __construct(private Api $bot) {}

    /**
     * 🛒 Savatni ko‘rish
     */
    public function showCart(int $chatId): void
    {
        $cart = $_SESSION['cart'][$chatId] ?? [];

        if (empty($cart)) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛍 Savatingiz bo‘sh."
            ]);
            return;
        }

        $products = Product::whereIn('id', $cart)->get();
        $text = "🛒 Sizning savatingiz:\n\n";

        foreach ($products as $i => $p) {
            $text .= ($i + 1) . ". {$p->name} — {$p->price} so‘m\n";
        }

        $keyboard = [
            [['text' => '✅ Buyurtma berish', 'callback_data' => 'order_create']],
            [['text' => '🗑 Savatni tozalash', 'callback_data' => 'cart_clear']],
        ];

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }

    /**
     * 🧾 Buyurtma yaratish
     */
    public function createOrder(int $chatId): void
    {
        $cart = $_SESSION['cart'][$chatId] ?? [];

        if (empty($cart)) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛒 Savatingiz bo‘sh. Avval mahsulot qo‘shing."
            ]);
            return;
        }

        $products = Product::whereIn('id', $cart)->get();

        foreach ($products as $product) {
            Order::create([
                'chat_id' => $chatId,
                'product_name' => $product->name,
                'quantity' => 1,
                'address' => 'Toshkent',
                'status' => 'pending',
            ]);
        }

        unset($_SESSION['cart'][$chatId]);

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ Buyurtmangiz qabul qilindi!\nTez orada siz bilan bog‘lanamiz."
        ]);
    }

    /**
     * 🧹 Savatni tozalash
     */
    public function clearCart(int $chatId): void
    {
        unset($_SESSION['cart'][$chatId]);

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🧹 Savatingiz tozalandi."
        ]);
    }
}
<?php
namespace App\Keyboards\Inline;

use App\Models\Product;

final class ProductKeyboard
{
    public function buildList(array $products, int $page, int $totalPages): string
    {
        $buttons = [];
        foreach ($products as $p) {
            $buttons[] = [
                ['text' => "📦 {$p['name']} — {$p['price']} so‘m", 'callback_data' => "product_view:{$p['id']}"]
            ];
        }

        // Pagination tugmalar
        $nav = [];
        if ($page > 1) $nav[] = ['text' => "⬅️", 'callback_data' => "product_page:" . ($page - 1)];
        $nav[] = ['text' => "{$page}/{$totalPages}", 'callback_data' => 'noop'];
        if ($page < $totalPages) $nav[] = ['text' => "➡️", 'callback_data' => "product_page:" . ($page + 1)];

        if ($nav) $buttons[] = $nav;

        return json_encode(['inline_keyboard' => $buttons]);
    }

    public function buildProductActions(Product $product): string
    {
        return json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🛒 Savatga qo‘shish", 'callback_data' => "add_to_cart:{$product->id}"]
                ],
                [
                    ['text' => "⬅️ Orqaga", 'callback_data' => "product_page:1"]
                ]
            ]
        ]);
    }
}
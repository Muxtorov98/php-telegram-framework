<?php
namespace App\Keyboards\Inline;

use Telegram\Bot\Keyboard\Keyboard;

class AdminProductKeyboard
{
    public function build(int $productId): string
    {
        $keyboard = Keyboard::make([
            'inline_keyboard' => [
                [
                    ['text' => '✏️ Tahrirlash', 'callback_data' => "edit_product:$productId"],
                    ['text' => '🗑 O‘chirish', 'callback_data' => "delete_product:$productId"]
                ]
            ]
        ]);

        return json_encode($keyboard->toArray());
    }
}
<?php
namespace App\Keyboards\Default;

final class DefaultReplyKeyboard
{
    public static function build(): string
    {
        $keyboard = [
            [['text' => '🛍 Mahsulotlar']],
            [['text' => '🛒 Savatim'], ['text' => '📦 Buyurtmalarim']],
            [['text' => 'ℹ️ Yordam']],
        ];

        return json_encode([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);
    }
}
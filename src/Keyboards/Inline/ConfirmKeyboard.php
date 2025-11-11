<?php
namespace App\Keyboards\Inline;

use Telegram\Bot\Keyboard\Keyboard;

class ConfirmKeyboard
{
    /**
     * ✅ Tasdiqlash / ❌ Bekor qilish tugmalari
     */
    public function build(): string
    {
        $keyboard = Keyboard::make([
            'inline_keyboard' => [
                [
                    ['text' => '✅ Tasdiqlash', 'callback_data' => 'confirm_yes'],
                    ['text' => '❌ Bekor qilish', 'callback_data' => 'confirm_no']
                ]
            ]
        ]);

        // Telegram kutgan formatda qaytariladi
        return json_encode($keyboard->toArray());
    }
}

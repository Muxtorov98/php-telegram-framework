<?php
namespace App\Keyboards\Default;

use Telegram\Bot\Keyboard\Keyboard;

class MainMenuKeyboard
{
    public function build()
    {
        return Keyboard::make([
            'keyboard' => [
                [['text' => '/start'], ['text' => '/help']],
                [['text' => '🎥 Video yubor'], ['text' => '📁 Fayl yubor']],
                [['text' => '🎧 Audio yubor']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
}

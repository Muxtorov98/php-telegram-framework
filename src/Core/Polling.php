<?php
namespace App\Core;

use Telegram\Bot\Api;
use App\Data\Config;
use App\Console\Commands\SetBotCommands;

class Polling
{
    private Api $telegram;
    private Bot $bot;

    public function __construct()
    {
        $this->telegram = new Api(Config::get('BOT_TOKEN'));
        $this->bot = new Bot();

        // 🔧 Bot komandalarini (sidebar menyu) avtomatik o‘rnatamiz
        $this->registerBotCommands();
    }

    /**
     * 📋 Telegram sidebar menyusini o‘rnatish
     */
    private function registerBotCommands(): void
    {
        try {
            (new SetBotCommands())->handle();
            echo "✅ Telegram bot komandalar (sidebar) muvaffaqiyatli o‘rnatildi.\n";
        } catch (\Throwable $e) {
            echo "⚠️ Komandalarni o‘rnatishda xato: {$e->getMessage()}\n";
        }
    }

    /**
     * 🚀 Botni ishga tushirish (Long Polling)
     */
    public function run(): void
    {
        echo "🤖 Bot started via long polling...\n";
        $offset = 0;

        while (true) {
            try {
                $updates = $this->telegram->getUpdates([
                    'offset' => $offset,
                    'timeout' => 30,
                ]);

                foreach ($updates as $update) {
                    $this->bot->run($update->toArray());
                    $offset = $update->getUpdateId() + 1;
                }
            } catch (\Throwable $e) {
                echo "❌ Xato: {$e->getMessage()}\n";
                sleep(5);
            }
        }
    }
}
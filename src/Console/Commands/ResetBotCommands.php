<?php
namespace App\Console\Commands;

use Telegram\Bot\Api;
use App\Data\Config;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * 🧹 Telegram bot menyularini tozalab, qayta o‘rnatish
 *
 * 👉 Run:
 * docker compose exec telegram-bot php bin/console reset:commands
 */
final class ResetBotCommands
{
    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        $bot = new Api(Config::get('BOT_TOKEN'));

        echo "🧹 Eski komandalar tozalanmoqda...\n";

        // 1️⃣ GLOBAL komandalarni o‘chirish
        $bot->deleteMyCommands(['scope' => ['type' => 'default']]);

        // 2️⃣ Adminlar uchun komandalarni o‘chirish
        foreach (Config::getAdminIds() as $adminId) {
            $bot->deleteMyCommands([
                'scope' => ['type' => 'chat', 'chat_id' => (int)$adminId],
            ]);
        }

        // 3️⃣ Managerlar uchun komandalarni o‘chirish
        foreach (Config::getManagerIds() as $managerId) {
            $bot->deleteMyCommands([
                'scope' => ['type' => 'chat', 'chat_id' => (int)$managerId],
            ]);
        }

        echo "✅ Barcha eski komandalar o‘chirildi.\n";

        // 🔁 Yangi komandalarni qayta o‘rnatamiz
        echo "⚙️  Yangi komandalar o‘rnatilmoqda...\n";
        (new SetBotCommands())->handle();

        echo "🎯 Qayta o‘rnatish yakunlandi!\n";
    }
}
<?php
namespace App\Console\Commands;

use Telegram\Bot\Api;
use App\Data\Config;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * 🔧 Telegram bot komandalarini o‘rnatish (rolga qarab)
 *
 * 👉 Run:
 * docker compose exec telegram-bot php bin/console set:commands
 */
final class SetBotCommands
{
    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        $bot = new Api(Config::get('BOT_TOKEN'),);

        // 🧍 Oddiy foydalanuvchilar uchun menyu
        $userCommands = [
            ['command' => 'start',        'description' => '🤖 Botni ishga tushirish'],
            ['command' => 'products',     'description' => '🛍 Mahsulotlarni ko‘rish'],
            ['command' => 'cart',         'description' => '🛒 Savatni ko‘rish'],
            ['command' => 'my_orders',    'description' => '📦 Buyurtmalarim'],
            ['command' => 'help',         'description' => '❓ Yordam olish'],
        ];

        // 👑 Adminlar uchun menyu
        $adminCommands = [
            ['command' => 'start',            'description' => '🤖 Botni ishga tushirish'],
            ['command' => 'add_product',      'description' => '➕ Yangi mahsulot qo‘shish'],
            ['command' => 'products',         'description' => '🛍 Mahsulotlar ro‘yxati'],
            ['command' => 'cart',             'description' => '🛒 Savatni ko‘rish'],
            ['command' => 'orders',           'description' => '📦 Barcha buyurtmalar'],
            ['command' => 'notify_users',     'description' => '📢 Xabar yuborish'],
            ['command' => 'help',             'description' => '❓ Yordam'],
        ];

        // 👔 Managerlar uchun menyu
        $managerCommands = [
            ['command' => 'start',        'description' => '🤖 Botni ishga tushirish'],
            ['command' => 'orders',       'description' => '📦 Buyurtmalarni boshqarish'],
            ['command' => 'products',     'description' => '🛍 Mahsulotlarni ko‘rish'],
            ['command' => 'help',         'description' => '❓ Yordam olish'],
        ];

        echo "⚙️ Komandalar o‘rnatilmoqda...\n";

        // 🔹 1. GLOBAL foydalanuvchilar uchun
        $bot->deleteMyCommands(['scope' => ['type' => 'default']]);
        $bot->setMyCommands([
            'commands' => $userCommands,
            'scope' => ['type' => 'default'],
        ]);
        echo "✅ Global (user) komandalar o‘rnatildi.\n";

        // 🔹 2. ADMINLAR uchun — faqat chat mavjud bo‘lsa
        foreach (Config::getAdminIds() as $adminId) {
            try {
                $chat = $bot->getChat(['chat_id' => (int)$adminId]);
                if (!$chat) continue;

                $bot->setMyCommands([
                    'commands' => $adminCommands,
                    'scope' => [
                        'type' => 'chat',
                        'chat_id' => (int)$adminId,
                    ],
                ]);
                echo "👑 Admin komandalar o‘rnatildi: {$adminId}\n";
            } catch (\Throwable $e) {
                echo "⚠️ Admin {$adminId} uchun chat topilmadi yoki ulanish xatosi.\n";
            }
        }

        // 🔹 3. MANAGERLAR uchun
        foreach (Config::getManagerIds() as $managerId) {
            try {
                $chat = $bot->getChat(['chat_id' => (int)$managerId]);
                if (!$chat) continue;

                $bot->setMyCommands([
                    'commands' => $managerCommands,
                    'scope' => [
                        'type' => 'chat',
                        'chat_id' => (int)$managerId,
                    ],
                ]);
                echo "🧑‍💼 Manager komandalar o‘rnatildi: {$managerId}\n";
            } catch (\Throwable $e) {
                echo "⚠️ Manager {$managerId} uchun chat topilmadi yoki ulanish xatosi.\n";
            }
        }

        echo "🎯 Barcha rollar uchun komandalar yangilandi!\n";
    }
}
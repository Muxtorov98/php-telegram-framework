<?php
namespace App\Helpers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * ⚙️ PaginationHelper — professional sahifalash helper
 * Format: ⬅️ 1 / 15 ➡️
 */
final class PaginationHelper
{
    public function __construct(private Api $bot) {}

    /**
     * 🧮 Sahifalash hisob-kitobi
     */
    public function paginate(int $totalItems, int $page, int $perPage): array
    {
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'totalPages' => $totalPages,
            'offset' => $offset,
            'limit' => $perPage,
        ];
    }

    /**
     * 🧭 Inline sahifa tugmalarini yaratish
     * Format: ⬅️ 1 / 15 ➡️ + pastda Next / Prev / Close
     */
    public function build(int $current, int $total, string $prefix): array
    {
        $keyboard = [];

        // Asosiy sahifalash tugmalari
        $row1 = [];

        // ⬅️ Oldingi
        if ($current > 1) {
            $row1[] = ['text' => '⬅️ Prev', 'callback_data' => "{$prefix}_page_" . ($current - 1)];
        } else {
            $row1[] = ['text' => '⬅️ Prev', 'callback_data' => "{$prefix}_noop"];
        }

        // Markaziy 1 / 15 raqam
        $row1[] = ['text' => "{$current} / {$total}", 'callback_data' => "{$prefix}_noop"];

        // ➡️ Keyingi
        if ($current < $total) {
            $row1[] = ['text' => '➡️ Next', 'callback_data' => "{$prefix}_page_" . ($current + 1)];
        } else {
            $row1[] = ['text' => '➡️ Next', 'callback_data' => "{$prefix}_noop"];
        }

        $keyboard[] = $row1;

        return $keyboard;
    }

    /**
     * 📤 Tugmalarni yuborish (birinchi page)
     */
    public function sendPageNavigation(
        int $chatId,
        int $current,
        int $total,
        string $prefix,
        ?string $message = null
    ): void {
        $keyboard = $this->build($current, $total, $prefix);

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $message ?: "📄 Sahifa {$current}/{$total}",
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }

    /**
     * 🔁 Eski xabarni yangilash (page o‘zgarganda)
     */
    public function updatePage(
        int $chatId,
        int $messageId,
        string $text,
        int $current,
        int $total,
        string $prefix
    ): void {
        $keyboard = $this->build($current, $total, $prefix);
        try {
            $this->bot->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            ]);
        } catch (TelegramSDKException $e) {
            // fallback: yangi xabar yuborish
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            ]);
        }
    }
}
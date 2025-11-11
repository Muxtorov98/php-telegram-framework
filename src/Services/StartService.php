<?php
namespace App\Services;

use App\Keyboards\Inline\ConfirmKeyboard;
final class StartService
{
    public function __construct(
        private ConfirmKeyboard $confirmKeyboard
    ) {}

    /**
     * 👋 Start komandasi
     */
    public function getStartMessage(array $user): array
    {
        $first    = $user['first_name'] ?? null;
        $last     = $user['last_name'] ?? null;
        $username = $user['username'] ?? null;

        // Foydalanuvchi nomini formatlash
        $displayName = $first
            ? trim($first . ' ' . ($last ?? ''))
            : ($username ? "@{$username}" : "Do‘stim");

        $text = "👋 Assalomu alaykum, *{$displayName}*!\n"
            . "Inline tugma bosish uchun pastdagi menyuni sinab ko‘ring.";

        return [
            'text' => $text,
            'keyboard' => $this->confirmKeyboard->build(),
        ];
    }

    /**
     * ✅ Ha tugmasi
     */
    public function confirmYes(): array
    {
        return [
            'callback_text' => '✅ Tasdiq qabul qilindi!',
            'message' => '🔒 Sizda bu amal uchun ruxsat bor (admin/manager).'
        ];
    }

    /**
     * ❌ Yo‘q tugmasi
     */
    public function confirmNo(): array
    {
        return [
            'callback_text' => '❌ Bekor qilindi.',
            'message' => '❌ Amal bekor qilindi.'
        ];
    }
}
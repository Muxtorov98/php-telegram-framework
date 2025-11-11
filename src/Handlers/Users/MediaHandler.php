<?php
namespace App\Handlers\Users;

use Telegram\Bot\Api;
use App\Core\Attributes\Handler;
use App\Services\LoggerService;

class MediaHandler
{
    private LoggerService $logger;

    public function __construct(private Api $bot)
    {
        $this->logger = new LoggerService();
    }

    /**
     * 🖼 Photo (rasm)
     */
    #[Handler(type: 'photo')]
    public function onPhoto(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("🖼 Rasm qabul qilindi: {$file['local_path']}");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "📸 *Rasm qabul qilindi!*\n\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 📄 Document (fayl)
     */
    #[Handler(type: 'document')]
    public function onDocument(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $doc = $update['message']['document'] ?? [];
        $name = $doc['file_name'] ?? 'nomalum';
        $size = number_format(($doc['file_size'] ?? 0) / 1024, 2);
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("📄 Fayl yuklandi: {$file['local_path']} ({$size} KB)");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "📄 *Fayl saqlandi!*\n\n🧾 Nomi: *{$name}*\n📦 Hajmi: {$size} KB\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 🎥 Video
     */
    #[Handler(type: 'video')]
    public function onVideo(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $video = $update['message']['video'] ?? [];
        $dur = $video['duration'] ?? 0;
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("🎥 Video yuklandi: {$file['local_path']} ({$dur}s)");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎬 *Video yuklandi!*\n\n🕐 Davomiyligi: {$dur} soniya\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 🎧 Audio
     */
    #[Handler(type: 'audio')]
    public function onAudio(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $audio = $update['message']['audio'] ?? [];
        $title = $audio['title'] ?? 'Audio fayl';
        $size = number_format(($audio['file_size'] ?? 0) / 1024, 2);
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("🎧 Audio yuklandi: {$file['local_path']} ({$size} KB)");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎧 *Audio fayl yuklandi!*\n\n🎵 Nomi: *{$title}*\n📦 Hajmi: {$size} KB\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 🎙 Voice (ovozli xabar)
     */
    #[Handler(type: 'voice')]
    public function onVoice(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $voice = $update['message']['voice'] ?? [];
        $dur = $voice['duration'] ?? 0;
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("🎙 Voice xabar qabul qilindi: {$file['local_path']} ({$dur}s)");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎙 *Ovozli xabar qabul qilindi!*\n\n🕐 Davomiyligi: {$dur} soniya\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 📹 Video Note (qisqa video xabar)
     */
    #[Handler(type: 'video_note')]
    public function onVideoNote(array $update, ?array $file): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $note = $update['message']['video_note'] ?? [];
        $dur = $note['duration'] ?? 0;
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId || !$file) return;

        $this->logger->info("📹 Video Note qabul qilindi: {$file['local_path']} ({$dur}s)");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "📹 *Qisqa video xabar qabul qilindi!*\n\n🕐 Davomiyligi: {$dur} soniya\n👤 Foydalanuvchi: @$username\n📂 Joylashuv: `{$file['local_path']}`",
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * 🧩 Sticker
     */
    #[Handler(type: 'sticker')]
    public function onSticker(array $update, ?array $file = null): void
    {
        $chatId = $update['message']['chat']['id'] ?? null;
        $sticker = $update['message']['sticker'] ?? [];
        $emoji = $sticker['emoji'] ?? '🙂';
        $username = $update['message']['from']['username'] ?? 'anonim';

        if (!$chatId) return;

        $this->logger->info("🧩 Sticker qabul qilindi: {$emoji}");

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🧩 *Sticker qabul qilindi!*\n\n{$emoji} - foydalanuvchi: @$username",
            'parse_mode' => 'Markdown'
        ]);
    }
}

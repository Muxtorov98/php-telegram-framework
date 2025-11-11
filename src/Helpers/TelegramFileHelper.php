<?php
namespace App\Helpers;

use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

/**
 * 📦 TelegramFileHelper — barcha fayl turlarini yuborish uchun universal helper
 */
final class TelegramFileHelper
{
    public function __construct(private Api $bot) {}

    /**
     * 📤 Universal fayl yuboruvchi
     */
    public function sendFile(string $type, int $chatId, string $filePath, string $caption = '', string $parseMode = 'Markdown'): void
    {
        if (!file_exists($filePath)) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "⚠️ Fayl topilmadi: `$filePath`",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        $file = InputFile::create($filePath);

        $methods = [
            'photo'       => 'sendPhoto',
            'document'    => 'sendDocument',
            'audio'       => 'sendAudio',
            'voice'       => 'sendVoice',
            'video'       => 'sendVideo',
            'video_note'  => 'sendVideoNote',
            'sticker'     => 'sendSticker',
        ];

        if (!isset($methods[$type])) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Fayl turi qo‘llab-quvvatlanmaydi: *{$type}*",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        $method = $methods[$type];
        $params = [
            'chat_id' => $chatId,
            $type     => $file,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        $this->bot->{$method}($params);
    }

    // ============================================================
    // 🔹 Aniq turlar uchun qulay metodlar
    // ============================================================

    public function sendPhoto(int $chatId, string $path, string $caption = '', string $parseMode = 'Markdown'): void
    {
        $this->sendFile('photo', $chatId, $path, $caption, $parseMode);
    }

    public function sendVideo(int $chatId, string $path, string $caption = '', string $parseMode = 'Markdown'): void
    {
        $this->sendFile('video', $chatId, $path, $caption, $parseMode);
    }

    public function sendAudio(int $chatId, string $path, string $caption = '', string $parseMode = 'Markdown'): void
    {
        $this->sendFile('audio', $chatId, $path, $caption, $parseMode);
    }

    public function sendVoice(int $chatId, string $path, string $caption = '', string $parseMode = 'Markdown'): void
    {
        $this->sendFile('voice', $chatId, $path, $caption, $parseMode);
    }

    public function sendDocument(int $chatId, string $path, string $caption = '', string $parseMode = 'Markdown'): void
    {
        $this->sendFile('document', $chatId, $path, $caption, $parseMode);
    }

    public function sendSticker(int $chatId, string $path): void
    {
        $this->sendFile('sticker', $chatId, $path);
    }

    // ============================================================
    // 🌍 Oddiy media bo‘lmagan turlar uchun
    // ============================================================

    public function sendContact(int $chatId, string $phoneNumber, string $firstName, ?string $lastName = null): void
    {
        $this->bot->sendContact([
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    public function sendLocation(int $chatId, float $latitude, float $longitude): void
    {
        $this->bot->sendLocation([
            'chat_id' => $chatId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function sendDice(int $chatId, string $emoji = '🎲'): void
    {
        $this->bot->sendDice([
            'chat_id' => $chatId,
            'emoji' => $emoji,
        ]);
    }

    public function sendPoll(int $chatId, string $question, array $options): void
    {
        $this->bot->sendPoll([
            'chat_id' => $chatId,
            'question' => $question,
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function sendInvoice(
        int $chatId,
        string $title,
        string $description,
        string $payload,
        string $providerToken,
        string $currency,
        array $prices
    ): void {
        $this->bot->sendInvoice([
            'chat_id' => $chatId,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => $currency,
            'prices' => json_encode($prices),
        ]);
    }
}
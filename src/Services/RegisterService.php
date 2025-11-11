<?php
namespace App\Services;

use App\Core\FormFlowManager;
use App\Models\User;

final class RegisterService
{
    private FormFlowManager $form;
    private LoggerService $logger;

    public function __construct()
    {
        $this->form = new FormFlowManager();
        $this->logger = new LoggerService();
    }

    public function getForm(): FormFlowManager
    {
        return $this->form;
    }

    // ==========================================================
    // 🟢 1. Boshlanish — ism so‘rash
    // ==========================================================
    public function start(int $chatId): string
    {
        $existing = User::where('chat_id', $chatId)->first();
        if ($existing) {
            return "⚠️ Siz allaqachon ro‘yxatdan o‘tgansiz!\n👤 {$existing->username} | 🎭 {$existing->role}";
        }

        $this->form->start($chatId, 'name', 'register');
        return "🧍 Ismingizni kiriting:";
    }

    // ==========================================================
    // 🧩 2. Ism → Yosh
    // ==========================================================
    public function stepName(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'register') return '';

        if (mb_strlen($text) < 2) {
            return "⚠️ Ism kamida 2 ta harfdan iborat bo‘lishi kerak.";
        }

        $this->form->saveAnswer($chatId, 'name', $text);
        $this->form->setStep($chatId, 'age');
        return "🔢 Yoshingizni kiriting:";
    }

    // ==========================================================
    // 🧩 3. Yosh → Parol
    // ==========================================================
    public function stepAge(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'register') return '';

        if (!is_numeric($text) || $text < 10 || $text > 100) {
            return "⚠️ Yosh 10–100 oralig‘ida bo‘lishi kerak.";
        }

        $this->form->saveAnswer($chatId, 'age', $text);
        $this->form->setStep($chatId, 'password');
        return "🔑 Endi parol kiriting (kamida 4 ta belgi):";
    }

    // ==========================================================
    // 🧩 4. Parol → Tasdiqlash
    // ==========================================================
    public function stepPassword(int $chatId, string $text): array
    {
        if ($this->form->getContext($chatId) !== 'register') return [];

        if (strlen($text) < 4) {
            return ['text' => "⚠️ Parol kamida 4 ta belgidan iborat bo‘lishi kerak.", 'keyboard' => []];
        }

        $this->form->saveAnswer($chatId, 'password', $text);
        $this->form->setStep($chatId, 'confirm');

        $answers = $this->form->getAnswers($chatId);
        $summary = "📋 Ma’lumotlaringiz:\n";
        foreach ($answers as $key => $val) {
            if ($key === 'password') continue;
            $summary .= ucfirst($key) . ": $val\n";
        }

        return [
            'text' => $summary . "\nTasdiqlaysizmi?",
            'keyboard' => [
                [['text' => '✅ Ha', 'callback_data' => 'confirm_yes']],
                [['text' => '❌ Yo‘q', 'callback_data' => 'confirm_no']]
            ]
        ];
    }

    // ==========================================================
    // ✅ Tasdiqlash
    // ==========================================================
    public function confirmYes(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'register') {
            return "⚠️ Siz hali formani to‘ldirmagansiz. /register ni qayta boshlang.";
        }

        $answers = $this->form->complete($chatId);

        $user = User::create([
            'username' => $answers['name'],
            'password' => password_hash($answers['password'], PASSWORD_BCRYPT),
            'chat_id' => $chatId,
            'role' => 'user',
        ]);

        return "🎉 Ro‘yxatdan o‘tish yakunlandi!\n👤 {$user->username}\n🕑 Yosh: {$answers['age']}\n🎭 Rol: {$user->role}";
    }

    // ==========================================================
    // ❌ Bekor qilish
    // ==========================================================
    public function confirmNo(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'register') return '';
        $this->form->reset($chatId);
        return "🚫 Ro‘yxatdan o‘tish bekor qilindi. /register ni qayta boshlang.";
    }
}
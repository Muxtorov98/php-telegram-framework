<?php
namespace App\Services;

use App\Core\FormFlowManager;
use App\Models\User;
use Telegram\Bot\Api;

final class AuthService
{
    private string $attemptFile;
    private FormFlowManager $form;
    private LoggerService $logger;

    public function __construct()
    {
        $this->form = new FormFlowManager();
        $this->logger = new LoggerService();

        $this->attemptFile = __DIR__ . '/../../storage/login_attempts.json';
        if (!file_exists($this->attemptFile)) {
            file_put_contents($this->attemptFile, json_encode([]));
        }
    }

    public function getForm(): FormFlowManager
    {
        return $this->form;
    }

    // ==========================================================
    // 🟢 Boshlanish
    // ==========================================================
    public function startLogin(int $chatId): string
    {
        $user = User::where('chat_id', $chatId)->first();

        if ($this->isBlocked($chatId)) {
            return "⏳ Siz ko‘p marta xato parol kiritdingiz.\n1 daqiqa kuting va qayta urinib ko‘ring.";
        }

        if (!$user) {
            return "❌ Siz hali ro‘yxatdan o‘tmagansiz.\nIltimos, /register orqali ro‘yxatdan o‘ting.";
        }

        $this->form->start($chatId, 'login_password', 'auth');
        return "🔑 Parolingizni kiriting:";
    }

    // ==========================================================
    // 🧩 Parol tekshirish
    // ==========================================================
    public function stepPassword(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'auth') return '';

        if ($this->isBlocked($chatId)) {
            return "⏳ Siz vaqtincha bloklangansiz. 1 daqiqa kuting.";
        }

        $user = User::where('chat_id', $chatId)->first();
        if (!$user) {
            $this->form->reset($chatId);
            return "⚠️ Siz ro‘yxatdan o‘tmagansiz. /register ni boshing.";
        }

        if (!password_verify($text, $user->password)) {
            $this->addFailedAttempt($chatId);
            $remaining = max(0, 3 - $this->getFailedAttempts($chatId));

            if ($remaining > 0) {
                return "❌ Parol noto‘g‘ri. Qayta urinib ko‘ring.\n📉 Qolgan urinishlar: {$remaining}";
            } else {
                $this->blockUser($chatId);
                return "🚫 Juda ko‘p urinishlar. 1 daqiqa bloklandingiz.";
            }
        }

        // ✅ Muvaffaqiyatli login
        $this->resetAttempts($chatId);
        $_SESSION['auth'][$chatId] = [
            'username' => $user->username,
            'role' => $user->role,
            'logged_in' => true
        ];

        $this->form->reset($chatId);
        return "🎉 Xush kelibsiz, {$user->username}!\n🎭 Rol: {$user->role}";
    }

    // ==========================================================
    // 🚪 Logout
    // ==========================================================
    public function logout(int $chatId): string
    {
        unset($_SESSION['auth'][$chatId]);
        return "👋 Siz tizimdan chiqdingiz. /login orqali qayta kiring.";
    }

    // ==========================================================
    // 👤 Profil
    // ==========================================================
    public function profile(int $chatId): string
    {
        $session = $_SESSION['auth'][$chatId] ?? null;
        if (!$session || empty($session['logged_in'])) {
            return "🔒 Siz tizimga kirmagansiz. /login ni bosing.";
        }

        return "👤 Profil:\nUsername: {$session['username']}\n🎭 Rol: {$session['role']}";
    }

    // ==========================================================
    // 🧠 Bloklash va urinishlar
    // ==========================================================
    private function addFailedAttempt(int $chatId): void
    {
        $data = $this->readAttempts();
        $data[$chatId]['count'] = ($data[$chatId]['count'] ?? 0) + 1;
        $data[$chatId]['last'] = time();
        file_put_contents($this->attemptFile, json_encode($data));
    }

    private function getFailedAttempts(int $chatId): int
    {
        $data = $this->readAttempts();
        return $data[$chatId]['count'] ?? 0;
    }

    private function resetAttempts(int $chatId): void
    {
        $data = $this->readAttempts();
        unset($data[$chatId]);
        file_put_contents($this->attemptFile, json_encode($data));
    }

    private function blockUser(int $chatId): void
    {
        $data = $this->readAttempts();
        $data[$chatId]['blocked_until'] = time() + 60;
        file_put_contents($this->attemptFile, json_encode($data));
    }

    private function isBlocked(int $chatId): bool
    {
        $data = $this->readAttempts();
        if (!isset($data[$chatId]['blocked_until'])) return false;

        if (time() > $data[$chatId]['blocked_until']) {
            unset($data[$chatId]);
            file_put_contents($this->attemptFile, json_encode($data));
            return false;
        }

        return true;
    }

    private function readAttempts(): array
    {
        $json = @file_get_contents($this->attemptFile);
        return $json ? json_decode($json, true) ?? [] : [];
    }
}
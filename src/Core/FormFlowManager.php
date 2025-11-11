<?php
namespace App\Core;

use App\Services\LoggerService;
use App\Support\SessionHelper;

/**
 * 💾 FormFlowManager (Session versiya)
 * Har bir chatId uchun: current_step va answers
 * PHP sessiyasi orqali saqlanadi
 */
class FormFlowManager
{
    private LoggerService $logger;

    public function __construct()
    {
        SessionHelper::start();
        $this->logger = new LoggerService();
    }

    /**
     * 🚀 Formani boshlash
     */
    public function start(int $chatId, string $firstStep, string $context): void
    {
        $_SESSION['forms'][$chatId] = [
            'context' => $context,
            'current_step' => $firstStep,
            'answers' => [],
        ];
        $this->logger->info("🧩 Forma boshlandi: {$context} (chat={$chatId})");
    }

    /**
     * 🔍 Joriy contextni olish
     */
    public function getContext(int $chatId): ?string
    {
        return $_SESSION['forms'][$chatId]['context'] ?? null;
    }

    /**
     * 🔄 Context o‘zgarganda avtomatik tozalash
     */
    public function ensureContext(int $chatId, string $newContext): void
    {
        $current = $this->getContext($chatId);
        if ($current && $current !== $newContext) {
            // 🔥 Eski formani tozalaymiz
            unset($_SESSION['forms'][$chatId]);
            $this->logger->warning("🧹 Context o‘zgardi: {$current} → {$newContext} (chat={$chatId})");
        }
    }

    /**
     * 🔖 Stepni olish
     */
    public function getStep(int $chatId): ?string
    {
        return $_SESSION['forms'][$chatId]['current_step'] ?? null;
    }

    /**
     * 🔖 Stepni o‘rnatish
     */
    public function setStep(int $chatId, string $step): void
    {
        if (!isset($_SESSION['forms'][$chatId])) return;
        $_SESSION['forms'][$chatId]['current_step'] = $step;
        $this->logger->success("🪜 Step yangilandi: {$step} (chat={$chatId})");
    }

    /**
     * ✏️ Javobni saqlash
     */
    public function saveAnswer(int $chatId, string $key, string $value): void
    {
        if (!isset($_SESSION['forms'][$chatId])) return;
        $_SESSION['forms'][$chatId]['answers'][$key] = $value;
        $this->logger->info("💾 Javob saqlandi: {$key} = {$value} (chat={$chatId})");
    }

    /**
     * 📋 Javoblarni olish
     */
    public function getAnswers(int $chatId): array
    {
        return $_SESSION['forms'][$chatId]['answers'] ?? [];
    }

    /**
     * ✅ Yakunlash (va tozalash)
     */
    public function complete(int $chatId): array
    {
        $answers = $_SESSION['forms'][$chatId]['answers'] ?? [];
        unset($_SESSION['forms'][$chatId]);
        $this->logger->success("🎉 Forma yakunlandi (chat={$chatId}) — " . json_encode($answers, JSON_UNESCAPED_UNICODE));
        return $answers;
    }

    /**
     * 🧹 Tozalash
     */
    public function reset(int $chatId): void
    {
        unset($_SESSION['forms'][$chatId]);
        $this->logger->warning("🗑️ Forma tozalandi (chat={$chatId})");
    }
}
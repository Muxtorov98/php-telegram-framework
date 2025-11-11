<?php
namespace App\Services;

class RateLimiterService
{
    private string $storage = 'storage/rate_limit.json';
    private int $limitPerSecond = 1; // 1 ta so‘rov/sekund
    private array $data = [];

    public function __construct()
    {
        if (file_exists($this->storage)) {
            $json = file_get_contents($this->storage);
            $this->data = json_decode($json, true) ?? [];
        }
    }

    /**
     * Foydalanuvchi uchun so‘rov ruxsat etiladimi — tekshiradi
     */
    public function allow(int $chatId): bool
    {
        $now = microtime(true);
        $lastRequest = $this->data[$chatId]['last'] ?? 0;
        $count = $this->data[$chatId]['count'] ?? 0;

        // Agar 1 sekunddan oshgan bo‘lsa, hisobni yangilaymiz
        if ($now - $lastRequest > 1) {
            $this->data[$chatId] = ['last' => $now, 'count' => 1];
            $this->save();
            return true;
        }

        // Limitga yetgan bo‘lsa — bloklash
        if ($count >= $this->limitPerSecond) {
            return false;
        }

        // Hali limit ichida
        $this->data[$chatId]['count'] = $count + 1;
        $this->data[$chatId]['last'] = $now;
        $this->save();

        return true;
    }

    private function save(): void
    {
        if (!is_dir('storage')) mkdir('storage', 0777, true);
        file_put_contents($this->storage, json_encode($this->data, JSON_PRETTY_PRINT));
    }
}
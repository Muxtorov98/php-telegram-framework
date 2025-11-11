<?php
namespace App\Services;

use App\Core\FormFlowManager;
use App\Models\Order;
use Telegram\Bot\Api;

final class OrderService
{
    private FormFlowManager $form;
    private LoggerService $logger;

    public function __construct(private Api $bot,)
    {
        $this->form = new FormFlowManager();
        $this->logger = new LoggerService();
    }

    public function getForm(): FormFlowManager
    {
        return $this->form;
    }

    // ==========================================================
    // 🚀 Boshlanish
    // ==========================================================
    public function start(int $chatId): string
    {
        $this->form->start($chatId, 'product', 'order');
        return "🛒 Buyurtma jarayoni boshlandi!\n📦 Qaysi mahsulotni xohlaysiz?";
    }

    // ==========================================================
    // 🧩 Step 1: Product → Quantity
    // ==========================================================
    public function stepProduct(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'order') return '';

        if (mb_strlen($text) < 2) {
            return "⚠️ Mahsulot nomi juda qisqa.";
        }

        $this->form->saveAnswer($chatId, 'product_name', $text);
        $this->form->setStep($chatId, 'quantity');

        return "🔢 Nechta dona buyurtma qilmoqchisiz?";
    }

    // ==========================================================
    // 🧩 Step 2: Quantity → Address
    // ==========================================================
    public function stepQuantity(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'order') return '';

        if (!is_numeric($text) || (int)$text <= 0) {
            return "⚠️ Miqdor 1 dan katta raqam bo‘lishi kerak.";
        }

        $this->form->saveAnswer($chatId, 'quantity', (int)$text);
        $this->form->setStep($chatId, 'address');

        return "📍 Yetkazib berish manzilingizni kiriting:";
    }

    // ==========================================================
    // 🧩 Step 3: Address → Confirm
    // ==========================================================
    public function stepAddress(int $chatId, string $text): array
    {
        if ($this->form->getContext($chatId) !== 'order') return [];

        if (mb_strlen($text) < 5) {
            return ['text' => "⚠️ Manzil juda qisqa.", 'keyboard' => []];
        }

        $this->form->saveAnswer($chatId, 'address', $text);
        $this->form->setStep($chatId, 'confirm');

        $answers = $this->form->getAnswers($chatId);

        $summary = "📋 Buyurtma ma’lumotlari:\n"
            . "📦 Mahsulot: {$answers['product_name']}\n"
            . "🔢 Miqdor: {$answers['quantity']} dona\n"
            . "📍 Manzil: {$answers['address']}\n\n"
            . "Tasdiqlaysizmi?";

        return [
            'text' => $summary,
            'keyboard' => [
                [['text' => '✅ Ha', 'callback_data' => 'order_confirm_yes']],
                [['text' => '❌ Yo‘q', 'callback_data' => 'order_confirm_no']]
            ]
        ];
    }

    // ==========================================================
    // ✅ Tasdiqlash
    // ==========================================================
    public function confirmYes(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'order') return '';
        $answers = $this->form->complete($chatId);

        Order::create([
            'chat_id' => $chatId,
            'product_name' => $answers['product_name'],
            'quantity' => $answers['quantity'],
            'address' => $answers['address'],
            'status' => 'pending',
        ]);

        return "✅ Buyurtma muvaffaqiyatli qabul qilindi!\n"
            . "📦 {$answers['product_name']} — {$answers['quantity']} dona\n"
            . "📍 Manzil: {$answers['address']}\n"
            . "⏳ Holat: kutilmoqda";
    }

    // ==========================================================
    // ❌ Bekor qilish
    // ==========================================================
    public function confirmNo(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'order') return '';
        $this->form->reset($chatId);
        return "🚫 Buyurtma bekor qilindi.";
    }

    /**
     * 📦 Foydalanuvchining barcha buyurtmalarini chiqarish
     */
    public function showUserOrders(int $chatId): void
    {
        $orders = Order::where('chat_id', $chatId)
            ->orderBy('id', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "📭 Sizda hali buyurtmalar yo‘q."
            ]);
            return;
        }

        $text = "📦 *Sizning buyurtmalaringiz:*\n\n";

        foreach ($orders as $i => $o) {
            $statusEmoji = match ($o->status) {
                'pending' => '⏳',
                'done'    => '✅',
                'canceled'=> '❌',
                default   => '📦',
            };
            $text .= "{$statusEmoji} {$o->product_name} — {$o->status}\n";
        }

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    /**
     * 🔄 Admin uchun barcha buyurtmalar (opsional)
     */
    public function showAllOrders(int $chatId): void
    {
        $orders = Order::orderBy('id', 'desc')->get();

        if ($orders->isEmpty()) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "🚫 Hali hech qanday buyurtma mavjud emas."
            ]);
            return;
        }

        $text = "🧾 *Barcha buyurtmalar:*\n\n";
        foreach ($orders as $o) {
            $statusEmoji = match ($o->status) {
                'pending' => '⏳',
                'done'    => '✅',
                'canceled'=> '❌',
                default   => '📦',
            };
            $text .= "{$statusEmoji} [#{$o->id}] {$o->product_name} — {$o->status}\n";
        }

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    /**
     * ✅ Buyurtma holatini yangilash
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $order = Order::find($orderId);
        if (!$order) return false;

        $order->status = $status;
        $order->save();

        return true;
    }

}
<?php
namespace App\Services;

use App\Core\FormFlowManager;
use App\Models\Product;
use App\Helpers\TelegramFileHelper;
use Telegram\Bot\Api;

final class ProductService
{
    private const PER_PAGE = 5;
    private FormFlowManager $form;
    private LoggerService $logger;
    private TelegramFileHelper $fileHelper;

    public function __construct(private Api $bot)
    {
        $this->form = new FormFlowManager();
        $this->logger = new LoggerService();
        $this->fileHelper = new TelegramFileHelper($this->bot);
    }

    public function start(int $chatId): string
    {
        $this->form->start($chatId, 'name', 'product');
        return "🛍 Yangi mahsulot nomini kiriting:";
    }

    public function stepName(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'product') return '';
        if (strlen($text) < 2) return "⚠️ Mahsulot nomi kamida 2 ta belgidan iborat bo‘lishi kerak.";

        $this->form->saveAnswer($chatId, 'name', $text);
        $this->form->setStep($chatId, 'price');
        return "💰 Mahsulot narxini kiriting (so‘mda):";
    }

    public function stepPrice(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'product') return '';
        if (!is_numeric($text) || $text <= 0) return "⚠️ Narx raqam bo‘lishi va 0 dan katta bo‘lishi kerak.";

        $this->form->saveAnswer($chatId, 'price', $text);
        $this->form->setStep($chatId, 'description');
        return "📝 Mahsulot tavsifini kiriting:";
    }

    public function stepDescription(int $chatId, string $text): string
    {
        if ($this->form->getContext($chatId) !== 'product') return '';
        $this->form->saveAnswer($chatId, 'description', $text);
        $this->form->setStep($chatId, 'image');
        return "📸 Endi mahsulot rasmi yuboring (jpg/png).";
    }

    public function stepImage(int $chatId, ?array $file): array
    {
        if ($this->form->getContext($chatId) !== 'product') return [];

        if (!$file || !isset($file['local_path'])) {
            return ['text' => "⚠️ Iltimos, mahsulot rasmi yuboring.", 'keyboard' => []];
        }

        $this->form->saveAnswer($chatId, 'image', $file['local_path']);
        $this->form->setStep($chatId, 'confirm');

        $answers = $this->form->getAnswers($chatId);

        $summary = "📋 Mahsulot ma’lumotlari:\n"
            . "📦 Nomi: {$answers['name']}\n"
            . "💰 Narx: {$answers['price']} so‘m\n"
            . "📝 Tavsif: {$answers['description']}\n\n"
            . "Tasdiqlaysizmi?";

        return [
            'text' => $summary,
            'keyboard' => [
                [['text' => '✅ Ha', 'callback_data' => 'product_confirm_yes']],
                [['text' => '❌ Yo‘q', 'callback_data' => 'product_confirm_no']]
            ]
        ];
    }

    public function confirmYes(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'product') return '';
        $answers = $this->form->complete($chatId);

        Product::create([
            'name' => $answers['name'],
            'price' => $answers['price'],
            'description' => $answers['description'],
            'image' => $answers['image'] ?? null,
        ]);

        return "✅ Mahsulot muvaffaqiyatli qo‘shildi!";
    }

    public function confirmNo(int $chatId): string
    {
        if ($this->form->getContext($chatId) !== 'product') return '';
        $this->form->reset($chatId);
        return "🚫 Mahsulot qo‘shish bekor qilindi.";
    }

    public function sendAllProducts(int $chatId): void
    {
        $products = Product::orderBy('id', 'desc')->get()->toArray();

        if (empty($products)) {
            $this->bot->sendMessage(['chat_id' => $chatId, 'text' => "📦 Hozircha mahsulotlar yo‘q."]);
            return;
        }

        foreach ($products as $product) {
            $text = "📦 *{$product['name']}*\n💰 {$product['price']} so‘m\n📝 {$product['description']}";
            $image = $product['image'] ?? null;

            if ($image && file_exists($image)) {
                $this->fileHelper->sendPhoto($chatId, $image, $text);
            } else {
                $this->bot->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown'
                ]);
            }
        }
    }

    public function paginate(int $page = 1): array
    {
        $total = Product::count();
        $totalPages = max(1, ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        $offset = ($page - 1) * self::PER_PAGE;
        $data = Product::skip($offset)->take(self::PER_PAGE)->get()->toArray();

        return [
            'data' => $data,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * 🛒 Savatga qo‘shish (session orqali)
     */
    public function addToCart(int $chatId, int $productId): void
    {
        if (!isset($_SESSION['cart'][$chatId])) {
            $_SESSION['cart'][$chatId] = [];
        }
        $_SESSION['cart'][$chatId][] = $productId;
    }
}
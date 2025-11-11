<?php

namespace App\Handlers\Users;

use Telegram\Bot\Api;
use App\Core\Attributes\Handler;
use App\Models\Product;
use App\Services\ProductService;
use App\Keyboards\Inline\ProductKeyboard;
use App\Services\LoggerService;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class ProductViewHandler
{
    private Api $bot;
    private ProductService $service;
    private ProductKeyboard $keyboard;
    private LoggerService $logger;

    public function __construct(Api $bot, ProductService $service, ProductKeyboard $keyboard, LoggerService $logger)
    {
        $this->bot = $bot;
        $this->service = $service;
        $this->keyboard = $keyboard;
        $this->logger = $logger;
    }

    /**
     * 🛍 /products — mahsulotlar ro‘yxati
     * @throws TelegramSDKException
     */
    #[Handler(type: 'message', query_param: '/products_list')]
    #[Handler(type: 'message', query_param: '🛍 Mahsulotlar')]
    public function index(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $page = 1;

        $products = $this->service->paginate($page);
        if (empty($products['data'])) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛒 Hozircha mahsulotlar mavjud emas.",
            ]);
            return;
        }

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🛍 Mahsulotlar ro‘yxati (sahifa {$page} / {$products['total_pages']})",
            'reply_markup' => $this->keyboard->buildList($products['data'], $page, $products['total_pages'])
        ]);
    }

    /**
     * 📦 Mahsulotni ko‘rish
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'product_view')]
    public function viewProduct(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $callbackData = $update['callback_query']['data']; // masalan: product_view:3
        [, $productId] = explode(':', $callbackData);

        $product = Product::find($productId);
        if (!$product) {
            $this->bot->answerCallbackQuery([
                'callback_query_id' => $update['callback_query']['id'],
                'text' => '❌ Mahsulot topilmadi.'
            ]);
            return;
        }

        $text = "📦 *{$product->name}*\n💰 Narxi: {$product->price} so‘m\n📝 {$product->description}";

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $this->keyboard->buildProductActions($product)
        ]);
    }

    /**
     * ➡️ Pagination (keyingi/oldingi sahifa)
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'product_page')]
    public function paginate(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        $data = explode(':', $update['callback_query']['data']); // product_page:2
        $page = (int)($data[1] ?? 1);

        $products = $this->service->paginate($page);
        $this->bot->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $update['callback_query']['message']['message_id'],
            'text' => "🛍 Mahsulotlar ro‘yxati (sahifa {$page} / {$products['total_pages']})",
            'reply_markup' => $this->keyboard->buildList($products['data'], $page, $products['total_pages'])
        ]);
    }

    /**
     * 🛒 Savatga qo‘shish
     * @throws TelegramSDKException
     */
    #[Handler(type: 'callback_query', query_param: 'add_to_cart')]
    public function addToCart(array $update): void
    {
        $chatId = $update['callback_query']['from']['id'];
        [, $productId] = explode(':', $update['callback_query']['data']);

        $this->service->addToCart($chatId, (int)$productId);

        $this->bot->answerCallbackQuery([
            'callback_query_id' => $update['callback_query']['id'],
            'text' => "🛍 Mahsulot savatga qo‘shildi!",
        ]);
    }
}
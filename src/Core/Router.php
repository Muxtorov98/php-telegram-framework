<?php
namespace App\Core;

use Telegram\Bot\Api;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use App\Core\Attributes\{Handler, Permission, FormStep};
use App\Data\Config;
use App\Services\{LoggerService, RateLimiterService};

/**
 * 🚀 Router — Full Telegram Dispatcher
 * - JSON yoki string callback_data ni qo‘llaydi
 * - File/media yuklash
 * - Form va Context boshqaruvi
 * - Role-based access
 * - noop tugmalarni e’tiborsiz qoldiradi
 */
final class Router
{
    private Api $telegram;
    private LoggerService $logger;
    private RateLimiterService $rateLimiter;
    private array $handlers = [];
    private array $bindings = [];
    private bool $debug = false;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
        $this->logger = new LoggerService();
        $this->rateLimiter = new RateLimiterService();
        $this->debug = filter_var(Config::get('APP_DEBUG', false), FILTER_VALIDATE_BOOL);

        // ✅ Dependency Injection registry
        $this->bindings = [
            Api::class => fn() => $this->telegram,
            LoggerService::class => fn() => $this->logger,
            RateLimiterService::class => fn() => $this->rateLimiter,
        ];

        $this->loadHandlers();
    }

    // =========================================================
    // 🔍 HANDLERLARNI YUKLASH
    // =========================================================
    private function loadHandlers(): void
    {
        $baseDir = __DIR__ . '/../Handlers';
        $baseNamespace = 'App\\Handlers\\';
        $classes = $this->scanDirectory($baseDir, $baseNamespace);

        foreach ($classes as $class) {
            if (!class_exists($class)) continue;
            $ref = new ReflectionClass($class);

            foreach ($ref->getMethods() as $method) {
                $attrs = $method->getAttributes(Handler::class);
                if (empty($attrs)) continue;

                foreach ($attrs as $attr) {
                    $data = $attr->newInstance();
                    $permAttr = $method->getAttributes(Permission::class)[0] ?? null;
                    $roles = $permAttr ? (array)$permAttr->newInstance()->role : [];

                    $instance = $this->resolveDependencies($ref);
                    $this->handlers[$data->type][] = [
                        'instance' => $instance,
                        'method'   => $method->getName(),
                        'meta'     => $data,
                        'roles'    => $roles,
                    ];

                    $this->logger->success("✅ Handler yuklandi: {$class}::{$method->getName()} ({$data->type})");
                }
            }
        }
    }

    // =========================================================
    // 🧠 AUTO DEPENDENCY RESOLVER
    // =========================================================
    private function resolveDependencies(ReflectionClass $ref): object
    {
        $params = $ref->getConstructor()?->getParameters() ?? [];
        $args = [];

        foreach ($params as $param) {
            $type = $param->getType()?->getName();

            if ($type && isset($this->bindings[$type])) {
                $args[] = $this->bindings[$type]();
            } elseif ($type && class_exists($type)) {
                $depRef = new ReflectionClass($type);
                $args[] = $this->resolveDependencies($depRef);
                $this->bindings[$type] = fn() => $args[array_key_last($args)];
            } else {
                $args[] = null;
            }
        }

        return $ref->newInstanceArgs($args);
    }

    // =========================================================
    // 📂 PAPKADAN KLASSLARNI TOPISH
    // =========================================================
    private function scanDirectory(string $dir, string $namespace): array
    {
        $classes = [];
        foreach (scandir($dir) as $file) {
            if (in_array($file, ['.', '..'])) continue;
            $path = "$dir/$file";
            if (is_dir($path)) {
                $classes = array_merge($classes, $this->scanDirectory($path, $namespace . $file . '\\'));
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $classes[] = $namespace . pathinfo($file, PATHINFO_FILENAME);
            }
        }
        return $classes;
    }

    // =========================================================
    // 🔐 ROLE ANIQLASH
    // =========================================================
    private function getRole(int $chatId): string
    {
        $admins   = Config::getAdminIds();
        $managers = Config::getManagerIds();

        return match (true) {
            in_array($chatId, $admins)   => 'admin',
            in_array($chatId, $managers) => 'manager',
            default                      => 'user',
        };
    }

    // =========================================================
    // 🔍 UPDATE TURI ANIQLASH
    // =========================================================
    private function detectUpdateType(array $update): string
    {
        return match (true) {
            isset($update['message']['text'])         => 'message',
            isset($update['callback_query'])          => 'callback_query',
            isset($update['edited_message'])          => 'edited_message',
            isset($update['channel_post'])            => 'channel_post',
            isset($update['edited_channel_post'])     => 'edited_channel_post',
            isset($update['message']['photo'])        => 'photo',
            isset($update['message']['video'])        => 'video',
            isset($update['message']['audio'])        => 'audio',
            isset($update['message']['voice'])        => 'voice',
            isset($update['message']['video_note'])   => 'video_note',
            isset($update['message']['document'])     => 'document',
            isset($update['message']['sticker'])      => 'sticker',
            isset($update['message']['dice'])         => 'dice',
            isset($update['message']['contact'])      => 'contact',
            isset($update['message']['location'])     => 'location',
            isset($update['message']['poll'])         => 'poll',
            isset($update['message']['invoice'])      => 'invoice',
            isset($update['message']['successful_payment']) => 'successful_payment',
            isset($update['my_chat_member'])          => 'my_chat_member',
            isset($update['chat_member'])             => 'chat_member',
            isset($update['chat_join_request'])       => 'chat_join_request',
            default => 'unknown'
        };
    }

    private function extractChatId(array $update, string $type): ?int
    {
        return $type === 'callback_query'
            ? ($update['callback_query']['from']['id'] ?? null)
            : ($update['message']['chat']['id'] ?? null);
    }

    private function extractQueryValue(array $update, string $type): ?string
    {
        return $type === 'callback_query'
            ? ($update['callback_query']['data'] ?? null)
            : ($update['message']['text'] ?? null);
    }

    // =========================================================
    // 📥 FAYL YUKLASH
    // =========================================================
    private function handleFileDownload(array $update, string $type): ?array
    {
        $fileId = match ($type) {
            'photo'       => end($update['message']['photo'])['file_id'] ?? null,
            'video'       => $update['message']['video']['file_id'] ?? null,
            'audio'       => $update['message']['audio']['file_id'] ?? null,
            'voice'       => $update['message']['voice']['file_id'] ?? null,
            'video_note'  => $update['message']['video_note']['file_id'] ?? null,
            'document'    => $update['message']['document']['file_id'] ?? null,
            'sticker'     => $update['message']['sticker']['file_id'] ?? null,
            default => null
        };

        if (!$fileId) return null;

        try {
            $file = $this->telegram->getFile(['file_id' => $fileId]);
            $filePath = $file->get('file_path');
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'bin';
            $localDir = 'storage/uploads';
            if (!is_dir($localDir)) mkdir($localDir, 0777, true);
            $localPath = "$localDir/" . uniqid('tg_', true) . '.' . $ext;

            $fileUrl = "https://api.telegram.org/file/bot" . Config::get('BOT_TOKEN') . "/$filePath";
            copy($fileUrl, $localPath);

            return [
                'file_id' => $fileId,
                'local_path' => $localPath,
                'file_url' => $fileUrl,
                'type' => $type,
            ];
        } catch (Throwable $e) {
            $this->logger->error("❌ Fayl yuklashda xato: {$e->getMessage()}");
            return null;
        }
    }

    // =========================================================
    // 🧩 ASOSIY DISPATCH
    // =========================================================
    public function dispatch(array $update): void
    {
        $type = $this->detectUpdateType($update);
        $chatId = $this->extractChatId($update, $type);
        if (!$chatId) return;

        // 🕒 Spamni oldini olish
        if (!$this->rateLimiter->allow($chatId)) {
            $this->logger->warning("⏱ Juda tez so‘rov: {$chatId}");
            return;
        }

        $query = $this->extractQueryValue($update, $type);
        $fileInfo = $this->handleFileDownload($update, $type);

        // 🧠 JSON yoki string callback_data
        if ($type === 'callback_query') {
            $rawQuery = trim((string)$query);

            // ✅ noop tugmalarni e’tiborsiz qoldirish
            if (in_array($rawQuery, ['noop', 'ignore', 'empty', 'none'], true)) {
                $this->logger->info("⏸️ NOOP callback e’tiborsiz: {$chatId}");
                return;
            }

            // ✅ JSON formatda bo‘lsa
            if (str_starts_with($rawQuery, '{') && str_ends_with($rawQuery, '}')) {
                $decoded = json_decode($rawQuery, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['action'])) {
                    $query = $decoded['action'];
                    $update['callback_query']['data_json'] = $decoded;
                }
            }
        }

        $formManager = new FormFlowManager();
        $currentStep = $formManager->getStep($chatId);
        $currentContext = $formManager->getContext($chatId);

        if (!isset($this->handlers[$type])) return;
        $this->logger->info("📩 Update: {$type} | Query: {$query}");

        foreach ($this->handlers[$type] as $handler) {
            ['instance' => $instance, 'method' => $method, 'meta' => $meta, 'roles' => $roles] = $handler;

            // =========================================================
            // 🧭 /commands va callback_data uchun mustahkam tekshiruv
            // =========================================================
            $queryParam = $meta->query_param;

            if ($queryParam) {
                // Slash bilan boshlansa — bu /command => aniq tenglik kerak
                if (str_starts_with($queryParam, '/')) {
                    if ($query !== $queryParam) continue;
                } else {
                    // callback_data uchun — boshlanishi yetarli
                    if (!str_starts_with((string)$query, (string)$queryParam)) continue;
                }
            }


            // 🔐 Role check
            $userRole = $this->getRole($chatId);
            if ($roles && !in_array($userRole, $roles)) {
                $allowed = implode(', ', $roles);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⛔ Sizda bu komandani bajarish uchun ruxsat yo‘q.\nSizning rolingiz: *{$userRole}*\nRuxsat: *{$allowed}*",
                    'parse_mode' => 'Markdown'
                ]);
                continue;
            }

            // 🧩 FormStep tekshiruvi
            $refMethod = new ReflectionMethod($instance, $method);
            $formStepAttr = $refMethod->getAttributes(FormStep::class)[0] ?? null;

            if ($formStepAttr) {
                $formStep = $formStepAttr->newInstance();
                $handlerContext = strtolower(str_replace('handler', '', class_basename($instance)));

                if ($handlerContext && $currentContext && $handlerContext !== $currentContext) {
                    $this->logger->info("🧱 Context mos emas: {$handlerContext} ≠ {$currentContext}");
                    continue;
                }

                if ($formStep->name !== $currentStep) continue;
            }

            try {
                $instance->$method($update, $fileInfo);

                if ($formStepAttr) {
                    if ($formStep->final) $formManager->complete($chatId);
                    elseif ($formStep->next) $formManager->setStep($chatId, $formStep->next);
                }

                $this->logger->success("✅ Handler ishladi: {$method}");
            } catch (Throwable $e) {
                $this->logger->error("❌ Xato: {$e->getMessage()} ({$method})");
            }
        }
    }
}
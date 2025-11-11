<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;
use App\Core\Attributes\Permission;
use App\Data\Config;
use ReflectionClass;

class PermissionMiddleware implements MiddlewareInterface
{
    public function process(array $update, callable $next): void
    {
        $message = $update['message'] ?? [];
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;

        if (!$chatId) {
            $next($update);
            return;
        }

        // Foydalanuvchi rolini aniqlaymiz
        $role = $this->resolveRole($chatId);

        // Routerga yo‘naltirilgan methodni tekshirish
        // Biz bu ma’lumotni handlerdagi atribut orqali olishimiz kerak
        // (Biz Router’da PermissionMiddleware’ni ham qo‘shamiz)
        $update['user_role'] = $role;

        $next($update);
    }

    private function resolveRole(int $chatId): string
    {
        $admins   = Config::getAdminIds();
        $managers = Config::getManagerIds();

        return match (true) {
            in_array($chatId, $admins)   => 'admin',
            in_array($chatId, $managers) => 'manager',
            default                      => 'user',
        };
    }
}

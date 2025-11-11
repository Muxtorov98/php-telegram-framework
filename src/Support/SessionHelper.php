<?php
namespace App\Support;

/**
 * 🧩 SessionHelper
 * PHP sessiyasini xavfsiz boshqaruvchi klass
 * - CLI (bot, cron) uchun xavfsiz
 * - Web (HTTP) uchun to‘liq mos
 */
final class SessionHelper
{
    /**
     * 🔑 Sessiyani xavfsiz boshlash
     */
    public static function start(): void
    {
        // Agar sessiya allaqachon boshlangan bo‘lsa — chiqamiz
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // CLI rejimda (php run.php)
        if (php_sapi_name() === 'cli') {
            // CLI rejimda header bo‘lmaydi, shuning uchun xatoni yo‘q qilamiz
            @session_start([
                'cookie_lifetime' => 0,
                'read_and_close' => false,
                'use_cookies' => false,
                'use_only_cookies' => false,
                'use_strict_mode' => false
            ]);
        } else {
            // HTTP rejim (web-app)
            if (headers_sent() === false) {
                session_start();
            } else {
                // fallback
                @session_start();
            }
        }
    }

    /**
     * 🔐 Sessiya o‘rnatilganmi?
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * 🧹 Sessiyani tozalash (logout uchun)
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
    }
}

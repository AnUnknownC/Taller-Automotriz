<?php
declare(strict_types=1);

namespace App\Core;

/**
 * RS-03: sesión segura.
 * - Cookie HttpOnly + SameSite=Lax (+ Secure cuando hay HTTPS).
 * - Modo estricto: PHP rechaza IDs de sesión que no generó.
 * - Expiración por inactividad (SESSION_LIFETIME en minutos).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_name('TALLER_SID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        self::checkInactivity();
    }

    private static function checkInactivity(): void
    {
        $limit = (int) Config::get('app.session_lifetime', 20) * 60;
        $last = $_SESSION['_last_activity'] ?? null;

        if ($last !== null && (time() - (int) $last) > $limit) {
            $wasLogged = isset($_SESSION['user_id']);
            $_SESSION = [];
            session_regenerate_id(true);
            if ($wasLogged) {
                self::flash('warning', 'Tu sesión expiró por inactividad. Ingresa de nuevo.');
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    /** Nuevo ID tras autenticarse: evita fijación de sesión. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
        session_destroy();
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /** @param mixed $value */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Mensaje de un solo uso (se muestra en la siguiente página). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlashes(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}

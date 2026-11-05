<?php
declare(strict_types=1);

namespace App\Core;

/** Datos de la petición HTTP actual. */
final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Ruta interna sin la carpeta del proyecto: /taller/login -> /login */
    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        foreach ([self::publicBase(), self::baseUrl()] as $base) {
            if ($base !== '' && strpos($path, $base) === 0) {
                $path = substr($path, strlen($base));
                break;
            }
        }
        if ($path === '/index.php') {
            $path = '/';
        }
        $path = '/' . trim($path, '/');
        return $path;
    }

    /** Carpeta donde vive index.php, p. ej. /taller/public */
    private static function publicBase(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        return rtrim($dir, '/');
    }

    /** URL base pública, p. ej. /taller (el .htaccess raíz oculta /public). */
    public static function baseUrl(): string
    {
        $base = self::publicBase();
        if (substr($base, -7) === '/public') {
            $base = substr($base, 0, -7);
        }
        return $base;
    }

    /** @return mixed */
    public static function input(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public static function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;
    }

    public static function ip(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}

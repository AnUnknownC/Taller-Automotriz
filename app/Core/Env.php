<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Lee el archivo .env (fuera de public/ y fuera del repositorio). */
final class Env
{
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new RuntimeException('No existe el archivo .env. Copia .env.example como .env y ajústalo.');
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            $quoted = strlen($value) >= 2
                && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"));
            self::$vars[trim($key)] = $quoted ? substr($value, 1, -1) : $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$vars[$key] ?? $default;
    }
}

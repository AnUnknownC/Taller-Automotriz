<?php
declare(strict_types=1);

namespace App\Core;

/** Acceso a config/app.php con notación de puntos: Config::get('db.host'). */
final class Config
{
    private static array $items = [];

    public static function load(string $file): void
    {
        self::$items = require $file;
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

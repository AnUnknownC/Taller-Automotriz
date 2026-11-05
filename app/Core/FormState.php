<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Datos y errores de un formulario rechazado, disponibles SOLO en la siguiente petición.
 * Patrón Post/Redirect/Get: POST inválido -> redirige al formulario -> se repinta con errores.
 */
final class FormState
{
    private static array $old = [];
    private static array $errors = [];
    private static bool $hasOld = false;

    /** Se llama una vez por petición (bootstrap): mueve los datos de la sesión a memoria. */
    public static function load(): void
    {
        self::$hasOld = isset($_SESSION['_old']);
        self::$old = $_SESSION['_old'] ?? [];
        self::$errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }

    public static function flash(array $old, array $errors): void
    {
        $_SESSION['_old'] = $old;
        $_SESSION['_errors'] = $errors;
    }

    public static function hasOld(): bool
    {
        return self::$hasOld;
    }

    /** @return mixed */
    public static function old(string $key)
    {
        return self::$old[$key] ?? null;
    }

    public static function error(string $key): ?string
    {
        return self::$errors[$key] ?? null;
    }
}

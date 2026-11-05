<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/**
 * Estado de autenticación de la petición actual.
 * En sesión solo se guarda el ID; rol y estado se releen de la BD en cada petición,
 * así un usuario desactivado o con rol cambiado pierde el acceso de inmediato.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function login(array $user): void
    {
        Session::regenerate();   // RS-03: evita fijación de sesión
        Csrf::rotate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
        Session::start();        // sesión nueva y vacía para el mensaje de despedida
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            self::$user = $id ? (new Usuario())->findActive((int) $id) : null;
            if ($id && self::$user === null) {
                Session::remove('user_id');   // usuario desactivado o eliminado
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['rol'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }
}

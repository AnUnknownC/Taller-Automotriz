<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\Auditoria;
use App\Models\IntentoLogin;
use App\Models\Usuario;

/**
 * Caso de uso "iniciar sesión".
 * RF-01 / RS-01 / RS-07:
 *  - password_verify() en servidor; rehash automático a Argon2id si está disponible.
 *  - Mensaje genérico: nunca revela si el correo existe o si la cuenta está inactiva.
 *  - Bloqueo progresivo: desde el 5.º fallo, espera de 1, 2, 4, 8... minutos (máx. 15).
 *  - Límite por IP: 30 fallos en 15 minutos bloquea esa IP.
 */
final class AuthService
{
    public const MENSAJE_GENERICO = 'Correo o contraseña incorrectos.';

    /** Hash bcrypt de un texto aleatorio que no corresponde a ninguna cuenta. */
    private const HASH_FICTICIO = '$2y$10$hvxQregfdgjQEzJKSiXDJeGZGctxokNxYUTaJRHZdoJq0mh.GyLb6';

    private const FALLOS_PERMITIDOS = 5;
    private const ESPERA_BASE_SEG   = 60;
    private const ESPERA_MAX_SEG    = 900;
    private const VENTANA_MIN       = 30;
    private const FALLOS_POR_IP     = 30;
    private const VENTANA_IP_MIN    = 15;

    private Usuario $usuarios;
    private IntentoLogin $intentos;
    private Auditoria $auditoria;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->intentos = new IntentoLogin();
        $this->auditoria = new Auditoria();
    }

    /** Algoritmo de hash del proyecto: Argon2id si el PHP lo trae, si no bcrypt. */
    public static function algoritmo()
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    /** RS-01: único punto donde se genera un hash de contraseña. */
    public static function hash(string $password): string
    {
        return password_hash($password, self::algoritmo());
    }

    /** @return array{ok:bool, error?:string} */
    public function intentar(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));

        $espera = $this->segundosDeBloqueo($email, $ip);
        if ($espera > 0) {
            $minutos = (int) ceil($espera / 60);
            return ['ok' => false, 'error' => "Demasiados intentos fallidos. Espera $minutos minuto(s) e intenta de nuevo."];
        }

        $usuario = $this->usuarios->findByEmailForLogin($email);

        // Se verifica contra un hash ficticio si el correo no existe: mismo tiempo de respuesta
        $hash = $usuario['password_hash'] ?? self::HASH_FICTICIO;
        $valida = password_verify($password, $hash);

        if (!$usuario || !$valida || (int) $usuario['activo'] !== 1) {
            $this->intentos->registrar($email, $ip, false);
            return ['ok' => false, 'error' => self::MENSAJE_GENERICO];
        }

        if (password_needs_rehash($usuario['password_hash'], self::algoritmo())) {
            $this->usuarios->updatePasswordHash((int) $usuario['id'], self::hash($password));
        }

        $this->intentos->registrar($email, $ip, true);
        unset($usuario['password_hash']);
        Auth::login($usuario);
        $this->auditoria->registrar((int) $usuario['id'], 'inicio_sesion', 'usuario', (int) $usuario['id']);

        return ['ok' => true];
    }

    /** Segundos que faltan para poder intentar de nuevo (0 = puede intentar). */
    private function segundosDeBloqueo(string $email, string $ip): int
    {
        if ($this->intentos->fallosRecientesPorIp($ip, self::VENTANA_IP_MIN) >= self::FALLOS_POR_IP) {
            return self::VENTANA_IP_MIN * 60;
        }

        $estado = $this->intentos->fallosRecientesPorEmail($email, self::VENTANA_MIN);
        if ($estado['fallos'] < self::FALLOS_PERMITIDOS) {
            return 0;
        }

        $exceso = $estado['fallos'] - self::FALLOS_PERMITIDOS;
        $espera = min(self::ESPERA_BASE_SEG * (2 ** $exceso), self::ESPERA_MAX_SEG);
        return max(0, $espera - $estado['segundos']);
    }
}

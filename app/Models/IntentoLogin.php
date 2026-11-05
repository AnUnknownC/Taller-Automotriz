<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** RS-07: registro de intentos de inicio de sesión (incluye correos que no existen). */
final class IntentoLogin extends Model
{
    public function registrar(string $email, string $ip, bool $exitoso): void
    {
        $this->query(
            'INSERT INTO intentos_login (email, ip, exitoso) VALUES (:email, :ip, :exitoso)',
            ['email' => $email, 'ip' => $ip, 'exitoso' => $exitoso ? 1 : 0]
        );
    }

    /**
     * Fallos del correo desde su último acceso exitoso, dentro de la ventana.
     * El tiempo se calcula en la BD (NOW()) para no depender de la zona horaria de PHP.
     * @return array{fallos:int, segundos:int}
     */
    public function fallosRecientesPorEmail(string $email, int $ventanaMin): array
    {
        $row = $this->fetch(
            'SELECT COUNT(*) AS fallos,
                    COALESCE(TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()), 0) AS segundos
             FROM intentos_login
             WHERE email = :email AND exitoso = 0
               AND created_at > GREATEST(
                     COALESCE((SELECT MAX(created_at) FROM intentos_login
                               WHERE email = :email_ok AND exitoso = 1), \'1970-01-01\'),
                     NOW() - INTERVAL :ventana MINUTE)',
            ['email' => $email, 'email_ok' => $email, 'ventana' => $ventanaMin]
        );
        return ['fallos' => (int) $row['fallos'], 'segundos' => (int) $row['segundos']];
    }

    public function fallosRecientesPorIp(string $ip, int $ventanaMin): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM intentos_login
             WHERE ip = :ip AND exitoso = 0 AND created_at > NOW() - INTERVAL :ventana MINUTE',
            ['ip' => $ip, 'ventana' => $ventanaMin]
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Request;

/** RF-11: quién hizo qué, sobre qué registro y cuándo. Nunca guarda secretos. */
final class Auditoria extends Model
{
    private const CAMPOS_SECRETOS = ['password', 'password_hash', 'password_confirm',
                                      'acceso_password', 'acceso_password_confirm', '_csrf'];

    public function registrar(?int $usuarioId, string $accion, string $entidad, ?int $entidadId = null,
                              ?array $antes = null, ?array $despues = null): void
    {
        $this->query(
            'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, datos_antes, datos_despues, ip)
             VALUES (:usuario, :accion, :entidad, :entidad_id, :antes, :despues, :ip)',
            [
                'usuario'    => $usuarioId,
                'accion'     => $accion,
                'entidad'    => $entidad,
                'entidad_id' => $entidadId,
                'antes'      => $this->json($antes),
                'despues'    => $this->json($despues),
                'ip'         => Request::ip(),
            ]
        );
    }

    private function json(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }
        foreach (self::CAMPOS_SECRETOS as $campo) {
            unset($data[$campo]);
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * RF-08: listado con filtro, búsqueda, orden y paginación.
     * El ORDER BY usa una lista blanca: nunca se concatena texto del usuario en el SQL.
     */
    public function buscar(string $accion, string $q, string $orden, int $pagina, int $porPagina): array
    {
        [$where, $params] = $this->filtros($accion, $q);
        $direccion = $orden === 'asc' ? 'ASC' : 'DESC';

        $total = (int) $this->fetchValue(
            "SELECT COUNT(*) FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id $where",
            $params
        );

        $filas = $this->fetchAll(
            "SELECT a.id, a.accion, a.entidad, a.entidad_id, a.datos_despues, a.ip, a.created_at,
                    u.nombre AS usuario, u.email
             FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id
             $where
             ORDER BY a.created_at $direccion, a.id $direccion
             LIMIT :limite OFFSET :offset",
            $params + ['limite' => $porPagina, 'offset' => ($pagina - 1) * $porPagina]
        );

        return ['total' => $total, 'filas' => $filas];
    }

    private function filtros(string $accion, string $q): array
    {
        $condiciones = [];
        $params = [];
        if ($accion !== '') {
            $condiciones[] = 'a.accion = :accion';
            $params['accion'] = $accion;
        }
        if ($q !== '') {
            $condiciones[] = '(u.nombre LIKE :q1 OR u.email LIKE :q2 OR a.ip LIKE :q3)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        return [$condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '', $params];
    }

    /** Cuántas veces se registró una acción desde una IP en los últimos N minutos (límite anti-abuso). */
    public function contarRecientesPorIp(string $accion, string $ip, int $minutos): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM auditoria WHERE accion = :accion AND ip = :ip AND created_at > NOW() - INTERVAL :minutos MINUTE',
            ['accion' => $accion, 'ip' => $ip, 'minutos' => $minutos]
        );
    }

    public function accionesDistintas(): array
    {
        return array_column($this->fetchAll('SELECT DISTINCT accion FROM auditoria ORDER BY accion'), 'accion');
    }

    public function recientes(int $limite = 5): array
    {
        return $this->fetchAll(
            'SELECT a.accion, a.entidad, a.created_at, u.nombre AS usuario
             FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT :limite',
            ['limite' => $limite]
        );
    }
}

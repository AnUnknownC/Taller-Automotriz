<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

final class Usuario extends Model
{
    /** Roles que se crean desde el módulo Usuarios. Las cuentas de cliente nacen en el módulo Clientes. */
    public const ROLES_INTERNOS = ['administrador', 'asesor', 'tecnico'];
    public const ROLES = ['administrador', 'asesor', 'tecnico', 'cliente'];

    /** Lista blanca de columnas ordenables: clave de la URL => columna SQL. */
    private const ORDEN = [
        'nombre' => 'u.nombre',
        'email'  => 'u.email',
        'rol'    => 'r.nombre',
        'creado' => 'u.created_at',
    ];

    private const SELECT = 'SELECT u.id, u.rol_id, r.nombre AS rol, u.nombre, u.email, u.activo, u.created_at
                            FROM usuarios u JOIN roles r ON r.id = u.rol_id';

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    public function listar(ListParams $p, string $rol, string $estado): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(u.nombre LIKE :q1 OR u.email LIKE :q2)';
            $params += ['q1' => $this->like($p->q), 'q2' => $this->like($p->q)];
        }
        if ($rol !== '') {
            $where[] = 'r.nombre = :rol';
            $params['rol'] = $rol;
        }
        if ($estado !== 'todos') {
            $where[] = 'u.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }

        return $this->paginate(
            'SELECT u.id, u.nombre, u.email, u.activo, u.created_at, r.nombre AS rol',
            'FROM usuarios u JOIN roles r ON r.id = u.rol_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', u.id',
            $p
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE u.id = :id', ['id' => $id]);
    }

    public function findActive(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE u.id = :id AND u.activo = 1', ['id' => $id]);
    }

    /** Incluye el hash: solo para verificar la contraseña en AuthService. */
    public function findByEmailForLogin(string $email): ?array
    {
        return $this->fetch(
            'SELECT u.id, u.nombre, u.email, u.password_hash, u.activo, r.nombre AS rol
             FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE u.email = :email LIMIT 1',
            ['email' => $email]
        );
    }

    public function emailEnUso(string $email, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM usuarios WHERE email = :email AND id <> :excepto',
            ['email' => $email, 'excepto' => $exceptoId ?? 0]
        );
    }

    /** @param array{nombre:string,email:string,rol:string,password_hash:string} $d */
    public function crear(array $d): int
    {
        $this->query(
            'INSERT INTO usuarios (rol_id, nombre, email, password_hash)
             VALUES ((SELECT id FROM roles WHERE nombre = :rol), :nombre, :email, :hash)',
            ['rol' => $d['rol'], 'nombre' => $d['nombre'], 'email' => $d['email'], 'hash' => $d['password_hash']]
        );
        return (int) $this->db->lastInsertId();
    }

    /** Si password_hash viene vacío, se conserva la contraseña actual. */
    public function actualizar(int $id, array $d): void
    {
        $this->query(
            'UPDATE usuarios SET nombre = :nombre, email = :email,
                    rol_id = (SELECT id FROM roles WHERE nombre = :rol)
             WHERE id = :id',
            ['nombre' => $d['nombre'], 'email' => $d['email'], 'rol' => $d['rol'], 'id' => $id]
        );
        if (!empty($d['password_hash'])) {
            $this->updatePasswordHash($id, $d['password_hash']);
        }
    }

    public function actualizarContacto(int $id, string $nombre, string $email): void
    {
        $this->query('UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id',
            ['nombre' => $nombre, 'email' => $email, 'id' => $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->query('UPDATE usuarios SET activo = :activo WHERE id = :id', ['activo' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->query('UPDATE usuarios SET password_hash = :hash WHERE id = :id', ['hash' => $hash, 'id' => $id]);
    }

    /** Técnicos activos con su carga actual (órdenes abiertas asignadas). */
    public function tecnicosActivos(): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.nombre,
                    (SELECT COUNT(*) FROM ordenes_trabajo o
                     WHERE o.tecnico_id = u.id AND o.estado NOT IN ('entregada','rechazada')) AS carga
             FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE r.nombre = 'tecnico' AND u.activo = 1 ORDER BY u.nombre"
        );
    }

    public function contarAdminsActivos(): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE r.nombre = 'administrador' AND u.activo = 1"
        );
    }

    public function contarActivos(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM usuarios WHERE activo = 1');
    }

    /** @return array<string,int> rol => total de usuarios activos */
    public function contarActivosPorRol(): array
    {
        $rows = $this->fetchAll(
            'SELECT r.nombre AS rol, COUNT(u.id) AS total
             FROM roles r LEFT JOIN usuarios u ON u.rol_id = r.id AND u.activo = 1
             GROUP BY r.id, r.nombre ORDER BY r.id'
        );
        return array_column($rows, 'total', 'rol');
    }
}

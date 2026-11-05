<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

final class Proveedor extends Model
{
    private const ORDEN = ['nombre' => 'p.nombre', 'nit' => 'p.nit', 'entradas' => 'entradas', 'ultima' => 'ultima'];

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    public function listar(ListParams $p, string $estado): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(p.nombre LIKE :q1 OR p.nit LIKE :q2 OR p.email LIKE :q3)';
            $like = $this->like($p->q);
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if ($estado !== 'todos') {
            $where[] = 'p.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }
        return $this->paginate(
            "SELECT p.id, p.nit, p.nombre, p.telefono, p.email, p.activo,
                    (SELECT COUNT(*) FROM movimientos_inventario m WHERE m.proveedor_id = p.id) AS entradas,
                    (SELECT MAX(m.created_at) FROM movimientos_inventario m WHERE m.proveedor_id = p.id) AS ultima",
            'FROM proveedores p' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', p.id',
            $p
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM proveedores WHERE id = :id', ['id' => $id]);
    }

    public function nitEnUso(string $nit, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue('SELECT COUNT(*) FROM proveedores WHERE nit = :nit AND id <> :excepto',
            ['nit' => $nit, 'excepto' => $exceptoId ?? 0]);
    }

    public function crear(array $d): int
    {
        $this->query('INSERT INTO proveedores (nit, nombre, telefono, email) VALUES (:nit, :nombre, :tel, :email)', $this->params($d));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->query('UPDATE proveedores SET nit = :nit, nombre = :nombre, telefono = :tel, email = :email WHERE id = :id',
            $this->params($d) + ['id' => $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->query('UPDATE proveedores SET activo = :a WHERE id = :id', ['a' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function activosParaSelect(): array
    {
        return $this->fetchAll('SELECT id, nombre, nit FROM proveedores WHERE activo = 1 ORDER BY nombre');
    }

    private function params(array $d): array
    {
        return ['nit' => $d['nit'], 'nombre' => $d['nombre'],
                'tel' => $d['telefono'] !== '' ? $d['telefono'] : null, 'email' => $d['email'] !== '' ? $d['email'] : null];
    }
}

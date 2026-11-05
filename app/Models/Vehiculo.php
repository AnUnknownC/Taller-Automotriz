<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

final class Vehiculo extends Model
{
    /** Placa colombiana: carro ABC123 o moto ABC12D. */
    public const PATRON_PLACA = '/^[A-Z]{3}[0-9]{2}[0-9A-Z]$/';
    /** VIN: 17 caracteres, sin I, O ni Q (norma ISO 3779). */
    public const PATRON_VIN = '/^[A-HJ-NPR-Z0-9]{17}$/';

    private const ORDEN = [
        'placa'   => 'v.placa',
        'marca'   => 'v.marca',
        'anio'    => 'v.anio',
        'cliente' => 'c.nombre',
        'creado'  => 'v.created_at',
    ];

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    public function listar(ListParams $p, string $estado): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(v.placa LIKE :q1 OR v.marca LIKE :q2 OR v.modelo LIKE :q3 OR c.nombre LIKE :q4)';
            $like = $this->like($p->q);
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        if ($estado !== 'todos') {
            $where[] = 'v.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }

        return $this->paginate(
            'SELECT v.id, v.placa, v.marca, v.modelo, v.anio, v.color, v.activo, v.cliente_id, c.nombre AS cliente',
            'FROM vehiculos v JOIN clientes c ON c.id = v.cliente_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', v.id',
            $p
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT v.*, c.nombre AS cliente, c.activo AS cliente_activo, c.telefono AS cliente_telefono
             FROM vehiculos v JOIN clientes c ON c.id = v.cliente_id WHERE v.id = :id',
            ['id' => $id]
        );
    }

    public function findByPlaca(string $placa): ?array
    {
        return $this->fetch(
            'SELECT v.*, c.nombre AS cliente, c.activo AS cliente_activo, c.telefono AS cliente_telefono
             FROM vehiculos v JOIN clientes c ON c.id = v.cliente_id WHERE v.placa = :placa',
            ['placa' => $placa]
        );
    }

    public function placaEnUso(string $placa, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue('SELECT COUNT(*) FROM vehiculos WHERE placa = :placa AND id <> :excepto',
            ['placa' => $placa, 'excepto' => $exceptoId ?? 0]);
    }

    public function vinEnUso(string $vin, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue('SELECT COUNT(*) FROM vehiculos WHERE vin = :vin AND id <> :excepto',
            ['vin' => $vin, 'excepto' => $exceptoId ?? 0]);
    }

    public function crear(array $d): int
    {
        $this->query(
            'INSERT INTO vehiculos (cliente_id, placa, marca, modelo, anio, color, vin)
             VALUES (:cliente, :placa, :marca, :modelo, :anio, :color, :vin)',
            $this->params($d)
        );
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->query(
            'UPDATE vehiculos SET cliente_id = :cliente, placa = :placa, marca = :marca, modelo = :modelo,
                    anio = :anio, color = :color, vin = :vin
             WHERE id = :id',
            $this->params($d) + ['id' => $id]
        );
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->query('UPDATE vehiculos SET activo = :activo WHERE id = :id', ['activo' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function ordenesAbiertas(int $id): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM ordenes_trabajo WHERE vehiculo_id = :id AND " . OrdenTrabajo::sqlAbiertas(),
            ['id' => $id]
        );
    }

    /** Todos los vehículos de un cliente (activos e inactivos), para su ficha. */
    public function deCliente(int $clienteId): array
    {
        return $this->fetchAll(
            'SELECT id, placa, marca, modelo, anio, color, activo FROM vehiculos
             WHERE cliente_id = :cliente ORDER BY activo DESC, placa',
            ['cliente' => $clienteId]
        );
    }

    /** Vehículos activos del cliente vinculado a este usuario. */
    public function deUsuarioCliente(int $usuarioId): array
    {
        return $this->fetchAll(
            'SELECT v.id, v.placa, v.marca, v.modelo, v.anio, v.color
             FROM vehiculos v JOIN clientes c ON c.id = v.cliente_id
             WHERE c.usuario_id = :usuario AND v.activo = 1
             ORDER BY v.placa',
            ['usuario' => $usuarioId]
        );
    }

    private function params(array $d): array
    {
        return [
            'cliente' => (int) $d['cliente_id'],
            'placa'   => $d['placa'],
            'marca'   => $d['marca'],
            'modelo'  => $d['modelo'],
            'anio'    => (int) $d['anio'],
            'color'   => $d['color'] !== '' ? $d['color'] : null,
            'vin'     => $d['vin'] !== '' ? $d['vin'] : null,
        ];
    }
}

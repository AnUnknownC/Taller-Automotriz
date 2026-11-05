<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

/**
 * Catálogo de repuestos. El campo `stock` NO se edita desde aquí:
 * solo cambia mediante movimientos (InventarioService y OrdenService).
 */
final class Repuesto extends Model
{
    /** Código interno: letras, números y guiones (ej. FRN-PAST-DEL). */
    public const PATRON_CODIGO = '/^[A-Z0-9][A-Z0-9\-]{2,29}$/';

    private const ORDEN = [
        'codigo' => 'r.codigo',
        'nombre' => 'r.nombre',
        'stock'  => 'r.stock',
        'precio' => 'r.precio_venta',
    ];

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    public function listar(ListParams $p, string $estado, bool $soloBajo): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(r.codigo LIKE :q1 OR r.nombre LIKE :q2 OR r.marca LIKE :q3)';
            $like = $this->like($p->q);
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if ($estado !== 'todos') {
            $where[] = 'r.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }
        if ($soloBajo) {
            $where[] = 'r.stock <= r.stock_minimo';
        }
        return $this->paginate(
            'SELECT r.id, r.codigo, r.nombre, r.marca, r.costo, r.precio_venta, r.stock, r.stock_minimo, r.activo',
            'FROM repuestos r' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', r.id',
            $p
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM repuestos WHERE id = :id', ['id' => $id]);
    }

    public function codigoEnUso(string $codigo, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue('SELECT COUNT(*) FROM repuestos WHERE codigo = :c AND id <> :excepto',
            ['c' => $codigo, 'excepto' => $exceptoId ?? 0]);
    }

    /** Nace con stock 0: las existencias entran con una entrada de mercancía o un ajuste. */
    public function crear(array $d): int
    {
        $this->query(
            'INSERT INTO repuestos (codigo, nombre, marca, costo, precio_venta, stock, stock_minimo)
             VALUES (:codigo, :nombre, :marca, :costo, :precio, 0, :minimo)',
            $this->params($d)
        );
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->query(
            'UPDATE repuestos SET codigo = :codigo, nombre = :nombre, marca = :marca, costo = :costo,
                    precio_venta = :precio, stock_minimo = :minimo WHERE id = :id',
            $this->params($d) + ['id' => $id]
        );
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->query('UPDATE repuestos SET activo = :a WHERE id = :id', ['a' => $activo ? 1 : 0, 'id' => $id]);
    }

    /** Órdenes que aún van a consumir este repuesto (cotizado y sin iniciar reparación). */
    public function ordenesPendientes(int $id): array
    {
        return $this->fetchAll(
            "SELECT DISTINCT o.id, o.codigo FROM cotizacion_items i
             JOIN cotizaciones q ON q.id = i.cotizacion_id
             JOIN ordenes_trabajo o ON o.id = q.orden_id
             WHERE i.repuesto_id = :id AND o.estado IN ('cotizada', 'aprobada')",
            ['id' => $id]
        );
    }

    /** Catálogo para el editor de cotizaciones. */
    public function activosParaCotizar(): array
    {
        return $this->fetchAll(
            'SELECT id, codigo, nombre, precio_venta, stock FROM repuestos WHERE activo = 1 ORDER BY nombre'
        );
    }

    public function activosParaSelect(): array
    {
        return $this->fetchAll('SELECT id, codigo, nombre, stock, costo FROM repuestos WHERE activo = 1 ORDER BY nombre');
    }

    /** @return array<int,array> repuestos activos indexados por id */
    public function activosPorId(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        // Un marcador por id: la lista nunca se concatena como texto
        $marcas = [];
        $params = [];
        foreach ($ids as $n => $id) {
            $marcas[] = ":id$n";
            $params["id$n"] = $id;
        }
        $filas = $this->fetchAll(
            'SELECT id, codigo, nombre, precio_venta, stock FROM repuestos
             WHERE activo = 1 AND id IN (' . implode(',', $marcas) . ')',
            $params
        );
        return array_column($filas, null, 'id');
    }

    /** Repuestos activos en o por debajo del stock mínimo (alerta de inventario). */
    public function bajoStock(int $limite = 5): array
    {
        return $this->fetchAll(
            'SELECT id, codigo, nombre, stock, stock_minimo FROM repuestos
             WHERE activo = 1 AND stock <= stock_minimo
             ORDER BY (stock - stock_minimo) ASC, nombre LIMIT :limite',
            ['limite' => $limite]
        );
    }

    public function contarBajoStock(): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM repuestos WHERE activo = 1 AND stock <= stock_minimo'
        );
    }

    private function params(array $d): array
    {
        return [
            'codigo' => $d['codigo'], 'nombre' => $d['nombre'], 'marca' => $d['marca'] !== '' ? $d['marca'] : null,
            'costo' => (string) $d['costo'], 'precio' => (string) $d['precio_venta'], 'minimo' => (int) $d['stock_minimo'],
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

/**
 * RF-09: indicadores y reportes calculados con consultas agregadas (JOIN, GROUP BY, AVG, SUM).
 * Ninguna cifra es inventada: todo sale de las tablas operativas.
 * $f = ['desde' => 'Y-m-d', 'hasta' => 'Y-m-d', 'estado' => '', 'tecnico' => 0]
 */
final class Reporte extends Model
{
    private const ORDEN_ORDENES = [
        'ingreso' => 'o.fecha_ingreso',
        'codigo'  => 'o.codigo',
        'dias'    => 'horas',
        'total'   => 'total',
    ];

    public static function columnasOrdenOrdenes(): array
    {
        return array_keys(self::ORDEN_ORDENES);
    }

    // ------------------------------------------------------------------
    // Órdenes (por fecha de ingreso)
    // ------------------------------------------------------------------

    /** @return array{0:string,1:array} WHERE y parámetros comunes del reporte de órdenes */
    private function filtroOrdenes(array $f): array
    {
        $where = ['o.fecha_ingreso BETWEEN :desde AND :hasta'];
        $params = ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59'];
        if ($f['estado'] === 'abiertas') {
            $where[] = OrdenTrabajo::sqlAbiertas('o');
        } elseif ($f['estado'] !== '') {
            $where[] = 'o.estado = :estado';
            $params['estado'] = $f['estado'];
        }
        if ($f['tecnico'] === -1) {
            $where[] = 'o.tecnico_id IS NULL';
        } elseif ($f['tecnico'] > 0) {
            $where[] = 'o.tecnico_id = :tecnico';
            $params['tecnico'] = $f['tecnico'];
        }
        return [' WHERE ' . implode(' AND ', $where), $params];
    }

    /** Indicadores del periodo. */
    public function kpisOrdenes(array $f): array
    {
        [$where, $params] = $this->filtroOrdenes($f);
        $k = $this->fetch(
            "SELECT COUNT(*) AS total,
                    SUM(o.estado = 'entregada') AS entregadas,
                    SUM(o.estado <> 'entregada') AS abiertas,
                    AVG(CASE WHEN o.estado = 'entregada' AND q.estado = 'aprobada'
                             THEN TIMESTAMPDIFF(MINUTE, o.fecha_ingreso, o.fecha_entrega) END) / 60 AS horas_ciclo,
                    SUM(q.estado = 'aprobada') AS aprobadas,
                    SUM(q.estado = 'rechazada') AS rechazadas
             FROM ordenes_trabajo o LEFT JOIN cotizaciones q ON q.orden_id = o.id $where",
            $params
        );
        $respondidas = (int) $k['aprobadas'] + (int) $k['rechazadas'];
        $k['tasa_aprobacion'] = $respondidas > 0 ? (int) $k['aprobadas'] / $respondidas * 100 : null;
        return $k;
    }

    /** Listado del reporte. $p = null devuelve TODO (para CSV, con tope de seguridad). */
    public function ordenes(array $f, ?ListParams $p): array
    {
        [$where, $params] = $this->filtroOrdenes($f);
        $select = "SELECT o.id, o.codigo, o.estado, o.fecha_ingreso, o.fecha_entrega, v.placa, v.marca, v.modelo,
                          c.nombre AS cliente, t.nombre AS tecnico, a.nombre AS asesor,
                          TIMESTAMPDIFF(MINUTE, o.fecha_ingreso, COALESCE(o.fecha_entrega, NOW())) / 60 AS horas,
                          l.total, l.metodo_simulado";
        $from = "FROM ordenes_trabajo o
                 JOIN vehiculos v ON v.id = o.vehiculo_id
                 JOIN clientes c ON c.id = v.cliente_id
                 JOIN usuarios a ON a.id = o.asesor_id
                 LEFT JOIN usuarios t ON t.id = o.tecnico_id
                 LEFT JOIN liquidaciones l ON l.orden_id = o.id $where";
        if ($p === null) {
            return $this->fetchAll("$select $from ORDER BY o.fecha_ingreso DESC LIMIT 5000", $params);
        }
        return $this->paginate($select, $from, $params,
            self::ORDEN_ORDENES[$p->sort] . ' ' . strtoupper($p->dir) . ', o.id', $p);
    }

    /** Carga y desempeño por técnico en el periodo. */
    public function porTecnico(array $f): array
    {
        [$where, $params] = $this->filtroOrdenes(['tecnico' => 0] + $f);
        return $this->fetchAll(
            "SELECT COALESCE(t.nombre, 'Sin asignar') AS tecnico, COUNT(*) AS total,
                    SUM(o.estado = 'entregada') AS entregadas,
                    AVG(CASE WHEN o.estado = 'entregada' AND q.estado = 'aprobada'
                             THEN TIMESTAMPDIFF(MINUTE, o.fecha_ingreso, o.fecha_entrega) END) / 60 AS horas
             FROM ordenes_trabajo o
             LEFT JOIN usuarios t ON t.id = o.tecnico_id
             LEFT JOIN cotizaciones q ON q.orden_id = o.id $where
             GROUP BY o.tecnico_id, t.nombre ORDER BY total DESC",
            $params
        );
    }

    // ------------------------------------------------------------------
    // Ingresos simulados (por fecha de liquidación)
    // ------------------------------------------------------------------

    public function resumenIngresos(array $f): array
    {
        return $this->fetch(
            'SELECT COUNT(*) AS liquidaciones, COALESCE(SUM(total), 0) AS total, COALESCE(SUM(subtotal_mano_obra), 0) AS mano_obra,
                    COALESCE(SUM(subtotal_repuestos), 0) AS repuestos, COALESCE(SUM(impuestos), 0) AS iva, AVG(total) AS ticket
             FROM liquidaciones WHERE created_at BETWEEN :desde AND :hasta',
            ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59']
        );
    }

    /**
     * Serie de ingresos por día, semana o mes (según la longitud del periodo).
     * $agrupacion viene de una lista blanca del controlador.
     */
    public function ingresosPorPeriodo(array $f, string $agrupacion): array
    {
        $expr = [
            'dia'    => "DATE(created_at)",
            'semana' => "DATE(created_at - INTERVAL WEEKDAY(created_at) DAY)",
            'mes'    => "DATE_FORMAT(created_at, '%Y-%m-01')",
        ][$agrupacion];
        return $this->fetchAll(
            "SELECT $expr AS periodo, COUNT(*) AS liquidaciones, SUM(total) AS total
             FROM liquidaciones WHERE created_at BETWEEN :desde AND :hasta
             GROUP BY periodo ORDER BY periodo",
            ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59']
        );
    }

    public function ingresosPorMetodo(array $f): array
    {
        return $this->fetchAll(
            'SELECT metodo_simulado AS metodo, COUNT(*) AS liquidaciones, SUM(total) AS total
             FROM liquidaciones WHERE created_at BETWEEN :desde AND :hasta
             GROUP BY metodo_simulado ORDER BY total DESC',
            ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59']
        );
    }

    public function liquidaciones(array $f): array
    {
        return $this->fetchAll(
            "SELECT l.created_at, o.id AS orden_id, o.codigo, v.placa, c.nombre AS cliente, l.subtotal_mano_obra,
                    l.subtotal_repuestos, l.impuestos, l.total, l.metodo_simulado, u.nombre AS registrada_por,
                    (q.estado = 'rechazada') AS solo_diagnostico
             FROM liquidaciones l
             JOIN ordenes_trabajo o ON o.id = l.orden_id
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes c ON c.id = v.cliente_id
             JOIN usuarios u ON u.id = l.registrada_por
             LEFT JOIN cotizaciones q ON q.orden_id = o.id
             WHERE l.created_at BETWEEN :desde AND :hasta
             ORDER BY l.created_at DESC LIMIT 5000",
            ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59']
        );
    }

    // ------------------------------------------------------------------
    // Repuestos más usados (por fecha de salida de bodega)
    // ------------------------------------------------------------------

    public function repuestosUsados(array $f): array
    {
        return $this->fetchAll(
            "SELECT r.id, r.codigo, r.nombre, r.stock, r.stock_minimo, r.costo,
                    SUM(m.cantidad) AS unidades,
                    COUNT(DISTINCT m.orden_id) AS ordenes,
                    SUM(m.cantidad * COALESCE(
                        (SELECT i.precio_unitario FROM cotizacion_items i JOIN cotizaciones q ON q.id = i.cotizacion_id
                         WHERE q.orden_id = m.orden_id AND i.repuesto_id = m.repuesto_id LIMIT 1), r.precio_venta)) AS vendido
             FROM movimientos_inventario m JOIN repuestos r ON r.id = m.repuesto_id
             WHERE m.tipo = 'salida' AND m.created_at BETWEEN :desde AND :hasta
             GROUP BY r.id, r.codigo, r.nombre, r.stock, r.stock_minimo, r.costo
             ORDER BY unidades DESC, vendido DESC",
            ['desde' => $f['desde'] . ' 00:00:00', 'hasta' => $f['hasta'] . ' 23:59:59']
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;

final class Cotizacion extends Model
{
    public function deOrden(int $ordenId): ?array
    {
        return $this->fetch(
            'SELECT q.*, a.nombre AS elaborada_por_nombre, r.nombre AS respondida_por_nombre
             FROM cotizaciones q
             JOIN usuarios a ON a.id = q.elaborada_por
             LEFT JOIN usuarios r ON r.id = q.respondida_por
             WHERE q.orden_id = :orden',
            ['orden' => $ordenId]
        );
    }

    /** Ítems con el stock actual del repuesto (para avisar si no alcanza). */
    public function items(int $cotizacionId): array
    {
        return $this->fetchAll(
            'SELECT i.id, i.tipo, i.repuesto_id, i.descripcion, i.cantidad, i.precio_unitario,
                    r.codigo AS repuesto_codigo, r.stock AS repuesto_stock
             FROM cotizacion_items i LEFT JOIN repuestos r ON r.id = i.repuesto_id
             WHERE i.cotizacion_id = :id ORDER BY i.tipo DESC, i.id',
            ['id' => $cotizacionId]
        );
    }

    /**
     * Crea la cotización o reemplaza sus ítems (solo mientras está pendiente).
     * Se llama dentro de la transacción de OrdenService.
     */
    public function guardar(int $ordenId, int $asesorId, array $items): int
    {
        $actual = $this->deOrden($ordenId);
        if ($actual) {
            $id = (int) $actual['id'];
            $this->query('DELETE FROM cotizacion_items WHERE cotizacion_id = :id', ['id' => $id]);
            $this->query('UPDATE cotizaciones SET elaborada_por = :asesor WHERE id = :id', ['asesor' => $asesorId, 'id' => $id]);
        } else {
            $this->query('INSERT INTO cotizaciones (orden_id, elaborada_por) VALUES (:orden, :asesor)',
                ['orden' => $ordenId, 'asesor' => $asesorId]);
            $id = (int) $this->db->lastInsertId();
        }

        foreach ($items as $item) {
            $this->query(
                'INSERT INTO cotizacion_items (cotizacion_id, tipo, repuesto_id, descripcion, cantidad, precio_unitario)
                 VALUES (:cot, :tipo, :repuesto, :desc, :cant, :precio)',
                [
                    'cot'      => $id,
                    'tipo'     => $item['tipo'],
                    'repuesto' => $item['repuesto_id'],
                    'desc'     => $item['descripcion'],
                    'cant'     => (string) $item['cantidad'],
                    'precio'   => (string) $item['precio_unitario'],
                ]
            );
        }
        return $id;
    }

    /**
     * Totales de la cotización. El IVA es un cálculo SIMULADO (config: TALLER_IVA).
     * @return array{mano_obra:float, repuestos:float, subtotal:float, iva:float, total:float, tasa:float}
     */
    public static function totales(array $items): array
    {
        $manoObra = 0.0;
        $repuestos = 0.0;
        foreach ($items as $i) {
            $valor = round((float) $i['cantidad'] * (float) $i['precio_unitario'], 2);
            $i['tipo'] === 'mano_obra' ? $manoObra += $valor : $repuestos += $valor;
        }
        $tasa = (float) Config::get('taller.iva', 0.19);
        $subtotal = $manoObra + $repuestos;
        $iva = round($subtotal * $tasa, 2);
        return ['mano_obra' => $manoObra, 'repuestos' => $repuestos, 'subtotal' => $subtotal,
                'iva' => $iva, 'total' => $subtotal + $iva, 'tasa' => $tasa];
    }

    /** Registra la decisión del cliente. Solo actúa si sigue pendiente (evita doble respuesta). */
    public function responder(int $id, string $estado, int $usuarioId, string $canal, ?string $motivo): bool
    {
        return $this->query(
            "UPDATE cotizaciones SET estado = :estado, respondida_por = :usuario, respondida_en = NOW(),
                    canal_respuesta = :canal, motivo_rechazo = :motivo
             WHERE id = :id AND estado = 'pendiente'",
            ['estado' => $estado, 'usuario' => $usuarioId, 'canal' => $canal, 'motivo' => $motivo, 'id' => $id]
        )->rowCount() === 1;
    }

    /** Órdenes con cotización pendiente de este cliente (para su tablero). */
    public function pendientesDeUsuarioCliente(int $usuarioId): array
    {
        return $this->fetchAll(
            "SELECT o.id, o.codigo, v.placa FROM cotizaciones q
             JOIN ordenes_trabajo o ON o.id = q.orden_id
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes c ON c.id = v.cliente_id
             WHERE q.estado = 'pendiente' AND c.usuario_id = :usuario ORDER BY o.id",
            ['usuario' => $usuarioId]
        );
    }

    public function contarPendientes(): int
    {
        return (int) $this->fetchValue("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'pendiente'");
    }

    /** Cotizaciones que esperan la respuesta de este cliente. */
    public function contarPendientesDeUsuarioCliente(int $usuarioId): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM cotizaciones q
             JOIN ordenes_trabajo o ON o.id = q.orden_id
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes c ON c.id = v.cliente_id
             WHERE q.estado = 'pendiente' AND c.usuario_id = :usuario",
            ['usuario' => $usuarioId]
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Liquidación SIMULADA de la orden: no procesa pagos ni es una factura legal. */
final class Liquidacion extends Model
{
    public const METODOS = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];

    public function deOrden(int $ordenId): ?array
    {
        return $this->fetch(
            'SELECT l.*, u.nombre AS registrada_por_nombre
             FROM liquidaciones l JOIN usuarios u ON u.id = l.registrada_por WHERE l.orden_id = :orden',
            ['orden' => $ordenId]
        );
    }

    public function insertar(int $ordenId, array $t, string $metodo, int $usuarioId): void
    {
        $this->query(
            'INSERT INTO liquidaciones (orden_id, subtotal_mano_obra, subtotal_repuestos, impuestos, total, metodo_simulado, registrada_por)
             VALUES (:orden, :mo, :rep, :iva, :total, :metodo, :usuario)',
            [
                'orden' => $ordenId, 'mo' => (string) $t['mano_obra'], 'rep' => (string) $t['repuestos'],
                'iva' => (string) $t['iva'], 'total' => (string) $t['total'], 'metodo' => $metodo, 'usuario' => $usuarioId,
            ]
        );
    }
}

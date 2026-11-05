<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MovimientoInventario extends Model
{
    public const TIPOS = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'];

    /** Efecto del movimiento sobre el stock (las salidas restan). */
    public static function efecto(array $m): int
    {
        return $m['tipo'] === 'salida' ? -(int) $m['cantidad'] : (int) $m['cantidad'];
    }

    public function registrar(int $repuestoId, string $tipo, int $cantidad, int $usuarioId, ?string $motivo,
                              ?int $proveedorId = null, ?int $ordenId = null, ?float $costo = null): void
    {
        $this->query(
            'INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, costo_unitario, orden_id, proveedor_id, usuario_id, motivo)
             VALUES (:rep, :tipo, :cant, :costo, :orden, :prov, :usuario, :motivo)',
            ['rep' => $repuestoId, 'tipo' => $tipo, 'cant' => $cantidad, 'costo' => $costo !== null ? (string) $costo : null,
             'orden' => $ordenId, 'prov' => $proveedorId, 'usuario' => $usuarioId, 'motivo' => $motivo]
        );
    }

    /**
     * Kardex: últimos movimientos con el SALDO después de cada uno.
     * El saldo se reconstruye hacia atrás desde el stock actual (fuente de verdad).
     */
    public function kardex(int $repuestoId, int $stockActual, int $limite = 100): array
    {
        $filas = $this->fetchAll(
            'SELECT m.*, u.nombre AS usuario, p.nombre AS proveedor, o.codigo AS orden_codigo
             FROM movimientos_inventario m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             LEFT JOIN ordenes_trabajo o ON o.id = m.orden_id
             WHERE m.repuesto_id = :rep
             ORDER BY m.created_at DESC, m.id DESC LIMIT :limite',
            ['rep' => $repuestoId, 'limite' => $limite]
        );
        $saldo = $stockActual;
        foreach ($filas as &$f) {
            $f['efecto'] = self::efecto($f);
            $f['saldo'] = $saldo;
            $saldo -= $f['efecto'];
        }
        return $filas;
    }

    /** Suma de todos los movimientos: debe coincidir con el stock (prueba de integridad). */
    public function saldoCalculado(int $repuestoId): int
    {
        return (int) $this->fetchValue(
            "SELECT COALESCE(SUM(CASE tipo WHEN 'salida' THEN -cantidad ELSE cantidad END), 0)
             FROM movimientos_inventario WHERE repuesto_id = :rep",
            ['rep' => $repuestoId]
        );
    }
}

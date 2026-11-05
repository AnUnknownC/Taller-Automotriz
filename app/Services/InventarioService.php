<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\Proveedor;
use App\Models\Repuesto;
use PDO;
use Throwable;

/**
 * Movimientos de inventario. Regla: el stock SOLO cambia aquí (entradas y ajustes)
 * o en OrdenService::iniciarReparacion (salidas). Cada cambio deja su fila en el kardex.
 */
final class InventarioService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Entrada de mercancía de un proveedor: suma stock y registra el movimiento.
     * Si se informa el costo de compra, actualiza el costo del repuesto (último costo).
     */
    public function registrarEntrada(int $repuestoId, int $proveedorId, int $cantidad, ?float $costo, ?string $nota, int $usuarioId): array
    {
        $repuesto = (new Repuesto())->find($repuestoId);
        if (!$repuesto || (int) $repuesto['activo'] !== 1) {
            throw new ReglaNegocioException('Selecciona un repuesto activo.');
        }
        $proveedor = (new Proveedor())->find($proveedorId);
        if (!$proveedor || (int) $proveedor['activo'] !== 1) {
            throw new ReglaNegocioException('Selecciona un proveedor activo.');
        }
        if ($cantidad < 1 || $cantidad > 9999) {
            throw new ReglaNegocioException('La cantidad debe estar entre 1 y 9.999.');
        }

        $this->transaccion(function () use ($repuesto, $proveedor, $cantidad, $costo, $nota, $usuarioId): void {
            // Suma atómica en la BD: dos entradas simultáneas no se pisan
            $this->db->prepare('UPDATE repuestos SET stock = stock + ? WHERE id = ?')->execute([$cantidad, (int) $repuesto['id']]);
            if ($costo !== null) {
                $this->db->prepare('UPDATE repuestos SET costo = ? WHERE id = ?')->execute([(string) $costo, (int) $repuesto['id']]);
            }
            (new MovimientoInventario())->registrar((int) $repuesto['id'], 'entrada', $cantidad, $usuarioId, $nota,
                (int) $proveedor['id'], null, $costo);
            (new Auditoria())->registrar($usuarioId, 'entrada_inventario', 'repuesto', (int) $repuesto['id'], null,
                ['cantidad' => $cantidad, 'proveedor' => $proveedor['nombre'], 'costo' => $costo]);
        });
        return (new Repuesto())->find($repuestoId);
    }

    /**
     * Ajuste por conteo físico, daño o pérdida (solo administrador). Cantidad con signo; motivo obligatorio.
     * El stock resultante nunca puede quedar negativo (R2).
     */
    public function ajustar(int $repuestoId, int $cantidad, string $motivo, int $usuarioId): array
    {
        if ($cantidad === 0) {
            throw new ReglaNegocioException('El ajuste no puede ser 0.');
        }
        if (mb_strlen(trim($motivo)) < 5) {
            throw new ReglaNegocioException('Explica el motivo del ajuste (mínimo 5 caracteres).');
        }

        $this->transaccion(function () use ($repuestoId, $cantidad, $motivo, $usuarioId): void {
            $st = $this->db->prepare('SELECT id, stock FROM repuestos WHERE id = ? FOR UPDATE');
            $st->execute([$repuestoId]);
            $repuesto = $st->fetch();
            if (!$repuesto) {
                throw new ReglaNegocioException('El repuesto no existe.');
            }
            $nuevo = (int) $repuesto['stock'] + $cantidad;
            if ($nuevo < 0) {
                throw new ReglaNegocioException("El ajuste dejaría el stock en $nuevo. Hay {$repuesto['stock']} unidad(es).");
            }
            $this->db->prepare('UPDATE repuestos SET stock = ? WHERE id = ?')->execute([$nuevo, $repuestoId]);
            (new MovimientoInventario())->registrar($repuestoId, 'ajuste', $cantidad, $usuarioId, trim($motivo));
            (new Auditoria())->registrar($usuarioId, 'ajuste_inventario', 'repuesto', $repuestoId,
                ['stock' => (int) $repuesto['stock']], ['stock' => $nuevo, 'motivo' => trim($motivo)]);
        });
        return (new Repuesto())->find($repuestoId);
    }

    private function transaccion(callable $fn): void
    {
        $this->db->beginTransaction();
        try {
            $fn();
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Diagnostico extends Model
{
    public function deOrden(int $ordenId): ?array
    {
        return $this->fetch(
            'SELECT d.descripcion, d.created_at, u.nombre AS tecnico
             FROM diagnosticos d JOIN usuarios u ON u.id = d.tecnico_id WHERE d.orden_id = :orden',
            ['orden' => $ordenId]
        );
    }

    /** Uno por orden: si ya existe, el técnico lo corrige (mientras la orden siga en diagnóstico). */
    public function guardar(int $ordenId, int $tecnicoId, string $descripcion): void
    {
        $this->query(
            'INSERT INTO diagnosticos (orden_id, tecnico_id, descripcion) VALUES (:orden, :tecnico, :desc)
             ON DUPLICATE KEY UPDATE tecnico_id = VALUES(tecnico_id), descripcion = VALUES(descripcion)',
            ['orden' => $ordenId, 'tecnico' => $tecnicoId, 'desc' => $descripcion]
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

/**
 * Orden de trabajo: eje del proceso principal.
 * Estados como constantes de clase (PHP 8.0 no tiene enum nativo).
 * Las REGLAS de transición viven en App\Services\OrdenService; aquí solo la persistencia.
 */
final class OrdenTrabajo extends Model
{
    public const RECIBIDA      = 'recibida';
    public const DIAGNOSTICO   = 'diagnostico';
    public const COTIZADA      = 'cotizada';
    public const APROBADA      = 'aprobada';
    public const RECHAZADA     = 'rechazada';
    public const EN_REPARACION = 'en_reparacion';
    public const LISTA         = 'lista';
    public const ENTREGADA     = 'entregada';

    /** Etiqueta visible por estado, en el orden del flujo. */
    public const ESTADOS = [
        self::RECIBIDA      => 'Recibida',
        self::DIAGNOSTICO   => 'En diagnóstico',
        self::COTIZADA      => 'Cotizada',
        self::APROBADA      => 'Aprobada',
        self::RECHAZADA     => 'Rechazada',
        self::EN_REPARACION => 'En reparación',
        self::LISTA         => 'Lista para entrega',
        self::ENTREGADA     => 'Entregada',
    ];

    /** Estados en los que la orden ya no requiere trabajo del taller. */
    public const CERRADAS = [self::ENTREGADA];

    /** Estados de una orden abierta (todo menos entregada). */
    public const ABIERTAS = [self::RECIBIDA, self::DIAGNOSTICO, self::COTIZADA, self::APROBADA,
                             self::RECHAZADA, self::EN_REPARACION, self::LISTA];

    /**
     * Condición SQL "orden abierta". Se usa IN (...) en lugar de <> 'entregada' porque IN
     * aprovecha los índices (estado, fecha_ingreso) y (vehiculo_id, estado): ver tests/rendimiento.md.
     * Los valores son constantes de la clase, nunca entrada del usuario.
     */
    public static function sqlAbiertas(string $alias = ''): string
    {
        return ($alias !== '' ? $alias . '.' : '') . "estado IN ('" . implode("','", self::ABIERTAS) . "')";
    }

    /** Pasos que se muestran en la barra de progreso (el rechazo es una rama). */
    public const PASOS = [self::RECIBIDA, self::DIAGNOSTICO, self::COTIZADA, self::APROBADA,
                          self::EN_REPARACION, self::LISTA, self::ENTREGADA];

    private const ORDEN = [
        'codigo'  => 'o.codigo',
        'ingreso' => 'o.fecha_ingreso',
        'placa'   => 'v.placa',
        'estado'  => "FIELD(o.estado,'recibida','diagnostico','cotizada','aprobada','rechazada','en_reparacion','lista','entregada')",
    ];

    private const SELECT_LISTA =
        'SELECT o.id, o.codigo, o.estado, o.fecha_ingreso, o.fecha_estimada,
                v.placa, v.marca, v.modelo, c.nombre AS cliente, t.nombre AS tecnico
         FROM ordenes_trabajo o
         JOIN vehiculos v ON v.id = o.vehiculo_id
         JOIN clientes  c ON c.id = v.cliente_id
         LEFT JOIN usuarios t ON t.id = o.tecnico_id';

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    /**
     * Listado con filtros (RF-08). Fechas en formato Y-m-d ya validadas por el controlador.
     */
    public function listar(ListParams $p, string $estado, int $tecnicoId, string $desde, string $hasta): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(o.codigo LIKE :q1 OR v.placa LIKE :q2 OR c.nombre LIKE :q3)';
            $like = $this->like($p->q);
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if ($estado === 'abiertas') {
            $where[] = self::sqlAbiertas('o');
        } elseif ($estado !== '') {
            $where[] = 'o.estado = :estado';
            $params['estado'] = $estado;
        }
        if ($tecnicoId === -1) {
            $where[] = 'o.tecnico_id IS NULL';
        } elseif ($tecnicoId > 0) {
            $where[] = 'o.tecnico_id = :tecnico';
            $params['tecnico'] = $tecnicoId;
        }
        if ($desde !== '') {
            $where[] = 'o.fecha_ingreso >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== '') {
            $where[] = 'o.fecha_ingreso <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        return $this->paginate(
            'SELECT o.id, o.codigo, o.estado, o.fecha_ingreso, o.fecha_estimada,
                    v.placa, v.marca, v.modelo, c.nombre AS cliente, t.nombre AS tecnico',
            'FROM ordenes_trabajo o
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes  c ON c.id = v.cliente_id
             LEFT JOIN usuarios t ON t.id = o.tecnico_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', o.id ' . strtoupper($p->dir),
            $p
        );
    }

    /** Orden completa con vehículo, cliente, asesor y técnico. */
    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT o.*, v.placa, v.marca, v.modelo, v.anio, v.color,
                    c.id AS cliente_id, c.nombre AS cliente, c.telefono AS cliente_telefono,
                    c.usuario_id AS cliente_usuario_id,
                    a.nombre AS asesor, t.nombre AS tecnico
             FROM ordenes_trabajo o
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes  c ON c.id = v.cliente_id
             JOIN usuarios  a ON a.id = o.asesor_id
             LEFT JOIN usuarios t ON t.id = o.tecnico_id
             WHERE o.id = :id',
            ['id' => $id]
        );
    }

    /** Orden abierta (no entregada) del vehículo, si existe. Regla R5. */
    public function abiertaDeVehiculo(int $vehiculoId): ?array
    {
        return $this->fetch(
            "SELECT id, codigo, estado FROM ordenes_trabajo
             WHERE vehiculo_id = :v AND " . self::sqlAbiertas() . " ORDER BY id DESC LIMIT 1",
            ['v' => $vehiculoId]
        );
    }

    /** Último kilometraje registrado del vehículo (0 si nunca ingresó). */
    public function ultimoKilometraje(int $vehiculoId): int
    {
        return (int) $this->fetchValue(
            'SELECT COALESCE(MAX(kilometraje_ingreso), 0) FROM ordenes_trabajo WHERE vehiculo_id = :v',
            ['v' => $vehiculoId]
        );
    }

    /**
     * Inserta la orden con código consecutivo por año (OT-2026-0005).
     * Debe llamarse DENTRO de una transacción: FOR UPDATE bloquea la lectura del consecutivo
     * para que dos asesores simultáneos no generen el mismo código.
     */
    public function insertar(array $d): int
    {
        $prefijo = 'OT-' . date('Y') . '-';
        $ultimo = (int) $this->fetchValue(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 9) AS UNSIGNED)), 0)
             FROM ordenes_trabajo WHERE codigo LIKE :pref FOR UPDATE',
            ['pref' => $prefijo . '%']
        );
        $this->query(
            'INSERT INTO ordenes_trabajo (codigo, vehiculo_id, asesor_id, tecnico_id, estado, motivo_ingreso,
                                          kilometraje_ingreso, fecha_estimada)
             VALUES (:codigo, :vehiculo, :asesor, NULL, :estado, :motivo, :km, :fecha)',
            [
                'codigo'   => $prefijo . str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT),
                'vehiculo' => (int) $d['vehiculo_id'],
                'asesor'   => (int) $d['asesor_id'],
                'estado'   => self::RECIBIDA,
                'motivo'   => $d['motivo_ingreso'],
                'km'       => (int) $d['kilometraje_ingreso'],
                'fecha'    => $d['fecha_estimada'] !== '' ? $d['fecha_estimada'] : null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Cambia el estado solo si la orden sigue en el estado esperado (bloqueo optimista).
     * Devuelve false si otro usuario la cambió primero.
     */
    public function moverEstado(int $id, string $desde, string $hacia, array $extra = []): bool
    {
        $sets = ['estado = :hacia'];
        $params = ['hacia' => $hacia, 'id' => $id, 'desde' => $desde];
        foreach ($extra as $columna => $valor) {   // columnas fijas definidas por OrdenService
            $sets[] = "$columna = :x_$columna";
            $params["x_$columna"] = $valor;
        }
        return $this->query(
            'UPDATE ordenes_trabajo SET ' . implode(', ', $sets) . ' WHERE id = :id AND estado = :desde',
            $params
        )->rowCount() === 1;
    }

    public function asignarTecnico(int $id, int $tecnicoId): void
    {
        $this->query('UPDATE ordenes_trabajo SET tecnico_id = :t WHERE id = :id', ['t' => $tecnicoId, 'id' => $id]);
    }

    public function registrarHistorial(int $id, ?string $anterior, string $nuevo, int $usuarioId, ?string $comentario): void
    {
        $this->query(
            'INSERT INTO orden_historial_estados (orden_id, estado_anterior, estado_nuevo, usuario_id, comentario)
             VALUES (:orden, :anterior, :nuevo, :usuario, :comentario)',
            ['orden' => $id, 'anterior' => $anterior, 'nuevo' => $nuevo, 'usuario' => $usuarioId, 'comentario' => $comentario]
        );
    }

    public function historial(int $id): array
    {
        return $this->fetchAll(
            'SELECT h.estado_anterior, h.estado_nuevo, h.comentario, h.created_at, u.nombre AS usuario
             FROM orden_historial_estados h JOIN usuarios u ON u.id = h.usuario_id
             WHERE h.orden_id = :id ORDER BY h.created_at DESC, h.id DESC',
            ['id' => $id]
        );
    }

    /** @return array<string,int> todos los estados, aunque tengan 0 órdenes */
    public function contarPorEstado(): array
    {
        $conteo = array_fill_keys(array_keys(self::ESTADOS), 0);
        foreach ($this->fetchAll('SELECT estado, COUNT(*) AS total FROM ordenes_trabajo GROUP BY estado') as $fila) {
            $conteo[$fila['estado']] = (int) $fila['total'];
        }
        return $conteo;
    }

    public function contarAbiertas(): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM ordenes_trabajo WHERE " . self::sqlAbiertas()
        );
    }

    public function contarSinTecnico(): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM ordenes_trabajo WHERE estado = 'recibida' AND tecnico_id IS NULL"
        );
    }

    public function recientes(int $limite = 8): array
    {
        return $this->fetchAll(
            self::SELECT_LISTA . ' ORDER BY o.fecha_ingreso DESC, o.id DESC LIMIT :limite',
            ['limite' => $limite]
        );
    }

    /** Órdenes activas asignadas a un técnico (el técnico solo ve las suyas). */
    public function activasDeTecnico(int $tecnicoId): array
    {
        return $this->fetchAll(
            self::SELECT_LISTA . " WHERE o.tecnico_id = :tecnico AND o.estado NOT IN ('entregada','rechazada')
             ORDER BY o.fecha_ingreso ASC",
            ['tecnico' => $tecnicoId]
        );
    }

    public function deVehiculo(int $vehiculoId): array
    {
        return $this->fetchAll(self::SELECT_LISTA . ' WHERE o.vehiculo_id = :v ORDER BY o.fecha_ingreso DESC',
            ['v' => $vehiculoId]);
    }

    public function deCliente(int $clienteId): array
    {
        return $this->fetchAll(self::SELECT_LISTA . ' WHERE c.id = :c ORDER BY o.fecha_ingreso DESC',
            ['c' => $clienteId]);
    }

    /** Órdenes de los vehículos del cliente vinculado a este usuario (control de propiedad). */
    public function deUsuarioCliente(int $usuarioId): array
    {
        return $this->fetchAll(
            self::SELECT_LISTA . ' WHERE c.usuario_id = :usuario ORDER BY o.fecha_ingreso DESC',
            ['usuario' => $usuarioId]
        );
    }
}

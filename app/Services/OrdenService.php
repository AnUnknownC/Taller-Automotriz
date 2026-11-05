<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Auditoria;
use App\Models\Cotizacion;
use App\Models\Diagnostico;
use App\Models\Liquidacion;
use App\Models\OrdenTrabajo as OT;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Core\Config;
use PDO;
use Throwable;

/**
 * Casos de uso de la orden de trabajo y su MÁQUINA DE ESTADOS (RF-04).
 *
 *   recibida ─asignar─▶ diagnostico ─cotizar─▶ cotizada ─┬─aprobar──▶ aprobada ─iniciar─▶ en_reparacion ─terminar─▶ lista ─entregar─▶ entregada
 *                                                         └─rechazar─▶ rechazada ──────────────────────────────────────────entregar─▶ entregada
 *
 * Toda operación que toca varias tablas corre en UNA transacción (RNF-07):
 * si algo falla, no queda nada a medias.
 */
final class OrdenService
{
    /** Transiciones válidas: estado actual => estados a los que puede pasar. */
    public const TRANSICIONES = [
        OT::RECIBIDA      => [OT::DIAGNOSTICO],
        OT::DIAGNOSTICO   => [OT::COTIZADA],
        OT::COTIZADA      => [OT::APROBADA, OT::RECHAZADA],
        OT::APROBADA      => [OT::EN_REPARACION],
        OT::EN_REPARACION => [OT::LISTA],
        OT::LISTA         => [OT::ENTREGADA],
        OT::RECHAZADA     => [OT::ENTREGADA],
        OT::ENTREGADA     => [],
    ];

    /** Estados en los que se puede cambiar el técnico asignado. */
    private const REASIGNABLE = [OT::DIAGNOSTICO, OT::COTIZADA, OT::APROBADA, OT::EN_REPARACION];

    private PDO $db;
    private OT $ordenes;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->ordenes = new OT();
    }

    public static function puedeTransitar(string $desde, string $hacia): bool
    {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    // ------------------------------------------------------------------
    // Autorización sobre UNA orden concreta (RS-04: control de propiedad)
    // ------------------------------------------------------------------

    public static function puedeVer(array $orden, array $usuario): bool
    {
        switch ($usuario['rol']) {
            case 'administrador':
            case 'asesor':
                return true;
            case 'tecnico':
                return (int) $orden['tecnico_id'] === (int) $usuario['id'];
            case 'cliente':
                return (int) $orden['cliente_usuario_id'] === (int) $usuario['id'];
        }
        return false;
    }

    /**
     * Acciones que el usuario puede ejecutar AHORA sobre la orden.
     * La vista solo muestra estos botones; cada acción vuelve a validarse al ejecutarse.
     * @return string[]
     */
    public static function acciones(array $orden, array $usuario, bool $tieneDiagnostico): array
    {
        $estado = $orden['estado'];
        $rol = $usuario['rol'];
        $esSuTecnico = (int) $orden['tecnico_id'] === (int) $usuario['id'];
        $acciones = [];

        if ($rol === 'asesor') {
            if ($estado === OT::RECIBIDA) {
                $acciones[] = 'asignar';
            } elseif (in_array($estado, self::REASIGNABLE, true)) {
                $acciones[] = 'reasignar';
            }
            if (($estado === OT::DIAGNOSTICO && $tieneDiagnostico) || $estado === OT::COTIZADA) {
                $acciones[] = 'cotizar';
            }
        }
        if ($rol === 'asesor') {
            if ($estado === OT::COTIZADA) {
                $acciones[] = 'responder';          // registra la respuesta presencial o telefónica
            }
            if (in_array($estado, [OT::LISTA, OT::RECHAZADA], true)) {
                $acciones[] = 'liquidar';
            }
        }
        if ($rol === 'cliente' && $estado === OT::COTIZADA
            && (int) $orden['cliente_usuario_id'] === (int) $usuario['id']) {
            $acciones[] = 'responder';
        }
        if ($rol === 'tecnico' && $esSuTecnico) {
            $porEstado = [OT::DIAGNOSTICO => 'diagnosticar', OT::APROBADA => 'iniciar', OT::EN_REPARACION => 'terminar'];
            if (isset($porEstado[$estado])) {
                $acciones[] = $porEstado[$estado];
            }
        }
        return $acciones;
    }

    // ------------------------------------------------------------------
    // Casos de uso
    // ------------------------------------------------------------------

    /**
     * Recepción del vehículo: crea la orden en estado "recibida".
     * Reglas: vehículo y cliente activos · R5 sin otra orden abierta · kilometraje no menor al último.
     */
    public function recibir(array $d, int $asesorId): int
    {
        $vehiculo = (new Vehiculo())->find((int) $d['vehiculo_id']);
        if (!$vehiculo || (int) $vehiculo['activo'] !== 1 || (int) $vehiculo['cliente_activo'] !== 1) {
            throw new ReglaNegocioException('El vehículo o su propietario no están activos.');
        }

        return $this->enTransaccion(function () use ($d, $asesorId, $vehiculo): int {
            // Se bloquea la fila del vehículo: dos asesores no pueden abrirle orden a la vez
            $this->db->prepare('SELECT id FROM vehiculos WHERE id = ? FOR UPDATE')->execute([(int) $vehiculo['id']]);

            if ($abierta = $this->ordenes->abiertaDeVehiculo((int) $vehiculo['id'])) {
                throw new ReglaNegocioException("El vehículo ya tiene la orden {$abierta['codigo']} abierta.");
            }
            $ultimoKm = $this->ordenes->ultimoKilometraje((int) $vehiculo['id']);
            if ((int) $d['kilometraje_ingreso'] < $ultimoKm) {
                throw new ReglaNegocioException('El kilometraje no puede ser menor al del último ingreso (' . number_format($ultimoKm, 0, ',', '.') . ' km).');
            }

            $id = $this->ordenes->insertar($d + ['asesor_id' => $asesorId]);
            $this->ordenes->registrarHistorial($id, null, OT::RECIBIDA, $asesorId, 'Ingreso del vehículo');
            (new Auditoria())->registrar($asesorId, 'crear', 'orden', $id, null,
                ['vehiculo' => $vehiculo['placa'], 'km' => (int) $d['kilometraje_ingreso']]);
            return $id;
        });
    }

    /** Asigna técnico (recibida → diagnóstico) o lo cambia mientras la orden está en curso. */
    public function asignarTecnico(array $orden, int $tecnicoId, int $asesorId): void
    {
        $tecnico = (new Usuario())->findActive($tecnicoId);
        if (!$tecnico || $tecnico['rol'] !== 'tecnico') {
            throw new ReglaNegocioException('Selecciona un técnico activo.');
        }
        $estado = $orden['estado'];
        $esPrimera = $estado === OT::RECIBIDA;
        if (!$esPrimera && !in_array($estado, self::REASIGNABLE, true)) {
            throw new ReglaNegocioException('En este estado ya no se puede cambiar el técnico.');
        }
        if ((int) $orden['tecnico_id'] === $tecnicoId) {
            throw new ReglaNegocioException("{$tecnico['nombre']} ya está asignado a esta orden.");
        }

        $this->enTransaccion(function () use ($orden, $tecnico, $asesorId, $esPrimera, $estado): void {
            $this->ordenes->asignarTecnico((int) $orden['id'], (int) $tecnico['id']);
            if ($esPrimera) {
                $this->mover($orden, OT::DIAGNOSTICO, $asesorId, "Asignada a {$tecnico['nombre']}");
            } else {
                $this->ordenes->registrarHistorial((int) $orden['id'], $estado, $estado, $asesorId,
                    "Técnico cambiado: {$orden['tecnico']} → {$tecnico['nombre']}");
            }
            (new Auditoria())->registrar($asesorId, $esPrimera ? 'asignar_tecnico' : 'reasignar_tecnico', 'orden',
                (int) $orden['id'], ['tecnico' => $orden['tecnico']], ['tecnico' => $tecnico['nombre']]);
        });
    }

    /** El técnico asignado registra (o corrige) el diagnóstico mientras la orden está en diagnóstico. */
    public function registrarDiagnostico(array $orden, int $tecnicoId, string $descripcion): void
    {
        if ((int) $orden['tecnico_id'] !== $tecnicoId) {
            throw new ReglaNegocioException('Solo el técnico asignado puede registrar el diagnóstico.');
        }
        if ($orden['estado'] !== OT::DIAGNOSTICO) {
            throw new ReglaNegocioException('El diagnóstico solo se registra mientras la orden está en diagnóstico.');
        }
        (new Diagnostico())->guardar((int) $orden['id'], $tecnicoId, $descripcion);
        (new Auditoria())->registrar($tecnicoId, 'diagnosticar', 'orden', (int) $orden['id']);
    }

    /**
     * El asesor arma la cotización. Primera vez: diagnóstico → cotizada.
     * Mientras el cliente no responda, puede corregirla (se reemplazan los ítems).
     * R9: el precio de cada repuesto se toma del catálogo y queda CONGELADO en la cotización.
     *
     * @param array $itemsEntrada [['tipo','repuesto_id','descripcion','cantidad','precio_unitario'], ...] ya validados
     */
    public function guardarCotizacion(array $orden, array $itemsEntrada, int $asesorId): void
    {
        $cotizaciones = new Cotizacion();
        $existente = $cotizaciones->deOrden((int) $orden['id']);

        if ($orden['estado'] === OT::DIAGNOSTICO) {
            if (!(new Diagnostico())->deOrden((int) $orden['id'])) {
                throw new ReglaNegocioException('Primero el técnico debe registrar el diagnóstico.');
            }
        } elseif (!($orden['estado'] === OT::COTIZADA && $existente && $existente['estado'] === 'pendiente')) {
            throw new ReglaNegocioException('La cotización ya no se puede modificar en este estado.');
        }

        // Precio de repuestos desde el catálogo (no se confía en el precio enviado por el navegador)
        $catalogo = (new Repuesto())->activosPorId(array_column($itemsEntrada, 'repuesto_id'));
        $items = [];
        foreach ($itemsEntrada as $i) {
            if ($i['tipo'] === 'repuesto') {
                $rep = $catalogo[(int) $i['repuesto_id']] ?? null;
                if (!$rep) {
                    throw new ReglaNegocioException('Uno de los repuestos ya no está disponible en el catálogo.');
                }
                $i['descripcion'] = $rep['nombre'];
                $i['precio_unitario'] = (float) $rep['precio_venta'];
            } else {
                $i['repuesto_id'] = null;
            }
            $items[] = $i;
        }

        $this->enTransaccion(function () use ($orden, $items, $asesorId, $cotizaciones, $existente): void {
            $cotizaciones->guardar((int) $orden['id'], $asesorId, $items);
            $total = Cotizacion::totales($items)['total'];
            if ($orden['estado'] === OT::DIAGNOSTICO) {
                $this->mover($orden, OT::COTIZADA, $asesorId,
                    'Cotización enviada al cliente por $' . number_format($total, 0, ',', '.'));
            } else {
                $this->ordenes->registrarHistorial((int) $orden['id'], OT::COTIZADA, OT::COTIZADA, $asesorId,
                    'Cotización corregida: nuevo total $' . number_format($total, 0, ',', '.'));
            }
            (new Auditoria())->registrar($asesorId, $existente ? 'editar_cotizacion' : 'cotizar', 'orden',
                (int) $orden['id'], null, ['items' => count($items), 'total' => $total]);
        });
    }

    /**
     * Respuesta del cliente a la cotización: cotizada → aprobada | rechazada.
     * Por el portal (el propio cliente) o registrada por el asesor (presencial / teléfono). R1.
     */
    public function responderCotizacion(array $orden, array $usuario, bool $aprueba, string $canal, ?string $motivo): void
    {
        if (!in_array('responder', self::acciones($orden, $usuario, true), true)) {
            throw new ReglaNegocioException('Esta cotización ya no espera respuesta o no te corresponde responderla.');
        }
        $canal = $usuario['rol'] === 'cliente' ? 'portal' : $canal;
        if (!in_array($canal, ['portal', 'presencial', 'telefono'], true)) {
            throw new ReglaNegocioException('Indica por qué medio respondió el cliente.');
        }
        if (!$aprueba && ($motivo === null || trim($motivo) === '')) {
            throw new ReglaNegocioException('Indica el motivo del rechazo.');
        }
        $cotizacion = (new Cotizacion())->deOrden((int) $orden['id']);

        $this->enTransaccion(function () use ($orden, $usuario, $aprueba, $canal, $motivo, $cotizacion): void {
            $estado = $aprueba ? 'aprobada' : 'rechazada';
            if (!$cotizacion || !(new Cotizacion())->responder((int) $cotizacion['id'], $estado, (int) $usuario['id'], $canal, $aprueba ? null : $motivo)) {
                throw new ReglaNegocioException('La cotización ya fue respondida. Recarga la página.');
            }
            $quien = $usuario['rol'] === 'cliente' ? 'el cliente desde el portal'
                : 'el asesor (' . ($canal === 'telefono' ? 'por teléfono' : 'en persona') . ')';
            $this->mover($orden, $aprueba ? OT::APROBADA : OT::RECHAZADA, (int) $usuario['id'],
                ($aprueba ? 'Aprobada' : 'Rechazada') . " por $quien" . ($aprueba ? '' : ": $motivo"));
            (new Auditoria())->registrar((int) $usuario['id'], $aprueba ? 'aprobar_cotizacion' : 'rechazar_cotizacion',
                'orden', (int) $orden['id'], null, ['canal' => $canal, 'motivo' => $motivo]);
        });
    }

    /**
     * El técnico inicia la reparación: aprobada → en_reparación.
     * R2: descuenta el stock de cada repuesto cotizado y registra la salida en el kardex,
     * todo en UNA transacción. Si falta cualquier repuesto, no se descuenta nada.
     */
    public function iniciarReparacion(array $orden, int $tecnicoId): void
    {
        $this->exigirTecnico($orden, $tecnicoId, OT::APROBADA, 'iniciar la reparación');
        $cotizacion = (new Cotizacion())->deOrden((int) $orden['id']);
        $items = array_filter((new Cotizacion())->items((int) $cotizacion['id']), fn ($i) => $i['tipo'] === 'repuesto');

        // Cantidades por repuesto (el mismo repuesto puede aparecer en varias filas)
        $necesario = [];
        foreach ($items as $i) {
            $necesario[(int) $i['repuesto_id']] = ($necesario[(int) $i['repuesto_id']] ?? 0) + (int) $i['cantidad'];
        }
        ksort($necesario);   // siempre en el mismo orden: evita bloqueos cruzados entre transacciones

        $this->enTransaccion(function () use ($orden, $tecnicoId, $necesario): void {
            $faltantes = [];
            $stock = [];
            foreach ($necesario as $repuestoId => $cantidad) {
                $st = $this->db->prepare('SELECT codigo, nombre, stock FROM repuestos WHERE id = ? FOR UPDATE');
                $st->execute([$repuestoId]);
                $stock[$repuestoId] = $st->fetch();
                if ((int) $stock[$repuestoId]['stock'] < $cantidad) {
                    $faltantes[] = "{$stock[$repuestoId]['nombre']} (hay {$stock[$repuestoId]['stock']}, se necesitan $cantidad)";
                }
            }
            if ($faltantes) {
                throw new ReglaNegocioException('No hay stock suficiente: ' . implode('; ', $faltantes) . '. Registra una entrada de mercancía o avisa al asesor.');
            }
            foreach ($necesario as $repuestoId => $cantidad) {
                $this->db->prepare('UPDATE repuestos SET stock = stock - ? WHERE id = ?')->execute([$cantidad, $repuestoId]);
                $this->db->prepare(
                    "INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, orden_id, usuario_id, motivo)
                     VALUES (?, 'salida', ?, ?, ?, ?)"
                )->execute([$repuestoId, $cantidad, (int) $orden['id'], $tecnicoId, 'Reparación ' . $orden['codigo']]);
            }
            $this->mover($orden, OT::EN_REPARACION, $tecnicoId,
                $necesario ? 'Repuestos retirados de bodega: ' . array_sum($necesario) . ' unidad(es)' : 'Solo mano de obra');
            (new Auditoria())->registrar($tecnicoId, 'iniciar_reparacion', 'orden', (int) $orden['id'], null, ['repuestos' => $necesario]);
        });
    }

    /** El técnico termina: en_reparación → lista. */
    public function terminarReparacion(array $orden, int $tecnicoId, ?string $nota): void
    {
        $this->exigirTecnico($orden, $tecnicoId, OT::EN_REPARACION, 'marcar la orden como lista');
        $this->enTransaccion(function () use ($orden, $tecnicoId, $nota): void {
            $this->mover($orden, OT::LISTA, $tecnicoId, $nota ?: 'Trabajo terminado');
            (new Auditoria())->registrar($tecnicoId, 'terminar_reparacion', 'orden', (int) $orden['id']);
        });
    }

    /**
     * Valores a liquidar: la cotización aprobada, o solo el diagnóstico si se rechazó.
     * @return array{mano_obra:float, repuestos:float, subtotal:float, iva:float, total:float, tasa:float, items:array, solo_diagnostico:bool}
     */
    public function calcularLiquidacion(array $orden): array
    {
        if ($orden['estado'] === OT::RECHAZADA) {
            $items = [['tipo' => 'mano_obra', 'descripcion' => 'Diagnóstico técnico', 'cantidad' => 1,
                       'precio_unitario' => (float) Config::get('taller.tarifa_diagnostico', 60000)]];
        } else {
            $cotizacion = (new Cotizacion())->deOrden((int) $orden['id']);
            $items = $cotizacion ? (new Cotizacion())->items((int) $cotizacion['id']) : [];
        }
        return Cotizacion::totales($items) + ['items' => $items, 'solo_diagnostico' => $orden['estado'] === OT::RECHAZADA];
    }

    /**
     * Liquidación SIMULADA y entrega en un solo paso: lista | rechazada → entregada.
     * R4: es imposible entregar sin liquidación porque ambas ocurren en la misma transacción.
     */
    public function liquidarYEntregar(array $orden, string $metodo, int $asesorId): array
    {
        if (!in_array($orden['estado'], [OT::LISTA, OT::RECHAZADA], true)) {
            throw new ReglaNegocioException('Solo se entregan órdenes listas o con la cotización rechazada.');
        }
        if (!array_key_exists($metodo, Liquidacion::METODOS)) {
            throw new ReglaNegocioException('Selecciona el método de pago (simulado).');
        }
        $totales = $this->calcularLiquidacion($orden);

        $this->enTransaccion(function () use ($orden, $metodo, $asesorId, $totales): void {
            if ((new Liquidacion())->deOrden((int) $orden['id'])) {
                throw new ReglaNegocioException('Esta orden ya tiene una liquidación registrada.');
            }
            (new Liquidacion())->insertar((int) $orden['id'], $totales, $metodo, $asesorId);
            $this->mover($orden, OT::ENTREGADA, $asesorId,
                'Liquidación simulada por $' . number_format($totales['total'], 0, ',', '.') . ' (' . Liquidacion::METODOS[$metodo] . ') y entrega al cliente',
                ['fecha_entrega' => date('Y-m-d H:i:s')]);
            (new Auditoria())->registrar($asesorId, 'liquidar_entregar', 'orden', (int) $orden['id'], null,
                ['total' => $totales['total'], 'metodo' => $metodo, 'solo_diagnostico' => $totales['solo_diagnostico']]);
        });
        return $totales;
    }

    // ------------------------------------------------------------------
    // Internos

    private function exigirTecnico(array $orden, int $tecnicoId, string $estado, string $accion): void
    {
        if ((int) $orden['tecnico_id'] !== $tecnicoId) {
            throw new ReglaNegocioException("Solo el técnico asignado puede $accion.");
        }
        if ($orden['estado'] !== $estado) {
            throw new ReglaNegocioException("No se puede $accion: la orden está en estado " . OT::ESTADOS[$orden['estado']] . '.');
        }
    }
    // ------------------------------------------------------------------

    /** Aplica una transición validando el mapa de estados y registra el historial. */
    private function mover(array $orden, string $hacia, int $usuarioId, ?string $comentario, array $extra = []): void
    {
        $desde = $orden['estado'];
        if (!self::puedeTransitar($desde, $hacia)) {
            throw new ReglaNegocioException('Transición no permitida: ' . OT::ESTADOS[$desde] . ' → ' . OT::ESTADOS[$hacia] . '.');
        }
        if (!$this->ordenes->moverEstado((int) $orden['id'], $desde, $hacia, $extra)) {
            throw new ReglaNegocioException('Otro usuario modificó esta orden hace un momento. Recarga la página.');
        }
        $this->ordenes->registrarHistorial((int) $orden['id'], $desde, $hacia, $usuarioId, $comentario);
    }

    /** @return mixed */
    private function enTransaccion(callable $fn)
    {
        if ($this->db->inTransaction()) {
            return $fn();
        }
        $this->db->beginTransaction();
        try {
            $resultado = $fn();
            $this->db->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

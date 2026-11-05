<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\FormState;
use App\Core\HttpException;
use App\Core\ListParams;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Auditoria;
use App\Models\Cotizacion;
use App\Models\Diagnostico;
use App\Models\Liquidacion;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Services\OrdenService;
use App\Services\ReglaNegocioException;

/**
 * Proceso principal: órdenes de trabajo.
 * El controlador valida FORMATO; las REGLAS del taller están en OrdenService.
 */
final class OrdenController extends Controller
{
    private OrdenTrabajo $ordenes;
    private OrdenService $servicio;

    public function __construct()
    {
        $this->ordenes = new OrdenTrabajo();
        $this->servicio = new OrdenService();
    }

    /** GET /ordenes (administrador, asesor) */
    public function index(): void
    {
        $p = ListParams::fromRequest(OrdenTrabajo::columnasOrden(), 'ingreso', 'desc');
        $estado = $p->filter('estado', array_merge(['abiertas'], array_keys(OrdenTrabajo::ESTADOS)), 'abiertas');
        $tecnicos = (new Usuario())->tecnicosActivos();
        $tecnico = $p->filter('tecnico', array_merge(['-1'], array_map('strval', array_column($tecnicos, 'id'))));
        // Fechas: solo se aceptan si son fechas reales en formato AAAA-MM-DD
        $desde = $this->fechaValida(Request::query('desde')) ? Request::query('desde') : '';
        $hasta = $this->fechaValida(Request::query('hasta')) ? Request::query('hasta') : '';
        $p->filters += ['desde' => $desde, 'hasta' => $hasta];

        $this->view('ordenes/index', [
            'title'    => 'Órdenes de trabajo',
            'res'      => $this->ordenes->listar($p, $estado, (int) $tecnico, $desde, $hasta),
            'p'        => $p,
            'tecnicos' => $tecnicos,
            'conteo'   => $this->ordenes->contarPorEstado(),
        ]);
    }

    /** GET /ordenes/crear?placa=ABC123 (asesor) — paso 1: buscar el vehículo por placa */
    public function create(): void
    {
        $placa = strtoupper(str_replace([' ', '-'], '', Request::query('placa')));
        $vehiculo = null;
        $abierta = null;
        $ultimoKm = 0;
        if ($placa !== '') {
            $vehiculo = (new Vehiculo())->findByPlaca($placa);
            if ($vehiculo) {
                $abierta = $this->ordenes->abiertaDeVehiculo((int) $vehiculo['id']);
                $ultimoKm = $this->ordenes->ultimoKilometraje((int) $vehiculo['id']);
            }
        }
        $this->view('ordenes/create', [
            'title'    => 'Nueva orden',
            'placa'    => $placa,
            'vehiculo' => $vehiculo,
            'abierta'  => $abierta,
            'ultimoKm' => $ultimoKm,
        ]);
    }

    /** POST /ordenes (asesor) — paso 2: registrar el ingreso */
    public function store(): void
    {
        $d = $this->only(['vehiculo_id', 'placa', 'motivo_ingreso', 'kilometraje_ingreso', 'fecha_estimada']);
        $d['kilometraje_ingreso'] = str_replace(['.', ',', ' '], '', $d['kilometraje_ingreso']);
        $volver = '/ordenes/crear?placa=' . rawurlencode($d['placa']);

        $v = (new Validator($d))
            ->rule('vehiculo_id', 'required|integer', 'El vehículo')
            ->rule('motivo_ingreso', 'required|min:5|max:1000', 'El motivo de ingreso')
            ->rule('kilometraje_ingreso', 'required|between:0,2000000', 'El kilometraje');
        if ($d['fecha_estimada'] !== '') {
            $v->check($this->fechaValida($d['fecha_estimada']) && $d['fecha_estimada'] >= date('Y-m-d'),
                'fecha_estimada', 'La fecha estimada no puede ser anterior a hoy.');
        }
        if ($v->fails()) {
            $this->fail($volver, $v->errors(), $d);
        }

        try {
            $id = $this->servicio->recibir($d, Auth::id());
        } catch (ReglaNegocioException $e) {
            FormState::flash($d, []);
            $this->error($e->getMessage(), $volver);
        }
        $orden = $this->ordenes->find($id);
        $this->success("Orden {$orden['codigo']} registrada. Ahora asígnale un técnico.", "/ordenes/$id");
    }

    /** GET /ordenes/{id} — todos los roles, con control de propiedad */
    public function show(int $id): void
    {
        $orden = $this->autorizada($id);
        $usuario = Auth::user();
        $diagnostico = (new Diagnostico())->deOrden($id);
        $cotizacion = (new Cotizacion())->deOrden($id);
        $items = $cotizacion ? (new Cotizacion())->items((int) $cotizacion['id']) : [];
        $acciones = OrdenService::acciones($orden, $usuario, $diagnostico !== null);

        $this->view('ordenes/show', [
            'title'       => $orden['codigo'],
            'orden'       => $orden,
            'usuario'     => $usuario,
            'diagnostico' => $diagnostico,
            'cotizacion'  => $cotizacion,
            'items'       => $items,
            'totales'     => Cotizacion::totales($items),
            'historial'   => $this->ordenes->historial($id),
            'acciones'    => $acciones,
            'tecnicos'    => array_intersect($acciones, ['asignar', 'reasignar']) ? (new Usuario())->tecnicosActivos() : [],
            'liquidacion' => (new Liquidacion())->deOrden($id),
        ]);
    }

    /** POST /ordenes/{id}/asignar (asesor) */
    public function asignar(int $id): void
    {
        $orden = $this->orFail($this->ordenes->find($id));
        $tecnicoId = (int) Request::input('tecnico_id', '0');
        try {
            $this->servicio->asignarTecnico($orden, $tecnicoId, Auth::id());
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id");
        }
        $this->success($orden['estado'] === OrdenTrabajo::RECIBIDA
            ? 'Técnico asignado. La orden pasó a diagnóstico.' : 'Técnico cambiado.', "/ordenes/$id");
    }

    /** POST /ordenes/{id}/diagnostico (técnico asignado) */
    public function diagnostico(int $id): void
    {
        $orden = $this->autorizada($id);
        $d = $this->only(['descripcion']);
        $v = (new Validator($d))->rule('descripcion', 'required|min:10|max:2000', 'El diagnóstico');
        if ($v->fails()) {
            $this->fail("/ordenes/$id", $v->errors(), $d);
        }
        try {
            $this->servicio->registrarDiagnostico($orden, Auth::id(), $d['descripcion']);
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id");
        }
        $this->success('Diagnóstico guardado. El asesor ya puede armar la cotización.', "/ordenes/$id");
    }

    /** GET /ordenes/{id}/cotizacion (asesor) — editor */
    public function editarCotizacion(int $id): void
    {
        $orden = $this->orFail($this->ordenes->find($id));
        $diagnostico = (new Diagnostico())->deOrden($id);
        if (!in_array('cotizar', OrdenService::acciones($orden, Auth::user(), $diagnostico !== null), true)) {
            $this->error('La cotización no se puede editar en el estado actual de la orden.', "/ordenes/$id");
        }
        $cotizacion = (new Cotizacion())->deOrden($id);
        $this->view('ordenes/cotizacion', [
            'title'       => 'Cotización ' . $orden['codigo'],
            'orden'       => $orden,
            'diagnostico' => $diagnostico,
            'items'       => FormState::hasOld() ? (array) FormState::old('items')
                                 : ($cotizacion ? (new Cotizacion())->items((int) $cotizacion['id']) : []),
            'catalogo'    => (new Repuesto())->activosParaCotizar(),
            'iva'         => Cotizacion::totales([])['tasa'],
        ]);
    }

    /** POST /ordenes/{id}/cotizacion (asesor) */
    public function guardarCotizacion(int $id): void
    {
        $orden = $this->orFail($this->ordenes->find($id));
        [$items, $errores] = $this->leerItems($_POST['items'] ?? null);

        if ($errores) {
            FormState::flash(['items' => $items], []);
            $this->error('No se guardó la cotización: ' . implode(' ', $errores), "/ordenes/$id/cotizacion");
        }
        try {
            $this->servicio->guardarCotizacion($orden, $items, Auth::id());
        } catch (ReglaNegocioException $e) {
            FormState::flash(['items' => $items], []);
            $this->error($e->getMessage(), "/ordenes/$id/cotizacion");
        }
        $this->success($orden['estado'] === OrdenTrabajo::DIAGNOSTICO
            ? 'Cotización enviada. El cliente ya puede verla y responder.' : 'Cotización actualizada.', "/ordenes/$id");
    }

    /** POST /ordenes/{id}/respuesta (cliente dueño desde el portal, o asesor registrando la respuesta) */
    public function responder(int $id): void
    {
        $orden = $this->autorizada($id);
        $decision = (string) Request::input('decision', '');
        $motivo = trim((string) Request::input('motivo', ''));
        if ($motivo === 'otro') {
            $motivo = trim((string) Request::input('motivo_otro', ''));
        }
        $motivo = mb_substr($motivo, 0, 255);
        if (!in_array($decision, ['aprobar', 'rechazar'], true)) {
            $this->error('Respuesta no válida.', "/ordenes/$id");
        }
        try {
            $this->servicio->responderCotizacion($orden, Auth::user(), $decision === 'aprobar',
                (string) Request::input('canal', ''), $motivo !== '' ? $motivo : null);
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id");
        }
        $this->success($decision === 'aprobar'
            ? 'Cotización aprobada. El técnico ya puede iniciar la reparación.'
            : 'Cotización rechazada. Solo se cobrará el diagnóstico al recoger el vehículo.', "/ordenes/$id");
    }

    /** POST /ordenes/{id}/iniciar (técnico asignado) */
    public function iniciar(int $id): void
    {
        $orden = $this->autorizada($id);
        try {
            $this->servicio->iniciarReparacion($orden, Auth::id());
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id");
        }
        $this->success('Reparación iniciada. Los repuestos se descontaron del inventario.', "/ordenes/$id");
    }

    /** POST /ordenes/{id}/terminar (técnico asignado) */
    public function terminar(int $id): void
    {
        $orden = $this->autorizada($id);
        $nota = mb_substr(trim((string) Request::input('nota', '')), 0, 255);
        try {
            $this->servicio->terminarReparacion($orden, Auth::id(), $nota !== '' ? $nota : null);
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id");
        }
        $this->success('Orden marcada como lista. El asesor avisará al cliente para la entrega.', "/ordenes/$id");
    }

    /** GET /ordenes/{id}/liquidar (asesor) */
    public function liquidacion(int $id): void
    {
        $orden = $this->orFail($this->ordenes->find($id));
        if (!in_array('liquidar', OrdenService::acciones($orden, Auth::user(), true), true)) {
            $this->error('Esta orden no está lista para liquidar.', "/ordenes/$id");
        }
        $this->view('ordenes/liquidar', [
            'title'   => 'Liquidar ' . $orden['codigo'],
            'orden'   => $orden,
            'totales' => $this->servicio->calcularLiquidacion($orden),
        ]);
    }

    /** POST /ordenes/{id}/liquidar (asesor) */
    public function liquidar(int $id): void
    {
        $orden = $this->orFail($this->ordenes->find($id));
        if (Request::input('confirma_entrega') !== '1') {
            FormState::flash(['metodo' => (string) Request::input('metodo', '')], ['confirma_entrega' => 'Confirma que el cliente recibe el vehículo.']);
            $this->error('No se registró: falta confirmar la entrega.', "/ordenes/$id/liquidar");
        }
        try {
            $totales = $this->servicio->liquidarYEntregar($orden, (string) Request::input('metodo', ''), Auth::id());
        } catch (ReglaNegocioException $e) {
            $this->error($e->getMessage(), "/ordenes/$id/liquidar");
        }
        $this->success('Liquidación simulada por ' . pesos($totales['total']) . ' registrada. Vehículo entregado.', "/ordenes/$id");
    }

    // ------------------------------------------------------------------

    /** Carga la orden y verifica que este usuario pueda verla (RS-04: propiedad del registro). */
    private function autorizada(int $id): array
    {
        $orden = $this->orFail($this->ordenes->find($id));
        if (!OrdenService::puedeVer($orden, Auth::user())) {
            (new Auditoria())->registrar(Auth::id(), 'acceso_denegado', 'orden', $id, null, ['rol' => Auth::role()]);
            throw new HttpException(403, 'Esta orden no está asignada a tu cuenta.');
        }
        return $orden;
    }

    /**
     * Valida el formato de los ítems de la cotización (RF-10).
     * @return array{0: array, 1: string[]}
     */
    private function leerItems($entrada): array
    {
        if (!is_array($entrada)) {
            return [[], ['Agrega al menos un ítem.']];
        }
        $items = [];
        $errores = [];
        foreach (array_values($entrada) as $n => $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $f = [];
            foreach (['tipo', 'repuesto_id', 'descripcion', 'cantidad', 'precio_unitario'] as $campo) {
                $f[$campo] = is_string($fila[$campo] ?? null) ? trim($fila[$campo]) : '';
            }
            $f['precio_unitario'] = str_replace(['.', ' ', '$'], '', $f['precio_unitario']);
            $f['cantidad'] = str_replace(',', '.', $f['cantidad']);
            $fila = $n + 1;

            if ($f['tipo'] === 'repuesto') {
                if (filter_var($f['repuesto_id'], FILTER_VALIDATE_INT) === false) {
                    $errores[] = "Fila $fila: elige un repuesto.";
                }
                if (filter_var($f['cantidad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]) === false) {
                    $errores[] = "Fila $fila: la cantidad de repuestos debe ser un entero entre 1 y 99.";
                }
                $f['repuesto_id'] = (int) $f['repuesto_id'];
            } elseif ($f['tipo'] === 'mano_obra') {
                if (mb_strlen($f['descripcion']) < 3 || mb_strlen($f['descripcion']) > 200) {
                    $errores[] = "Fila $fila: describe la mano de obra (3 a 200 caracteres).";
                }
                if (!is_numeric($f['cantidad']) || (float) $f['cantidad'] <= 0 || (float) $f['cantidad'] > 100) {
                    $errores[] = "Fila $fila: la cantidad (horas) debe estar entre 0,5 y 100.";
                }
                if (!is_numeric($f['precio_unitario']) || (float) $f['precio_unitario'] <= 0 || (float) $f['precio_unitario'] > 50000000) {
                    $errores[] = "Fila $fila: el valor de la mano de obra debe ser mayor a 0.";
                }
                $f['repuesto_id'] = null;
            } else {
                $errores[] = "Fila $fila: tipo de ítem inválido.";
            }
            $items[] = $f;
        }
        if (!$items) {
            $errores[] = 'Agrega al menos un ítem.';
        } elseif (count($items) > 30) {
            $errores[] = 'Máximo 30 ítems por cotización.';
        }
        return [$items, $errores];
    }

    private function fechaValida(string $fecha): bool
    {
        $f = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $f !== false && $f->format('Y-m-d') === $fecha;
    }
}

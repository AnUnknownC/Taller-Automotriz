<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ListParams;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Vehiculo;

/**
 * Vehículos (administrador y asesor).
 * Reglas:
 *  - Placa única con formato colombiano (ABC123 carro / ABC12D moto). VIN opcional, único, ISO 3779.
 *  - Solo se asocia a clientes activos.
 *  - Con órdenes abiertas no se puede desactivar ni cambiar de propietario.
 */
final class VehiculoController extends Controller
{
    private Vehiculo $vehiculos;

    public function __construct()
    {
        $this->vehiculos = new Vehiculo();
    }

    /** GET /vehiculos */
    public function index(): void
    {
        $p = ListParams::fromRequest(Vehiculo::columnasOrden(), 'placa');
        $estado = $p->filter('estado', ['activos', 'inactivos', 'todos'], 'activos');

        $this->view('vehiculos/index', [
            'title' => 'Vehículos',
            'res'   => $this->vehiculos->listar($p, $estado),
            'p'     => $p,
        ]);
    }

    /** GET /vehiculos/{id} */
    public function show(int $id): void
    {
        $vehiculo = $this->orFail($this->vehiculos->find($id));
        $this->view('vehiculos/show', [
            'title'    => $vehiculo['placa'],
            'vehiculo' => $vehiculo,
            'ordenes'  => (new OrdenTrabajo())->deVehiculo($id),
        ]);
    }

    /** GET /vehiculos/crear?cliente=ID (el cliente viene preseleccionado desde su ficha) */
    public function create(): void
    {
        $this->view('vehiculos/form', [
            'title'     => 'Nuevo vehículo',
            'vehiculo'  => null,
            'clientes'  => (new Cliente())->activosParaSelect(),
            'clienteId' => (int) Request::query('cliente', '0'),
        ]);
    }

    /** POST /vehiculos */
    public function store(): void
    {
        $datos = $this->datos();
        $v = $this->validar($datos, null);
        if ($v->fails()) {
            $this->fail('/vehiculos/crear', $v->errors(), $datos);
        }

        $id = $this->vehiculos->crear($datos);
        $this->audit('crear', 'vehiculo', $id, null, $datos);
        $this->success("Vehículo {$datos['placa']} registrado.", "/vehiculos/$id");
    }

    /** GET /vehiculos/{id}/editar */
    public function edit(int $id): void
    {
        $vehiculo = $this->orFail($this->vehiculos->find($id));
        $clientes = (new Cliente())->activosParaSelect();
        if ((int) $vehiculo['cliente_activo'] === 0) {   // su dueño actual, aunque esté inactivo
            array_unshift($clientes, ['id' => $vehiculo['cliente_id'], 'nombre' => $vehiculo['cliente'] . ' (inactivo)',
                                      'tipo_documento' => '', 'documento' => '']);
        }
        $this->view('vehiculos/form', [
            'title' => 'Editar vehículo', 'vehiculo' => $vehiculo, 'clientes' => $clientes, 'clienteId' => 0,
        ]);
    }

    /** POST /vehiculos/{id} */
    public function update(int $id): void
    {
        $actual = $this->orFail($this->vehiculos->find($id));
        $datos = $this->datos();

        $v = $this->validar($datos, $actual);
        if ((int) $datos['cliente_id'] !== (int) $actual['cliente_id']) {
            $v->check($this->vehiculos->ordenesAbiertas($id) === 0, 'cliente_id',
                'No se puede cambiar el propietario mientras el vehículo tenga órdenes sin entregar.');
        }
        if ($v->fails()) {
            $this->fail("/vehiculos/$id/editar", $v->errors(), $datos);
        }

        $this->vehiculos->actualizar($id, $datos);
        $this->audit('editar', 'vehiculo', $id,
            array_intersect_key($actual, array_flip(['cliente_id', 'placa', 'marca', 'modelo', 'anio', 'color', 'vin'])),
            $datos);
        $this->success('Cambios guardados.', "/vehiculos/$id");
    }

    /** POST /vehiculos/{id}/estado */
    public function toggle(int $id): void
    {
        $vehiculo = $this->orFail($this->vehiculos->find($id));
        $activar = (int) $vehiculo['activo'] === 0;

        if (!$activar && ($abiertas = $this->vehiculos->ordenesAbiertas($id)) > 0) {
            $this->error("No se puede desactivar: tiene $abiertas orden(es) sin entregar.", "/vehiculos/$id");
        }
        if ($activar && (int) $vehiculo['cliente_activo'] === 0) {
            $this->error('Primero activa al cliente propietario.', "/vehiculos/$id");
        }

        $this->vehiculos->cambiarEstado($id, $activar);
        $this->audit($activar ? 'activar' : 'desactivar', 'vehiculo', $id, ['activo' => !$activar], ['activo' => $activar]);
        $this->success('Vehículo ' . ($activar ? 'activado.' : 'desactivado.'), "/vehiculos/$id");
    }

    private function datos(): array
    {
        $d = $this->only(['cliente_id', 'placa', 'marca', 'modelo', 'anio', 'color', 'vin']);
        $d['placa'] = strtoupper(str_replace([' ', '-'], '', $d['placa']));
        $d['vin'] = strtoupper(str_replace(' ', '', $d['vin']));
        return $d;
    }

    private function validar(array $d, ?array $actual): Validator
    {
        $id = $actual ? (int) $actual['id'] : null;
        $anioMax = (int) date('Y') + 1;

        $v = (new Validator($d))
            ->rule('cliente_id', 'required|integer', 'El cliente')
            ->rule('placa', 'required', 'La placa')
            ->rule('marca', 'required|max:50', 'La marca')
            ->rule('modelo', 'required|max:50', 'El modelo')
            ->rule('anio', "required|between:1950,$anioMax", 'El año')
            ->rule('color', 'max:30', 'El color');

        $v->check((bool) preg_match(Vehiculo::PATRON_PLACA, $d['placa']), 'placa',
                'Formato de placa inválido. Usa ABC123 (carro) o ABC12D (moto).')
          ->check(!$this->vehiculos->placaEnUso($d['placa'], $id), 'placa', 'Ya existe un vehículo con esa placa.');

        if ($d['vin'] !== '') {
            $v->check((bool) preg_match(Vehiculo::PATRON_VIN, $d['vin']), 'vin',
                    'El VIN debe tener 17 caracteres (letras y números, sin I, O ni Q).')
              ->check(!$this->vehiculos->vinEnUso($d['vin'], $id), 'vin', 'Ya existe un vehículo con ese VIN.');
        }

        // El cliente debe existir y estar activo (o ser el dueño actual al editar)
        $mismoDueno = $actual && (int) $d['cliente_id'] === (int) $actual['cliente_id'];
        if (!$v->has('cliente_id') && !$mismoDueno) {
            $cliente = (new Cliente())->find((int) $d['cliente_id']);
            $v->check($cliente !== null && (int) $cliente['activo'] === 1, 'cliente_id', 'Selecciona un cliente activo.');
        }
        return $v;
    }
}

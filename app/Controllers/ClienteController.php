<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ListParams;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Services\AuthService;

/**
 * Clientes del taller (administrador y asesor).
 * Reglas:
 *  - Documento único por tipo (CC, CE, NIT, PAS).
 *  - La cuenta de acceso al portal se crea junto con el cliente, en una transacción.
 *  - No se desactiva un cliente con órdenes abiertas. Desactivarlo también le quita el acceso y desactiva sus vehículos.
 */
final class ClienteController extends Controller
{
    private Cliente $clientes;

    public function __construct()
    {
        $this->clientes = new Cliente();
    }

    /** GET /clientes */
    public function index(): void
    {
        $p = ListParams::fromRequest(Cliente::columnasOrden(), 'nombre');
        $estado = $p->filter('estado', ['activos', 'inactivos', 'todos'], 'activos');

        $this->view('clientes/index', [
            'title' => 'Clientes',
            'res'   => $this->clientes->listar($p, $estado),
            'p'     => $p,
        ]);
    }

    /** GET /clientes/{id} */
    public function show(int $id): void
    {
        $cliente = $this->orFail($this->clientes->find($id));
        $this->view('clientes/show', [
            'title'     => $cliente['nombre'],
            'cliente'   => $cliente,
            'vehiculos' => (new Vehiculo())->deCliente($id),
            'ordenes'   => (new OrdenTrabajo())->deCliente($id),
        ]);
    }

    /** GET /clientes/crear */
    public function create(): void
    {
        $this->view('clientes/form', ['title' => 'Nuevo cliente', 'cliente' => null]);
    }

    /** POST /clientes */
    public function store(): void
    {
        $datos = $this->datos();
        $v = $this->validar($datos, null);
        if ($v->fails()) {
            $this->fail('/clientes/crear', $v->errors(), $datos);
        }

        $acceso = $datos['crear_acceso'] ? ['password_hash' => AuthService::hash($datos['acceso_password'])] : null;
        $id = $this->clientes->crear($datos, $acceso);
        $this->audit('crear', 'cliente', $id, null, $datos);
        $this->success("Cliente {$datos['nombre']} registrado." . ($acceso ? ' Ya puede ingresar al portal.' : ''), "/clientes/$id");
    }

    /** GET /clientes/{id}/editar */
    public function edit(int $id): void
    {
        $cliente = $this->orFail($this->clientes->find($id));
        $this->view('clientes/form', ['title' => 'Editar cliente', 'cliente' => $cliente]);
    }

    /** POST /clientes/{id} */
    public function update(int $id): void
    {
        $actual = $this->orFail($this->clientes->find($id));
        $datos = $this->datos();
        if ($actual['usuario_id']) {
            $datos['crear_acceso'] = '';   // ya tiene cuenta: no se crea otra
        }

        $v = $this->validar($datos, $actual);
        if ($v->fails()) {
            $this->fail("/clientes/$id/editar", $v->errors(), $datos);
        }

        $acceso = $datos['crear_acceso'] ? ['password_hash' => AuthService::hash($datos['acceso_password'])] : null;
        $this->clientes->actualizar($id, $actual, $datos, $acceso);
        $this->audit('editar', 'cliente', $id,
            array_intersect_key($actual, array_flip(['tipo_documento', 'documento', 'nombre', 'telefono', 'email', 'direccion'])),
            $datos);
        $this->success('Cambios guardados.', "/clientes/$id");
    }

    /** POST /clientes/{id}/estado */
    public function toggle(int $id): void
    {
        $cliente = $this->orFail($this->clientes->find($id));
        $activar = (int) $cliente['activo'] === 0;

        if (!$activar && ($abiertas = $this->clientes->ordenesAbiertas($id)) > 0) {
            $this->error("No se puede desactivar: tiene $abiertas orden(es) de trabajo sin entregar.", "/clientes/$id");
        }

        $this->clientes->cambiarEstado($cliente, $activar);
        $this->audit($activar ? 'activar' : 'desactivar', 'cliente', $id, ['activo' => !$activar], ['activo' => $activar]);
        $this->success("Cliente " . ($activar ? 'activado.' : 'desactivado.')
            . ($cliente['usuario_id'] ? ' Su acceso al portal también quedó ' . ($activar ? 'activo.' : 'bloqueado.') : '')
            . ($activar ? '' : ' Sus vehículos quedaron inactivos.'), "/clientes/$id");
    }

    private function datos(): array
    {
        $d = $this->only(['tipo_documento', 'documento', 'nombre', 'telefono', 'email', 'direccion']);
        // Normalización: el documento sin puntos ni espacios; el teléfono solo con dígitos y +
        $d['documento'] = Cliente::normalizarDocumento($d['documento']);
        $d['telefono'] = Cliente::normalizarTelefono($d['telefono']);
        $d['email'] = mb_strtolower($d['email']);
        $d['crear_acceso'] = isset($_POST['crear_acceso']) ? '1' : '';
        $d['acceso_password'] = is_string($_POST['acceso_password'] ?? null) ? $_POST['acceso_password'] : '';
        $d['acceso_password_confirm'] = is_string($_POST['acceso_password_confirm'] ?? null) ? $_POST['acceso_password_confirm'] : '';
        return $d;
    }

    private function validar(array $d, ?array $actual): Validator
    {
        $id = $actual ? (int) $actual['id'] : null;
        $usuarioId = $actual['usuario_id'] ?? null;
        $necesitaCorreo = $d['crear_acceso'] || $usuarioId;

        $v = (new Validator($d))
            ->rule('tipo_documento', 'required|in:' . implode(',', array_keys(Cliente::TIPOS_DOCUMENTO)), 'El tipo de documento')
            ->rule('documento', 'required|max:20', 'El documento')
            ->rule('nombre', 'required|min:3|max:120', 'El nombre')
            ->rule('telefono', 'required', 'El teléfono')
            ->rule('email', ($necesitaCorreo ? 'required|' : '') . 'email|max:150', 'El correo')
            ->rule('direccion', 'max:200', 'La dirección');

        $v->check(Cliente::documentoValido($d['tipo_documento'], $d['documento']), 'documento',
                Cliente::mensajeDocumentoInvalido($d['tipo_documento']))
          ->check(!$this->clientes->documentoEnUso($d['tipo_documento'], $d['documento'], $id), 'documento',
                'Ya existe un cliente con ese documento.')
          ->check(Cliente::telefonoValido($d['telefono']), 'telefono',
                'El teléfono debe tener entre 7 y 15 dígitos.');

        if ($necesitaCorreo && $d['email'] !== '') {
            $v->check(!(new Usuario())->emailEnUso($d['email'], $usuarioId ? (int) $usuarioId : null), 'email',
                'Ese correo ya lo usa otra cuenta de acceso.');
        }
        if ($d['crear_acceso']) {
            $v->rule('acceso_password', 'required|password', 'La contraseña de acceso')
              ->rule('acceso_password_confirm', 'required|same:acceso_password', 'La confirmación de contraseña');
        }
        return $v;
    }
}

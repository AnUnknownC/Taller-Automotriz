<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\ListParams;
use App\Core\Validator;
use App\Models\Proveedor;

/** Proveedores: el administrador los gestiona; el asesor los consulta (config/routes.php). */
final class ProveedorController extends Controller
{
    private Proveedor $proveedores;

    public function __construct()
    {
        $this->proveedores = new Proveedor();
    }

    /** GET /proveedores */
    public function index(): void
    {
        $p = ListParams::fromRequest(Proveedor::columnasOrden(), 'nombre');
        $estado = $p->filter('estado', ['activos', 'inactivos', 'todos'], 'activos');
        $this->view('proveedores/index', [
            'title' => 'Proveedores', 'res' => $this->proveedores->listar($p, $estado), 'p' => $p,
            'puedeEditar' => Auth::hasRole('administrador'),
        ]);
    }

    /** GET /proveedores/crear */
    public function create(): void
    {
        $this->view('proveedores/form', ['title' => 'Nuevo proveedor', 'proveedor' => null]);
    }

    /** POST /proveedores */
    public function store(): void
    {
        $d = $this->datos();
        $v = $this->validar($d, null);
        if ($v->fails()) {
            $this->fail('/proveedores/crear', $v->errors(), $d);
        }
        $id = $this->proveedores->crear($d);
        $this->audit('crear', 'proveedor', $id, null, $d);
        $this->success("Proveedor {$d['nombre']} registrado.", '/proveedores');
    }

    /** GET /proveedores/{id}/editar */
    public function edit(int $id): void
    {
        $this->view('proveedores/form', ['title' => 'Editar proveedor', 'proveedor' => $this->orFail($this->proveedores->find($id))]);
    }

    /** POST /proveedores/{id} */
    public function update(int $id): void
    {
        $actual = $this->orFail($this->proveedores->find($id));
        $d = $this->datos();
        $v = $this->validar($d, $id);
        if ($v->fails()) {
            $this->fail("/proveedores/$id/editar", $v->errors(), $d);
        }
        $this->proveedores->actualizar($id, $d);
        $this->audit('editar', 'proveedor', $id, array_intersect_key($actual, $d), $d);
        $this->success('Cambios guardados.', '/proveedores');
    }

    /** POST /proveedores/{id}/estado */
    public function toggle(int $id): void
    {
        $proveedor = $this->orFail($this->proveedores->find($id));
        $activar = (int) $proveedor['activo'] === 0;
        $this->proveedores->cambiarEstado($id, $activar);
        $this->audit($activar ? 'activar' : 'desactivar', 'proveedor', $id, ['activo' => !$activar], ['activo' => $activar]);
        $this->success("Proveedor {$proveedor['nombre']} " . ($activar ? 'activado.' : 'desactivado: ya no aparecerá en las entradas de mercancía.'), '/proveedores');
    }

    private function datos(): array
    {
        $d = $this->only(['nit', 'nombre', 'telefono', 'email']);
        $d['nit'] = str_replace(['.', ' '], '', $d['nit']);
        $d['telefono'] = preg_replace('/[^0-9+]/', '', $d['telefono']);
        $d['email'] = mb_strtolower($d['email']);
        return $d;
    }

    private function validar(array $d, ?int $id): Validator
    {
        return (new Validator($d))
            ->rule('nit', 'required|max:20', 'El NIT')
            ->rule('nombre', 'required|min:3|max:120', 'El nombre')
            ->rule('email', 'email|max:150', 'El correo')
            ->check((bool) preg_match('/^[0-9]{6,12}(-[0-9])?$/', $d['nit']), 'nit', 'NIT inválido: entre 6 y 12 dígitos, con dígito de verificación opcional (900123456-7).')
            ->check($d['telefono'] === '' || (bool) preg_match('/^\+?[0-9]{7,15}$/', $d['telefono']), 'telefono', 'El teléfono debe tener entre 7 y 15 dígitos.')
            ->check(!$this->proveedores->nitEnUso($d['nit'], $id), 'nit', 'Ya existe un proveedor con ese NIT.');
    }
}

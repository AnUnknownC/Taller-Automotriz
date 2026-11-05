<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\ListParams;
use App\Core\Validator;
use App\Models\Usuario;
use App\Services\AuthService;

/**
 * RF-03: gestión de usuarios (solo administrador; ver config/routes.php).
 * Reglas:
 *  - Nadie cambia su propio rol ni se desactiva a sí mismo (evita autoasignarse permisos o bloquearse).
 *  - Siempre queda al menos un administrador activo.
 *  - Las cuentas de cliente se crean y vinculan desde el módulo Clientes; aquí su rol no cambia.
 */
final class UsuarioController extends Controller
{
    private Usuario $usuarios;

    public function __construct()
    {
        $this->usuarios = new Usuario();
    }

    /** GET /usuarios */
    public function index(): void
    {
        $p = ListParams::fromRequest(Usuario::columnasOrden(), 'nombre');
        $rol = $p->filter('rol', Usuario::ROLES);
        $estado = $p->filter('estado', ['activos', 'inactivos', 'todos'], 'activos');

        $this->view('usuarios/index', [
            'title' => 'Usuarios',
            'res'   => $this->usuarios->listar($p, $rol, $estado),
            'p'     => $p,
        ]);
    }

    /** GET /usuarios/crear */
    public function create(): void
    {
        $this->view('usuarios/form', ['title' => 'Nuevo usuario', 'usuario' => null]);
    }

    /** POST /usuarios */
    public function store(): void
    {
        $datos = $this->datos();
        $v = $this->validar($datos, null);
        if ($v->fails()) {
            $this->fail('/usuarios/crear', $v->errors(), $datos);
        }

        $id = $this->usuarios->crear([
            'nombre' => $datos['nombre'], 'email' => $datos['email'], 'rol' => $datos['rol'],
            'password_hash' => AuthService::hash($datos['password']),
        ]);
        $this->audit('crear', 'usuario', $id, null, $datos);
        $this->success("Usuario {$datos['nombre']} creado.", '/usuarios');
    }

    /** GET /usuarios/{id}/editar */
    public function edit(int $id): void
    {
        $usuario = $this->orFail($this->usuarios->find($id));
        $this->view('usuarios/form', ['title' => 'Editar usuario', 'usuario' => $usuario]);
    }

    /** POST /usuarios/{id} */
    public function update(int $id): void
    {
        $actual = $this->orFail($this->usuarios->find($id));
        $datos = $this->datos();

        // El rol no se toma del formulario si no se puede cambiar (aunque alguien manipule el POST)
        $rolBloqueado = $actual['rol'] === 'cliente' || $id === Auth::id();
        if ($rolBloqueado) {
            $datos['rol'] = $actual['rol'];
        }

        $v = $this->validar($datos, $actual);
        $v->check(
            !($actual['rol'] === 'administrador' && $datos['rol'] !== 'administrador'
              && (int) $actual['activo'] === 1 && $this->usuarios->contarAdminsActivos() <= 1),
            'rol',
            'Es el único administrador activo: no se le puede quitar el rol.'
        );
        if ($v->fails()) {
            $this->fail("/usuarios/$id/editar", $v->errors(), $datos);
        }

        $this->usuarios->actualizar($id, [
            'nombre' => $datos['nombre'], 'email' => $datos['email'], 'rol' => $datos['rol'],
            'password_hash' => $datos['password'] !== '' ? AuthService::hash($datos['password']) : '',
        ]);
        $this->audit('editar', 'usuario', $id,
            array_intersect_key($actual, array_flip(['nombre', 'email', 'rol'])),
            ['nombre' => $datos['nombre'], 'email' => $datos['email'], 'rol' => $datos['rol'],
             'cambio_contrasena' => $datos['password'] !== '']);
        $this->success('Cambios guardados.', '/usuarios');
    }

    /** POST /usuarios/{id}/estado (activar / desactivar; eliminación lógica) */
    public function toggle(int $id): void
    {
        $usuario = $this->orFail($this->usuarios->find($id));
        $activar = (int) $usuario['activo'] === 0;

        if ($id === Auth::id()) {
            $this->error('No puedes desactivar tu propia cuenta.', '/usuarios');
        }
        if (!$activar && $usuario['rol'] === 'administrador' && $this->usuarios->contarAdminsActivos() <= 1) {
            $this->error('No se puede desactivar al único administrador activo.', '/usuarios');
        }

        $this->usuarios->cambiarEstado($id, $activar);
        $this->audit($activar ? 'activar' : 'desactivar', 'usuario', $id, ['activo' => !$activar], ['activo' => $activar]);
        $this->success("Usuario {$usuario['nombre']} " . ($activar ? 'activado.' : 'desactivado: ya no puede iniciar sesión.'), '/usuarios');
    }

    private function datos(): array
    {
        $datos = $this->only(['nombre', 'email', 'rol']);
        $datos['email'] = mb_strtolower($datos['email']);
        // Las contraseñas no se recortan: un espacio es un carácter válido
        $datos['password'] = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $datos['password_confirm'] = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
        return $datos;
    }

    private function validar(array $d, ?array $actual): Validator
    {
        $roles = $actual && $actual['rol'] === 'cliente' ? Usuario::ROLES : Usuario::ROLES_INTERNOS;
        $id = $actual ? (int) $actual['id'] : null;

        return (new Validator($d))
            ->rule('nombre', 'required|min:3|max:100', 'El nombre')
            ->rule('email', 'required|email|max:150', 'El correo')
            ->rule('rol', 'required|in:' . implode(',', $roles), 'El rol')
            ->rule('password', ($actual ? '' : 'required|') . 'password', 'La contraseña')
            ->rule('password_confirm', ($d['password'] !== '' ? 'required|' : '') . 'same:password', 'La confirmación de contraseña')
            ->check(!$this->usuarios->emailEnUso($d['email'], $id), 'email', 'Ya existe un usuario con ese correo.');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\FormState;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\AuthService;
use PDOException;

/**
 * Autorregistro de clientes desde el login (solo visitantes sin sesión).
 *
 * Decisiones de seguridad:
 *  - Solo crea cuentas con rol CLIENTE: el rol no viene del formulario.
 *  - Si el documento o el correo ya existen, el mensaje es genérico (no revela cuál) para no
 *    permitir averiguar quién es cliente del taller (enumeración).
 *  - NO se vincula a un cliente que el taller ya registró: quien conozca la cédula de otra persona
 *    podría ver sus vehículos y aprobar sus cotizaciones. Ese cliente pide su acceso en recepción.
 *  - Anti-abuso: campo trampa invisible (honeypot) y máximo 5 registros por IP por hora.
 *  - Cliente + cuenta en una transacción (Cliente::crear) y sesión nueva al terminar.
 */
final class RegistroController extends Controller
{
    private const MAX_POR_HORA = 5;
    private const TIPOS_PERMITIDOS = ['CC', 'CE', 'PAS', 'NIT'];
    private const MENSAJE_DUPLICADO = 'No pudimos crear la cuenta con esos datos. Si ya tienes cuenta, inicia sesión. '
        . 'Si ya eres cliente del taller, pide en recepción que activen tu acceso al portal.';

    /** GET /registro */
    public function show(): void
    {
        $this->view('auth/registro', ['title' => 'Crear cuenta'], 'auth');
    }

    /** POST /registro */
    public function store(): void
    {
        $d = $this->datos();

        // Honeypot: un humano no ve ni llena este campo; un bot sí
        if (($_POST['sitio_web'] ?? '') !== '') {
            $this->error('No se pudo completar el registro.', '/registro');
        }
        if ((new Auditoria())->contarRecientesPorIp('registro_cliente', Request::ip(), 60) >= self::MAX_POR_HORA) {
            FormState::flash($d, []);
            $this->error('Se crearon demasiadas cuentas desde esta conexión. Intenta más tarde.', '/registro');
        }

        $v = $this->validar($d);
        if ($v->fails()) {
            $this->fail('/registro', $v->errors(), $d);
        }

        $clientes = new Cliente();
        if ($clientes->documentoEnUso($d['tipo_documento'], $d['documento']) || (new Usuario())->emailEnUso($d['email'])) {
            FormState::flash($d, []);
            $this->error(self::MENSAJE_DUPLICADO, '/registro');
        }

        try {
            $clienteId = $clientes->crear(
                ['tipo_documento' => $d['tipo_documento'], 'documento' => $d['documento'], 'nombre' => $d['nombre'],
                 'telefono' => $d['telefono'], 'email' => $d['email'], 'direccion' => ''],
                ['password_hash' => AuthService::hash($d['password'])]
            );
        } catch (PDOException $e) {
            // Dos registros simultáneos con el mismo dato: la restricción UNIQUE de la BD gana
            if ($e->getCode() === '23000') {
                FormState::flash($d, []);
                $this->error(self::MENSAJE_DUPLICADO, '/registro');
            }
            throw $e;
        }

        $usuario = (new Usuario())->findActive((int) $clientes->find($clienteId)['usuario_id']);
        Auth::login($usuario);   // regenera el ID de sesión y el token CSRF
        (new Auditoria())->registrar((int) $usuario['id'], 'registro_cliente', 'cliente', $clienteId, null,
            ['tipo_documento' => $d['tipo_documento'], 'email' => $d['email'], 'canal' => 'autorregistro']);

        Session::flash('success', "¡Bienvenido, {$d['nombre']}! Tu cuenta quedó creada. "
            . 'Cuando traigas tu vehículo al taller verás aquí su estado y sus cotizaciones.');
        $this->redirect('/dashboard');
    }

    private function datos(): array
    {
        $d = $this->only(['tipo_documento', 'documento', 'nombre', 'telefono', 'email']);
        $d['documento'] = Cliente::normalizarDocumento($d['documento']);
        $d['telefono'] = Cliente::normalizarTelefono($d['telefono']);
        $d['email'] = mb_strtolower($d['email']);
        $d['password'] = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $d['password_confirm'] = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
        $d['acepta_datos'] = isset($_POST['acepta_datos']) ? '1' : '';
        return $d;
    }

    private function validar(array $d): Validator
    {
        return (new Validator($d))
            ->rule('tipo_documento', 'required|in:' . implode(',', self::TIPOS_PERMITIDOS), 'El tipo de documento')
            ->rule('documento', 'required|max:20', 'El documento')
            ->rule('nombre', 'required|min:3|max:120', 'El nombre')
            ->rule('telefono', 'required', 'El teléfono')
            ->rule('email', 'required|email|max:150', 'El correo')
            ->rule('password', 'required|password', 'La contraseña')
            ->rule('password_confirm', 'required|same:password', 'La confirmación de contraseña')
            ->check(Cliente::documentoValido($d['tipo_documento'], $d['documento']), 'documento',
                Cliente::mensajeDocumentoInvalido($d['tipo_documento']))
            ->check(Cliente::telefonoValido($d['telefono']), 'telefono', 'El teléfono debe tener entre 7 y 15 dígitos.')
            ->check($d['acepta_datos'] === '1', 'acepta_datos', 'Debes autorizar el tratamiento de tus datos para crear la cuenta.');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Auditoria;
use App\Services\AuthService;

final class AuthController extends Controller
{
    /** GET /login */
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Iniciar sesión'], 'auth');
    }

    /** POST /login */
    public function login(): void
    {
        $email = (string) Request::input('email', '');
        $password = (string) ($_POST['password'] ?? '');   // la contraseña no se recorta

        $v = (new Validator(['email' => $email, 'password' => $password]))
            ->rule('email', 'required|email|max:150', 'El correo')
            ->rule('password', 'required|max:255', 'La contraseña');

        if ($v->fails()) {
            \App\Core\FormState::flash(['email' => $email], []);
            Session::flash('danger', implode(' ', $v->errors()));
            $this->redirect('/login');
        }

        $resultado = (new AuthService())->intentar($email, $password, Request::ip());

        if (!$resultado['ok']) {
            \App\Core\FormState::flash(['email' => $email], []);
            Session::flash('danger', $resultado['error']);
            $this->redirect('/login');
        }

        Session::flash('success', 'Bienvenido, ' . Auth::user()['nombre'] . '.');
        $this->redirect('/dashboard');
    }

    /** POST /logout (POST + CSRF: un enlace externo no puede cerrar tu sesión) */
    public function logout(): void
    {
        (new Auditoria())->registrar(Auth::id(), 'cierre_sesion', 'usuario', Auth::id());
        Auth::logout();
        Session::flash('info', 'Cerraste sesión correctamente.');
        $this->redirect('/login');
    }

    /** GET / */
    public function home(): void
    {
        $this->redirect(Auth::check() ? '/dashboard' : '/login');
    }
}

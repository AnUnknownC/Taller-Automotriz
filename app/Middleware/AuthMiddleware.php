<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Session;

/** Solo usuarios autenticados y activos. */
final class AuthMiddleware implements Middleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            Session::flash('warning', 'Inicia sesión para continuar.');
            header('Location: ' . url('/login'), true, 303);
            exit;
        }
    }
}

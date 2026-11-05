<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

/** Solo visitantes sin sesión (p. ej. la pantalla de login). */
final class GuestMiddleware implements Middleware
{
    public function handle(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'), true, 303);
            exit;
        }
    }
}

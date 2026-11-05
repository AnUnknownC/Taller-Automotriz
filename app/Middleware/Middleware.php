<?php
declare(strict_types=1);

namespace App\Middleware;

/** Filtro que se ejecuta antes del controlador. Corta la petición lanzando excepción o redirigiendo. */
interface Middleware
{
    public function handle(): void;
}

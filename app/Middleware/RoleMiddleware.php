<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Models\Auditoria;

/**
 * RS-04: autorización en el servidor. Ocultar un botón NO protege nada;
 * esta verificación sí, aunque el usuario escriba la URL a mano.
 */
final class RoleMiddleware implements Middleware
{
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function handle(): void
    {
        if (!Auth::check()) {
            (new AuthMiddleware())->handle();
        }
        if (!Auth::hasRole(...$this->roles)) {
            (new Auditoria())->registrar(Auth::id(), 'acceso_denegado', 'ruta', null, null, [
                'ruta' => Request::method() . ' ' . Request::path(),
                'rol'  => Auth::role(),
            ]);
            throw new HttpException(403, 'Tu rol no tiene permiso para esta sección.');
        }
    }
}

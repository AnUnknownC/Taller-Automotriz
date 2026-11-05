<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;

/** RS-05: todo POST sin token válido se rechaza con 419. */
final class CsrfMiddleware implements Middleware
{
    public function handle(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            throw new HttpException(419, 'El formulario expiró o no es válido. Recarga la página e intenta de nuevo.');
        }
    }
}

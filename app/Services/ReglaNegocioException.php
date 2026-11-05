<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Una regla del taller impidió la operación. El mensaje es apto para mostrar al usuario. */
final class ReglaNegocioException extends RuntimeException
{
}

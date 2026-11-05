<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * RF-12 / RS-08: toda excepción termina en una pantalla 403/404/405/419/500 amigable.
 * El detalle técnico solo va al log (storage/logs), nunca al usuario (salvo APP_DEBUG=true).
 */
final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        $status = $e instanceof HttpException ? $e->status() : 500;

        if ($status >= 500) {
            Logger::error($e);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($status);
        }

        $debug = Env::get('APP_DEBUG') === 'true';
        $detail = ($debug && $status >= 500) ? get_class($e) . ': ' . $e->getMessage()
            . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : null;

        try {
            View::render('errors/error', [
                'status'  => $status,
                'message' => $e instanceof HttpException ? $e->getMessage() : '',
                'detail'  => $detail,
                'title'   => 'Error ' . $status,
            ], 'error');
        } catch (Throwable $inner) {
            // Último recurso si la vista también falla
            echo '<h1>Error ' . $status . '</h1><p>Ocurrió un problema. Intenta de nuevo.</p>';
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Log técnico en storage/logs (fuera de public/). Sin contraseñas ni datos de formularios. */
final class Logger
{
    public static function error(Throwable $e): void
    {
        $line = sprintf(
            "[%s] %s: %s en %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}

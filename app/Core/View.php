<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/** Renderiza una vista dentro de un layout. Las vistas solo presentan: nada de SQL aquí. */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::capture('partials/' . $view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}

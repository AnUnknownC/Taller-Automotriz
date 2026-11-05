<?php
declare(strict_types=1);

namespace App\Core;

/**
 * RF-08: parámetros de un listado (búsqueda, filtros, orden, página) leídos de la URL.
 * Todo valor pasa por lista blanca: la columna de orden nunca es texto libre del usuario.
 */
final class ListParams
{
    public string $q = '';
    public string $sort;
    public string $dir;
    public int $page = 1;
    public int $perPage;
    public array $filters = [];

    public static function fromRequest(array $sortable, string $defaultSort, string $defaultDir = 'asc', int $perPage = 10): self
    {
        $p = new self();
        $p->q = mb_substr(Request::query('q'), 0, 100);
        $sort = Request::query('sort');
        $p->sort = in_array($sort, $sortable, true) ? $sort : $defaultSort;
        $dir = Request::query('dir');
        $p->dir = in_array($dir, ['asc', 'desc'], true) ? $dir : $defaultDir;
        $p->page = max(1, (int) Request::query('pagina', '1'));
        $p->perPage = $perPage;
        return $p;
    }

    /** Lee un filtro permitido; cualquier otro valor vuelve al predeterminado. */
    public function filter(string $key, array $allowed, string $default = ''): string
    {
        $value = Request::query($key, $default);
        $value = in_array($value, $allowed, true) ? $value : $default;
        $this->filters[$key] = $value;
        return $value;
    }

    /** Parámetros para construir enlaces (orden, paginación) conservando los filtros. */
    public function query(array $overrides = []): array
    {
        $base = ['q' => $this->q] + $this->filters + ['sort' => $this->sort, 'dir' => $this->dir, 'pagina' => $this->page];
        return array_filter(array_merge($base, $overrides), fn ($v) => $v !== null && $v !== '');
    }
}

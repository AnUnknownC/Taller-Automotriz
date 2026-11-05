<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\FormState;
use App\Core\ListParams;
use App\Core\Request;
use App\Core\Session;

/** RS-06: escapar TODA salida dinámica en las vistas. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/', array $query = []): string
{
    $url = Request::baseUrl() . '/' . ltrim($path, '/');
    $url = $url === '' ? '/' : $url;
    return $query ? $url . '?' . http_build_query($query) : $url;
}

function asset(string $path): string
{
    return Request::baseUrl() . '/assets/' . ltrim($path, '/');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/**
 * Valor de un campo: lo que el usuario escribió (si el formulario se rechazó)
 * o el valor por defecto (p. ej. el dato actual en la BD al editar).
 */
function old(string $key, $default = ''): string
{
    if (FormState::hasOld()) {
        return (string) (FormState::old($key) ?? '');
    }
    return (string) ($default ?? '');
}

function old_checked(string $key, bool $default = false): bool
{
    return FormState::hasOld() ? (bool) FormState::old($key) : $default;
}

/** Clase de Bootstrap para un campo con error. */
function invalid(string $key): string
{
    return FormState::error($key) !== null ? ' is-invalid' : '';
}

/** Atributos ARIA que asocian el campo con su mensaje de error (RNF-04). */
function aria_error(string $key): string
{
    return FormState::error($key) !== null ? 'aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

function field_error(string $key): string
{
    $error = FormState::error($key);
    return $error === null ? '' : '<div class="invalid-feedback d-block" id="err-' . e($key) . '">' . e($error) . '</div>';
}

/** Encabezado de columna ordenable (RF-08). */
function sort_link(string $label, string $key, ListParams $p, string $route): string
{
    $active = $p->sort === $key;
    $dir = ($active && $p->dir === 'asc') ? 'desc' : 'asc';
    $icon = $active ? ($p->dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    $href = url($route, $p->query(['sort' => $key, 'dir' => $dir, 'pagina' => null]));
    return '<a class="sort-link' . ($active ? ' active' : '') . '" href="' . e($href) . '">' . e($label)
        . ' <i class="bi ' . $icon . '" aria-hidden="true"></i></a>';
}

function aria_sort(string $key, ListParams $p): string
{
    return $p->sort === $key ? 'aria-sort="' . ($p->dir === 'asc' ? 'ascending' : 'descending') . '"' : '';
}

function activo_badge($activo): string
{
    return (int) $activo === 1
        ? '<span class="estado estado-aprobada">Activo</span>'
        : '<span class="estado estado-recibida">Inactivo</span>';
}

function app_name(): string
{
    return (string) Config::get('app.name', 'Taller');
}

function current_user(): ?array
{
    return Auth::user();
}

function is_current(string $path): bool
{
    return Request::path() === $path || strpos(Request::path(), rtrim($path, '/') . '/') === 0;
}

function fecha(?string $value, string $format = 'd/m/Y H:i'): string
{
    return $value ? date($format, strtotime($value)) : '—';
}

/** Insignia de estado de una orden (texto + color: el color nunca va solo). */
function estado_badge(string $estado): string
{
    $label = App\Models\OrdenTrabajo::ESTADOS[$estado] ?? $estado;
    return '<span class="estado estado-' . e($estado) . '">' . e($label) . '</span>';
}

/** Placa con el estilo de las placas colombianas (amarillo y negro). */
function placa(string $placa): string
{
    return '<span class="placa">' . e($placa) . '</span>';
}

/** Valor en pesos colombianos: $1.234.567 */
function pesos($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

function rol_label(?string $rol): string
{
    $labels = [
        'administrador' => 'Administrador',
        'asesor'        => 'Asesor de servicio',
        'tecnico'       => 'Técnico',
        'cliente'       => 'Cliente',
    ];
    return $labels[$rol] ?? (string) $rol;
}

<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Auditoria;

/** Controlador base: coordina, no contiene HTML ni SQL. */
abstract class Controller
{
    /** Campos que jamás se devuelven al formulario ni se auditan. */
    private const SECRETOS = ['password', 'password_confirm', 'acceso_password', 'acceso_password_confirm', '_csrf'];

    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path, array $query = []): void
    {
        header('Location: ' . url($path, $query), true, 303);
        exit;
    }

    /** Validación fallida: vuelve al formulario con los datos y errores (patrón PRG). */
    protected function fail(string $path, array $errors, array $old): void
    {
        foreach (self::SECRETOS as $campo) {
            unset($old[$campo]);
        }
        FormState::flash($old, $errors);
        Session::flash('danger', 'No se guardó: revisa ' . (count($errors) === 1 ? 'el campo marcado.' : 'los ' . count($errors) . ' campos marcados.'));
        $this->redirect($path);
    }

    protected function success(string $message, string $path, array $query = []): void
    {
        Session::flash('success', $message);
        $this->redirect($path, $query);
    }

    protected function error(string $message, string $path, array $query = []): void
    {
        Session::flash('danger', $message);
        $this->redirect($path, $query);
    }

    /** RF-11: registra la operación crítica con el estado antes/después. */
    protected function audit(string $accion, string $entidad, ?int $id, ?array $antes = null, ?array $despues = null): void
    {
        (new Auditoria())->registrar(Auth::id(), $accion, $entidad, $id, $antes, $despues);
    }

    /** 404 si el registro no existe (también cubre IDs manipulados en la URL). */
    protected function orFail(?array $registro): array
    {
        if ($registro === null) {
            throw new HttpException(404, 'El registro solicitado no existe.');
        }
        return $registro;
    }

    /** Toma solo los campos esperados del POST (lista blanca, evita asignación masiva). */
    protected function only(array $campos): array
    {
        $datos = [];
        foreach ($campos as $campo) {
            $valor = $_POST[$campo] ?? '';
            $datos[$campo] = is_string($valor) ? trim($valor) : '';
        }
        return $datos;
    }
}

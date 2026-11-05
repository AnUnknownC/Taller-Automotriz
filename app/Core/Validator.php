<?php
declare(strict_types=1);

namespace App\Core;

/**
 * RF-10: validación autoritativa en servidor, reutilizable en todos los formularios.
 *   $v = new Validator($_POST);
 *   $v->rule('email', 'required|email|max:150', 'Correo');
 *   if ($v->fails()) { ... $v->errors() ... }
 * Reglas: required, email, min:N, max:N, integer, numeric, in:a,b,c, between:a,b, password, same:campo
 * Reglas del dominio: $v->check($condicion, 'campo', 'mensaje')
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function rule(string $field, string $rules, string $label): self
    {
        $raw = $this->data[$field] ?? '';
        $value = is_string($raw) ? trim($raw) : $raw;

        foreach (explode('|', $rules) as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

            if ($name !== 'required' && ($value === '' || $value === null)) {
                continue; // campos opcionales vacíos no se validan
            }
            if (!is_string($value) && $name !== 'required') {
                $this->errors[$field] = "$label tiene un formato inválido.";
                break;
            }

            $error = null;
            switch ($name) {
                case 'required':
                    if ($value === '' || $value === null) {
                        $error = 'Falta ' . mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1) . '.';
                    }
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $error = "$label no es un correo válido.";
                    }
                    break;
                case 'min':
                    if (mb_strlen($value) < (int) $arg) {
                        $error = "$label debe tener al menos $arg caracteres.";
                    }
                    break;
                case 'max':
                    if (mb_strlen($value) > (int) $arg) {
                        $error = "$label admite máximo $arg caracteres.";
                    }
                    break;
                case 'integer':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $error = "$label debe ser un número entero.";
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        $error = "$label debe ser numérico.";
                    }
                    break;
                case 'in':
                    if (!in_array($value, explode(',', (string) $arg), true)) {
                        $error = "$label tiene un valor no permitido.";
                    }
                    break;
                case 'between':
                    [$min, $max] = array_map('intval', explode(',', (string) $arg));
                    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min || (int) $value > $max) {
                        $error = "$label debe estar entre $min y $max.";
                    }
                    break;
                case 'password':
                    if (mb_strlen($value) < 8 || mb_strlen($value) > 64
                        || !preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
                        $error = "$label debe tener entre 8 y 64 caracteres, con letras y números.";
                    }
                    break;
                case 'same':
                    if ($value !== (string) ($this->data[$arg] ?? '')) {
                        $error = "$label no coincide.";
                    }
                    break;
            }

            if ($error !== null) {
                $this->errors[$field] = $error;
                break; // un error por campo
            }
        }
        return $this;
    }

    /** Regla propia del dominio: si $ok es falso, marca el error (si el campo aún no tiene uno). */
    public function check(bool $ok, string $field, string $message): self
    {
        if (!$ok && !isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

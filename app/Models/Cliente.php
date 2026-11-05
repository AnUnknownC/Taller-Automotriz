<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ListParams;
use App\Core\Model;

final class Cliente extends Model
{
    public const TIPOS_DOCUMENTO = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'NIT' => 'NIT', 'PAS' => 'Pasaporte'];

    private const ORDEN = [
        'nombre'    => 'c.nombre',
        'documento' => 'c.documento',
        'vehiculos' => 'vehiculos',
        'creado'    => 'c.created_at',
    ];

    /** Documento sin puntos ni espacios, en mayúsculas (1.032.456.789 → 1032456789). */
    public static function normalizarDocumento(string $documento): string
    {
        return strtoupper(str_replace(['.', ' '], '', trim($documento)));
    }

    /** Teléfono solo con dígitos y + (310 555 1234 → 3105551234). */
    public static function normalizarTelefono(string $telefono): string
    {
        return (string) preg_replace('/[^0-9+]/', '', $telefono);
    }

    /** CC y NIT: 5 a 10 dígitos (NIT con dígito de verificación opcional). CE y pasaporte: 5 a 15 letras o números. */
    public static function documentoValido(string $tipo, string $documento): bool
    {
        $patron = in_array($tipo, ['CC', 'NIT'], true) ? '/^[0-9]{5,10}(-[0-9])?$/' : '/^[A-Z0-9]{5,15}$/';
        return (bool) preg_match($patron, $documento);
    }

    public static function mensajeDocumentoInvalido(string $tipo): string
    {
        return in_array($tipo, ['CC', 'NIT'], true)
            ? 'El documento debe tener entre 5 y 10 dígitos (NIT con dígito de verificación opcional: 900123456-7).'
            : 'El documento debe tener entre 5 y 15 letras o números.';
    }

    public static function telefonoValido(string $telefono): bool
    {
        return (bool) preg_match('/^\+?[0-9]{7,15}$/', $telefono);
    }

    public static function columnasOrden(): array
    {
        return array_keys(self::ORDEN);
    }

    public function listar(ListParams $p, string $estado): array
    {
        $where = [];
        $params = [];
        if ($p->q !== '') {
            $where[] = '(c.nombre LIKE :q1 OR c.documento LIKE :q2 OR c.telefono LIKE :q3 OR c.email LIKE :q4)';
            $like = $this->like($p->q);
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        if ($estado !== 'todos') {
            $where[] = 'c.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }

        return $this->paginate(
            'SELECT c.id, c.tipo_documento, c.documento, c.nombre, c.telefono, c.email, c.activo, c.usuario_id,
                    (SELECT COUNT(*) FROM vehiculos v WHERE v.cliente_id = c.id AND v.activo = 1) AS vehiculos',
            'FROM clientes c' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''),
            $params,
            self::ORDEN[$p->sort] . ' ' . strtoupper($p->dir) . ', c.id',
            $p
        );
    }

    /** Incluye los datos de su cuenta de acceso al portal, si tiene. */
    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT c.*, u.email AS acceso_email, u.activo AS acceso_activo
             FROM clientes c LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = :id',
            ['id' => $id]
        );
    }

    public function documentoEnUso(string $tipo, string $documento, ?int $exceptoId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM clientes WHERE tipo_documento = :tipo AND documento = :doc AND id <> :excepto',
            ['tipo' => $tipo, 'doc' => $documento, 'excepto' => $exceptoId ?? 0]
        );
    }

    /**
     * RNF-07: cliente + (opcional) cuenta de acceso en una sola transacción.
     * @param array|null $acceso ['password_hash' => ...] para crear la cuenta del portal
     */
    public function crear(array $d, ?array $acceso): int
    {
        return $this->transaction(function () use ($d, $acceso): int {
            $usuarioId = $acceso ? $this->crearCuenta($d, $acceso['password_hash']) : null;
            $this->query(
                'INSERT INTO clientes (usuario_id, tipo_documento, documento, nombre, telefono, email, direccion)
                 VALUES (:usuario, :tipo, :doc, :nombre, :tel, :email, :dir)',
                $this->params($d) + ['usuario' => $usuarioId]
            );
            return (int) $this->db->lastInsertId();
        });
    }

    /** Actualiza el cliente, sincroniza su cuenta (nombre/correo) o le crea una nueva. */
    public function actualizar(int $id, array $actual, array $d, ?array $accesoNuevo): void
    {
        $this->transaction(function () use ($id, $actual, $d, $accesoNuevo): void {
            $usuarioId = $actual['usuario_id'] ? (int) $actual['usuario_id'] : null;
            if ($usuarioId) {
                (new Usuario())->actualizarContacto($usuarioId, $d['nombre'], (string) $d['email']);
            } elseif ($accesoNuevo) {
                $usuarioId = $this->crearCuenta($d, $accesoNuevo['password_hash']);
            }
            $this->query(
                'UPDATE clientes SET usuario_id = :usuario, tipo_documento = :tipo, documento = :doc, nombre = :nombre,
                        telefono = :tel, email = :email, direccion = :dir
                 WHERE id = :id',
                $this->params($d) + ['usuario' => $usuarioId, 'id' => $id]
            );
        });
    }

    /**
     * Desactivar el cliente también desactiva su acceso al portal y sus vehículos.
     * Al reactivarlo se devuelve el acceso; los vehículos se reactivan uno a uno.
     */
    public function cambiarEstado(array $cliente, bool $activo): void
    {
        $this->transaction(function () use ($cliente, $activo): void {
            $this->query('UPDATE clientes SET activo = :activo WHERE id = :id',
                ['activo' => $activo ? 1 : 0, 'id' => (int) $cliente['id']]);
            if ($cliente['usuario_id']) {
                (new Usuario())->cambiarEstado((int) $cliente['usuario_id'], $activo);
            }
            if (!$activo) {
                $this->query('UPDATE vehiculos SET activo = 0 WHERE cliente_id = :id', ['id' => (int) $cliente['id']]);
            }
        });
    }

    /** Órdenes no entregadas de cualquiera de sus vehículos. */
    public function ordenesAbiertas(int $id): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM ordenes_trabajo o JOIN vehiculos v ON v.id = o.vehiculo_id
             WHERE v.cliente_id = :id AND " . OrdenTrabajo::sqlAbiertas('o'),
            ['id' => $id]
        );
    }

    /** Para el selector del formulario de vehículos. */
    public function activosParaSelect(): array
    {
        return $this->fetchAll(
            'SELECT id, nombre, tipo_documento, documento FROM clientes WHERE activo = 1 ORDER BY nombre'
        );
    }

    private function crearCuenta(array $d, string $hash): int
    {
        return (new Usuario())->crear([
            'rol' => 'cliente', 'nombre' => $d['nombre'], 'email' => (string) $d['email'], 'password_hash' => $hash,
        ]);
    }

    private function params(array $d): array
    {
        return [
            'tipo'   => $d['tipo_documento'],
            'doc'    => $d['documento'],
            'nombre' => $d['nombre'],
            'tel'    => $d['telefono'],
            'email'  => $d['email'] !== '' ? $d['email'] : null,
            'dir'    => $d['direccion'] !== '' ? $d['direccion'] : null,
        ];
    }
}

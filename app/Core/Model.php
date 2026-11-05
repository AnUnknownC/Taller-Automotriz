<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/** Modelo base: toda consulta pasa por prepare() + parámetros enlazados (RS-02). */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** Prepara y ejecuta. Los enteros se enlazan como PARAM_INT (necesario en LIMIT/OFFSET). */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** @return mixed */
    protected function fetchValue(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * RF-08 / RNF-06: listado paginado en la BD (LIMIT/OFFSET), nunca en PHP.
     * $orderBy viene de una lista blanca del modelo, no del usuario.
     * @return array{rows:array,total:int,page:int,pages:int}
     */
    protected function paginate(string $select, string $fromWhere, array $params, string $orderBy, ListParams $p): array
    {
        $total = (int) $this->fetchValue("SELECT COUNT(*) $fromWhere", $params);
        $pages = max(1, (int) ceil($total / $p->perPage));
        $page = min($p->page, $pages);
        $rows = $this->fetchAll(
            "$select $fromWhere ORDER BY $orderBy LIMIT :_limit OFFSET :_offset",
            $params + ['_limit' => $p->perPage, '_offset' => ($page - 1) * $p->perPage]
        );
        return compact('rows', 'total', 'page', 'pages');
    }

    /** Texto de búsqueda para LIKE: % y _ del usuario se tratan como literales. */
    protected function like(string $q): string
    {
        return '%' . addcslashes($q, '%_\\') . '%';
    }

    /**
     * RNF-07: ejecuta varias operaciones como una sola unidad.
     * Si algo falla se revierte todo y no quedan registros a medio guardar.
     * @return mixed
     */
    protected function transaction(callable $callback)
    {
        if ($this->db->inTransaction()) {   // transacción anidada: se une a la externa
            return $callback($this->db);
        }
        $this->db->beginTransaction();
        try {
            $result = $callback($this->db);
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

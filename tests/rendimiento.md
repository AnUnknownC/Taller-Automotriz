# Rendimiento (RNF-06)

Medido el 05/11/2026 en el entorno local (PHP 8 + MariaDB, servidor de desarrollo de PHP).

**Método:** mediana de 15 peticiones por pantalla con sesión iniciada (`curl -w '%{time_total}'`).

## Tiempos de respuesta

| Pantalla | Datos de demostración (40 órdenes) | 20.040 órdenes, índices iniciales | 20.040 órdenes, optimizado |
|---|---|---|---|
| `/dashboard` (admin) | 3 ms | 22 ms | **7 ms** |
| `/ordenes` (abiertas) | 4 ms | 70 ms | **15 ms** |
| `/ordenes?q=KLM&sort=estado&dir=desc` | 3 ms | 84 ms | **11 ms** |
| `/ordenes/1` (ficha) | 3 ms | 11 ms | 11 ms |
| `/clientes` | 2 ms | 2 ms | 2 ms |
| `/repuestos/2` (kardex) | 4 ms | 3 ms | 3 ms |
| `/reportes/ordenes` | 5 ms | 18 ms | 15 ms |
| `/reportes/ingresos` | 3 ms | 3 ms | 3 ms |
| `/reportes/repuestos` | 3 ms | 3 ms | 2 ms |
| `/auditoria` (+20.000 registros) | 2 ms | 23 ms | 15 ms |

La carga de prueba se generó con `INSERT … SELECT FROM seq_1_to_20000` (motor Sequence de MariaDB) y se borró al terminar.

## Optimización aplicada

**Problema detectado con `EXPLAIN`:** el filtro "órdenes abiertas" usaba `estado <> 'entregada'`. Un `<>` no puede aprovechar un índice como rango, así que MariaDB recorría la tabla completa y ordenaba en memoria:

```
type=ALL  rows=20041  Extra: Using where; Using filesort
```

**Solución:**
1. La condición pasa a `estado IN ('recibida', …, 'lista')`, centralizada en `OrdenTrabajo::sqlAbiertas()`.
2. Índices compuestos nuevos:
   - `idx_ot_estado_fecha (estado, fecha_ingreso)`
   - `idx_ot_vehiculo_estado (vehiculo_id, estado)`, que acelera la regla R5.

```
type=range  key=idx_ot_estado_fecha     rows=8   Extra: Using where; Using index
type=range  key=idx_ot_vehiculo_estado  rows=7   Extra: Using where; Using index
```

**Resultado:** el listado de órdenes pasa de 70 a 15 ms (4,7 veces más rápido) y el tablero de 22 a 7 ms.

## Otras medidas que mantienen el rendimiento

- **Paginación en la base de datos** (`LIMIT`/`OFFSET` en `Model::paginate`), nunca en PHP.
- **Índices:**
  - Por fecha en órdenes, auditoría e intentos de login.
  - Por (repuesto, fecha) en el kardex.
  - `UNIQUE` en placa, documento, código y NIT.
- **Reportes agregados en SQL** (`GROUP BY`, `AVG`, `SUM`): no se traen filas para sumarlas en PHP.
- **Recursos estáticos locales y minificados** (Bootstrap e íconos), sin depender de CDN.

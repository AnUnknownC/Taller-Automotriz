# Pruebas · Sprint 4 (reportes, indicadores y CSV)

Ejecutadas el 05/11/2026 (PHP 8 + MariaDB + Chromium, `seed.sql` con 36 órdenes históricas).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas.

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P4-01 | Acceso a `/reportes/ordenes`, `/repuestos` e `/ingresos` (incluido el CSV) por rol | Órdenes y repuestos: admin y asesor. Ingresos: solo admin. Técnico y cliente: 403 | ✅ |
| P4-02 | Indicadores de 90 días comparados con SQL directo: total, días promedio, tasa de aprobación | Coinciden (38 · 2,8 días · 91 %) | ✅ |
| P4-03 | Total de ingresos simulados comparado con `SUM(liquidaciones.total)` | Coinciden | ✅ |
| P4-04 | El promedio por técnico usa el mismo criterio que el indicador general | Solo órdenes reparadas; valores consistentes | ✅ |
| P4-05 | Filtro por técnico (Julián) | Mismo conteo que en SQL | ✅ |
| P4-06 | Fechas invertidas / rango de más de un año / fecha inexistente | Aviso y vuelta a los últimos 30 días | ✅ |
| P4-07 | Inyección en `desde`, `estado`, `tecnico` y `sort` | 200; los valores se ignoran y la consulta no se altera | ✅ |
| P4-08 | Exportar CSV de órdenes | `text/csv`, BOM UTF-8, separador `;`, tildes correctas; 38 filas | ✅ |
| P4-09 | **Inyección de fórmulas:** cliente llamado `=HYPERLINK(...)` | En el CSV aparece como `'=HYPERLINK…` (texto, no fórmula) | ✅ |
| P4-10 | CSV de repuestos e ingresos (descarga real en el navegador) | Archivo `reporte_..._fecha.csv` | ✅ |
| P4-11 | Cada exportación queda auditada | `exportar_csv` con reporte y filtros | ✅ |
| P4-12 | Tablero del admin: ingresos de los últimos 30 días con enlace al reporte | Visible y enlazado | ✅ |
| P4-13 | Integridad del histórico: horario laboral, sin fechas futuras, eventos en orden, kardex = stock, saldo nunca negativo, km creciente | 0 inconsistencias | ✅ |
| P4-14 | Reportes y tablero a 360, 768 y 1366 px | Sin scroll horizontal ni errores de consola | ✅ |

# Pruebas · Sprint 2B (proveedores, repuestos, entradas, ajustes y kardex)

> **Nota:** los códigos de orden de este archivo corresponden a los datos de prueba de esa fecha. Desde el Sprint 4 la base incluye 36 órdenes históricas y las órdenes en curso son `OT-2026-0037` a `OT-2026-0040`. Para repetir estas pruebas sobre los datos actuales, usa la suite automática `tests/run_tests.php`.

Ejecutadas el 05/11/2026 (PHP 8 + MariaDB + Chromium, datos de `seed.sql`).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas.

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P2B-01 | Matriz de acceso a inventario, entrada, ajuste y proveedores por rol | Consultar: admin, asesor y técnico. Catálogo y ajustes: admin. Entradas: admin y asesor. Cliente: 403 | ✅ |
| P2B-02 | El técnico abre la ficha de un repuesto | Ve stock y precio, **no** el costo ni el margen | ✅ |
| P2B-03 | Proveedor con NIT duplicado (`800.111.222`), NIT `12AB`, teléfono de 2 dígitos | Error en cada campo | ✅ |
| P2B-04 | El asesor crea un proveedor por POST directo | 403 | ✅ |
| P2B-05 | Repuesto con código duplicado (en minúsculas), precio menor al costo, código `a!`, costo 0, mínimo -1 | Error en cada campo | ✅ |
| P2B-06 | Crear un repuesto enviando además `stock=99` | Nace con stock **0** (el campo se ignora) | ✅ |
| P2B-07 | Entrada con un proveedor inactivo | "Selecciona un proveedor activo." | ✅ |
| P2B-08 | Entrada con cantidad 0 y costo negativo | Errores por campo | ✅ |
| P2B-09 | Entrada de 3 unidades a $275.000 con factura | Stock +3; último costo actualizado; movimiento con proveedor, costo y nota | ✅ |
| P2B-10 | El técnico registra una entrada / el asesor hace un ajuste (POST) | 403 | ✅ |
| P2B-11 | Ajuste que dejaría el stock negativo | Bloqueado con el stock actual (R2) | ✅ |
| P2B-12 | Ajuste con motivo de 1 carácter | Error | ✅ |
| P2B-13 | Ajuste válido (-1, motivo "caja rota") | Stock 3→2; kardex y auditoría | ✅ |
| P2B-14 | Kardex: saldo después de cada movimiento + verificación de que la suma de movimientos = stock | Saldos correctos; "coincide" | ✅ |
| P2B-15 | Desactivar pastillas (cotizadas en OT-1, sin iniciar) | Bloqueado indicando la orden | ✅ |
| P2B-16 | Filtro "Solo en stock mínimo" y alerta del listado | Muestra COR-DIST y FIL-ACE-SPK (y los que bajen) | ✅ |
| P2B-17 | Subir el precio de las pastillas | La cotización de OT-1 conserva $98.000 (R9) | ✅ |
| P2B-18 | **Integración:** el técnico no puede iniciar por stock 0 → el asesor registra entrada (+10) → el técnico inicia | Bloqueo, entrada, salida con enlace a la OT; kardex cuadra (9) | ✅ |
| P2B-19 | Campos de dinero con valores como $66.000 en el navegador | Aceptados (bug de `step` corregido) | ✅ |
| P2B-20 | Pantallas de inventario a 360 y 768 px | Sin scroll horizontal ni errores de consola | ✅ |

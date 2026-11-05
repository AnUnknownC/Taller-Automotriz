# Pruebas · Sprint 3A (orden: recepción → diagnóstico → cotización)

> **Nota:** los códigos de orden de este archivo corresponden a los datos de prueba de esa fecha. Desde el Sprint 4 la base incluye 36 órdenes históricas y las órdenes en curso son `OT-2026-0037` a `OT-2026-0040`. Para repetir estas pruebas sobre los datos actuales, usa la suite automática `tests/run_tests.php`.

Ejecutadas el 05/11/2026 (PHP 8 + MariaDB + Chromium, datos de `seed.sql`).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas.

## Acceso y propiedad (RS-04)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3-01 | `/ordenes` con cada rol | Admin y asesor: 200. Técnico y cliente: 403 | ✅ |
| P3-02 | `/ordenes/crear` | Solo el asesor (el admin solo consulta) | ✅ |
| P3-03 | `/ordenes/1` (de Óscar) como `cliente@` | 403 auditado; `cliente2@` (dueño): 200 | ✅ |
| P3-04 | Orden asignada a Julián, abierta por Camilo | 403 auditado | ✅ |
| P3-05 | El asesor hace POST a `/diagnostico`; un técnico no asignado hace POST | 403 / bloqueado | ✅ |

## Recepción

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3-06 | Buscar `klm-482` (vehículo con orden abierta) | Aviso con enlace a OT-2026-0002 y sin formulario (R5) | ✅ |
| P3-07 | POST directo para abrir una segunda orden del mismo vehículo | "El vehículo ya tiene la orden … abierta" (R5) | ✅ |
| P3-08 | Placa inexistente | Mensaje y enlace para registrar el vehículo | ✅ |
| P3-09 | Motivo de 1 carácter, km negativo, fecha pasada | Error en cada campo | ✅ |
| P3-10 | Kilometraje menor al último ingreso | Bloqueado (R11) | ✅ |
| P3-11 | Crear una orden con km `20.150` | `OT-2026-0005` consecutivo, estado Recibida, km 20150 | ✅ |

## Asignación y diagnóstico

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3-12 | Asignar como "técnico" a un usuario con rol asesor | "Selecciona un técnico activo." | ✅ |
| P3-13 | Asignar técnico | Recibida → Diagnóstico, con historial y auditoría | ✅ |
| P3-14 | Cotizar sin diagnóstico | Bloqueado | ✅ |
| P3-15 | El técnico registra el diagnóstico desde el celular | Guardado; el formulario aparece primero en móvil | ✅ |
| P3-16 | El técnico intenta cambiar el diagnóstico con la orden ya cotizada | Bloqueado | ✅ |
| P3-17 | Reasignar al mismo técnico / a otro | Error / cambio registrado en el historial | ✅ |

## Cotización

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3-18 | Ítems inválidos (sin repuesto, cantidad 0, mano de obra sin valor, tipo "hack") | Un mensaje por fila, sin guardar nada | ✅ |
| P3-19 | **Precio manipulado:** enviar $1 por un aceite de $45.000 | Se guarda $45.000 del catálogo (R9) | ✅ |
| P3-20 | Totales: 4×45.000 + 22.000 + 2,5 h×45.000 | Subtotal 314.500 + IVA 59.755 = **374.255** | ✅ |
| P3-21 | Corregir la cotización mientras está pendiente | Reemplaza los ítems; historial "Cotización corregida" | ✅ |
| P3-22 | Cantidad mayor al stock en el editor | Aviso visible (no bloquea: el stock se descuenta al reparar) | ✅ |
| P3-23 | Totales en vivo con JS (agregar y quitar filas) | Coinciden con el servidor ($223.720) | ✅ |

## Listado

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3-24 | Filtros: estado, "sin asignar", texto + rango de fechas inválido (`2099-02-31`) | Filtran correctamente; la fecha inválida se ignora | ✅ |
| P3-25 | Ordenar por estado (secuencia del flujo) | Orden según el flujo, no alfabético | ✅ |
| P3-26 | 360, 768 y 1366 px en listado, ficha y editor | Sin scroll horizontal; sin errores de consola | ✅ |

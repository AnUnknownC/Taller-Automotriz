# Pruebas · Sprint 2A (usuarios, clientes, vehículos)

> **Nota:** los códigos de orden de este archivo corresponden a los datos de prueba de esa fecha. Desde el Sprint 4 la base incluye 36 órdenes históricas y las órdenes en curso son `OT-2026-0037` a `OT-2026-0040`. Para repetir estas pruebas sobre los datos actuales, usa la suite automática `tests/run_tests.php`.

Ejecutadas el 05/11/2026 en entorno local (PHP 8 + MariaDB, datos de `seed.sql`).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas (RNF-10).

## Permisos (RF-02 / RS-04)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P2-01 | Abrir por URL `/usuarios`, `/clientes`, `/vehiculos` y sus formularios con cada rol | Admin: todo. Asesor: clientes y vehículos (403 en usuarios). Técnico y cliente: 403 | ✅ |
| P2-02 | El asesor hace un POST directo a `/usuarios` para crear un administrador | 403; no se crea nada | ✅ |
| P2-03 | Abrir `/usuarios/999/editar` | 404 | ✅ |

## Usuarios (RF-03)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P2-04 | Crear un técnico con correo en mayúsculas | Se guarda en minúsculas y puede iniciar sesión | ✅ |
| P2-05 | Correo duplicado, contraseña débil, confirmación distinta y rol "cliente" | Un error claro en cada campo | ✅ |
| P2-06 | El administrador intenta cambiarse su propio rol (manipulando el POST) | El rol no cambia | ✅ |
| P2-07 | El administrador intenta desactivarse a sí mismo | "No puedes desactivar tu propia cuenta." | ✅ |
| P2-08 | Desactivar al asesor | No puede iniciar sesión, y su sesión abierta se cierra en el siguiente clic | ✅ |
| P2-09 | Cambiar a administrador una cuenta de cliente (por POST) | El rol sigue siendo "cliente" | ✅ |
| P2-10 | Editar dejando la contraseña vacía | Se conserva el hash anterior | ✅ |

## Clientes

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P2-11 | Crear un cliente con acceso al portal (documento `1.032.456.789`, teléfono con espacios) | Se normaliza el documento y el teléfono, y se crean el usuario y el cliente en una transacción | ✅ |
| P2-12 | Documento duplicado, teléfono de 2 dígitos, acceso sin correo ni contraseña | Error en cada campo | ✅ |
| P2-13 | Pedir acceso con un correo que ya usa otra cuenta | Error, y no queda ningún registro a medias (RNF-07) | ✅ |
| P2-14 | Desactivar un cliente con una orden abierta | Bloqueado con mensaje | ✅ |
| P2-15 | Desactivar un cliente sin órdenes | Su portal y sus vehículos quedan inactivos | ✅ |
| P2-16 | Cambiar el correo de un cliente con acceso | Se actualiza también el correo de su usuario | ✅ |
| P2-17 | Buscar "andinos" y ordenar por número de vehículos | Filtra y ordena correctamente | ✅ |
| P2-18 | Revisar la auditoría tras crear accesos | No aparecen contraseñas ni hashes | ✅ |
| P2-19 | Nombre `<script>alert(1)</script>` | Se muestra como texto | ✅ |

## Vehículos

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P2-20 | Placa `abc-12d` (moto) con un VIN en minúsculas | Se guarda como `ABC12D` con el VIN en mayúsculas | ✅ |
| P2-21 | Placa duplicada, placa `12ABC`, año 1900, VIN con "O", cliente inexistente | Error en cada campo | ✅ |
| P2-22 | Cambiar el dueño de un vehículo con una orden abierta | Bloqueado | ✅ |
| P2-23 | Desactivar un vehículo con una orden abierta | Bloqueado | ✅ |
| P2-24 | Asociar un vehículo a un cliente inactivo | "Selecciona un cliente activo." | ✅ |
| P2-25 | `?sort=placa;DROP…&dir=asc,1&estado=x'&pagina=-5` | 200, con valores por defecto; la tabla sigue intacta | ✅ |
| P2-26 | POST sin token CSRF | 419 | ✅ |
| P2-27 | Desde la ficha del cliente, "Agregar vehículo" | El propietario viene preseleccionado | ✅ |
| P2-28 | Listados a 360, 768 y 1366 px | Sin scroll horizontal; en móvil, tarjetas | ✅ |

# Pruebas · Sprint 1 (núcleo y seguridad)

> **Nota:** los códigos de orden de este archivo corresponden a los datos de prueba de esa fecha. Desde el Sprint 4 la base incluye 36 órdenes históricas y las órdenes en curso son `OT-2026-0037` a `OT-2026-0040`. Para repetir estas pruebas sobre los datos actuales, usa la suite automática `tests/run_tests.php`.

Ejecutadas el 04/11/2026 en entorno local (PHP 8 + MariaDB, datos de `seed.sql`).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas como evidencia (RNF-10).

| ID | Requisito | Pasos | Resultado esperado | Resultado |
|---|---|---|---|---|
| P-01 | RF-01 | Entrar con `admin@torque.test` / `Taller2026*` | Redirige a `/dashboard` y muestra el panel de administración | ✅ |
| P-02 | RF-07 | Entrar con cada rol (admin, asesor, técnico, cliente) | Cada uno ve un tablero distinto | ✅ |
| P-03 | RF-01 | Contraseña incorrecta | Mensaje genérico "Correo o contraseña incorrectos." | ✅ |
| P-04 | RF-03 | Entrar con `inactivo@torque.test` y la contraseña correcta | Mismo mensaje genérico; no entra | ✅ |
| P-05 | RS-02 | Correo `' OR '1'='1` y contraseña `' OR '1'='1` | No entra; la consulta no se altera | ✅ |
| P-06 | RS-02 | Correo `admin@torque.test' -- ` | No entra | ✅ |
| P-07 | RS-02 | `/auditoria?q=' OR 1=1 -- &orden=desc;DROP` | 200; búsqueda literal; el orden inválido se ignora | ✅ |
| P-08 | RS-06 | Buscar `<script>alert(1)</script>` en auditoría | Se muestra como texto escapado y no se ejecuta | ✅ |
| P-09 | RS-04 | Asesor, técnico y cliente abren `/auditoria` por URL | 403 "Acceso denegado", registrado en la auditoría | ✅ |
| P-10 | RS-04 | Cliente `cliente@torque.test` en su tablero | Solo ve sus placas (HTS219, KLM482); no ve BQX731 | ✅ |
| P-11 | RS-05 | POST a `/login` sin el campo `_csrf` | 419 "Formulario expirado" | ✅ |
| P-12 | RS-07 | Seis intentos fallidos seguidos sobre el mismo correo | Del 6.º en adelante: "Demasiados intentos… espera 1 minuto(s)", incluso con la contraseña correcta | ✅ |
| P-13 | RS-03 | Cerrar sesión y reutilizar la cookie anterior en `/dashboard` | Redirige a `/login` | ✅ |
| P-14 | RS-03 | Revisar la cabecera `Set-Cookie` | `HttpOnly; SameSite=Lax` (más `Secure` bajo HTTPS) | ✅ |
| P-15 | RS-03 | Dejar la sesión inactiva más de `SESSION_LIFETIME` minutos | Al navegar: "Tu sesión expiró por inactividad" | Pendiente (manual) |
| P-16 | RS-01 | Tras el primer login, revisar `usuarios.password_hash` en phpMyAdmin | Cambia de `$2y$` a `$argon2id$` (si el PHP lo soporta) | ✅ |
| P-17 | RF-12 | Abrir `/no-existe` | Página 404 propia | ✅ |
| P-18 | RF-12 | GET a `/logout` | 405 (cerrar sesión solo funciona por POST con CSRF) | ✅ |
| P-19 | RF-12 | Sin sesión, abrir `/dashboard` | Redirige a `/login` con el aviso "Inicia sesión para continuar." | ✅ |
| P-20 | RNF-03 | Ver el tablero del asesor a 360, 768 y 1366 px | Sin scroll horizontal; en móvil la tabla se convierte en tarjetas | ✅ (360 y 1366) |
| P-21 | RNF-09 | Revisar la consola del navegador en login y tableros | Sin errores | ✅ |

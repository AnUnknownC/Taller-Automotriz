# Guía de sustentación · Torque

> Para preparar la sección 08 del documento ("Sustentación" y "Prueba de aceptación en vivo").
> **Los dos integrantes deben dominar todo el sistema**: el documento no acepta la división "uno frontend y otro backend".

---

## 1. Antes de empezar (día D)

- [ ] XAMPP encendido (Apache + MySQL) y base reinstalada: `php tests\run_tests.php --reset` (además deja la evidencia de 56/56).
- [ ] Luego, **reimportar `instalar_completo.sql`**, porque las pruebas modifican los datos de demostración.
- [ ] `.env` con `APP_DEBUG=false`.
- [ ] Pestañas abiertas: la app, phpMyAdmin (tabla `ordenes_trabajo`) y el editor con el proyecto.
- [ ] Un navegador en modo incógnito por rol, o perfiles distintos, para cambiar de rol sin cerrar sesión.
- [ ] El celular conectado a la misma red para mostrar la versión móvil (`http://IP-del-PC/taller/`).

---

## 2. Guion de la demostración (≈12 minutos)

| Min | Quién | Qué mostrar | Qué decir (idea clave) |
|---|---|---|---|
| 0–1 | — | Login | "Torque gestiona la orden de trabajo del taller de punta a punta, con 4 roles y permisos verificados en el servidor." |
| 1–3 | **Asesor** | Nueva orden → placa `GHZ904` → ingreso → asignar a Camilo | R5: una sola orden abierta por vehículo. R11: el km no retrocede. Código consecutivo con `FOR UPDATE`. |
| 3–4 | **Técnico** (celular) | Mis órdenes → diagnóstico | Solo ve sus órdenes (RS-04: propiedad). En móvil, el formulario aparece primero. |
| 4–6 | **Asesor** | Armar cotización: repuesto + mano de obra | Totales en vivo con JS, pero **el servidor recalcula**: el precio sale del catálogo (R9). |
| 6–7 | **Cliente** (`cliente2`, celular) | Tablero → Revisar y responder → Aprobar OT-0037 | Solo el dueño puede responder; queda el canal "portal". |
| 7–8 | **Técnico** | Iniciar reparación → Marcar lista | R2: el stock se descuenta en una transacción. Mostrar el kardex del repuesto. |
| 8–9 | **Asesor** | Liquidar y entregar | R4: la liquidación y la entrega son un solo paso transaccional. **Simulado.** |
| 9–10 | phpMyAdmin | `ordenes_trabajo`, `orden_historial_estados`, `movimientos_inventario` | Los cambios están en la BD; historial con autor y fecha. |
| 10–11 | **Admin** | Reportes → 90 días → Exportar CSV; Auditoría | Cifras calculadas en SQL; CSV protegido contra fórmulas; todo queda auditado. |
| 11–12 | Seguridad | `/usuarios` como asesor (403); `<script>` en un nombre; 6 claves malas | RS-04, RS-06, RS-07 en vivo. |

---

## 3. Prueba de aceptación en vivo (lo que puede pedir el docente)

| El docente pide… | Cómo hacerlo | Qué demuestra |
|---|---|---|
| "Instale sobre una base vacía" | phpMyAdmin → Importar `instalar_completo.sql` → abrir la app | RNF-08: instalación reproducible |
| "Entre con un rol restringido" | `tecnico@torque.test`: solo ve sus órdenes e inventario sin costos | RF-02, RF-07 |
| "Registre un nuevo proceso" | Asesor → Nueva orden (sección 2) | RF-04 |
| "Verifique el cambio en la BD" | phpMyAdmin → `ordenes_trabajo` (último registro) y `orden_historial_estados` | Persistencia real |
| "Intente entrar a una ruta no autorizada" | Como técnico, escribir `/taller/usuarios` → 403; `/taller/ordenes/N` de otro técnico → 403 | RS-04 (y queda en la auditoría) |
| "Introduzca una entrada maliciosa" | Cliente con nombre `<script>alert(1)</script>`, o buscar `' OR '1'='1` | RS-06, RS-02 |
| "Filtre los reportes" | Reportes → fechas, estado y técnico → Exportar CSV | RF-09 |
| "Cierre sesión" | Salir → botón atrás del navegador → la ruta redirige al login | RS-03 |
| "Pruebe en el móvil" | Celular en la misma red, o DevTools a 360 px | RNF-03 |

---

## 4. Los 9 elementos que cada integrante debe ubicar en el código

Ejemplo unificado: **"el técnico inicia la reparación"** (`POST /ordenes/{id}/iniciar`). Sigan la petición de punta a punta.

| # | Elemento | Archivo y línea | Explicación en una frase |
|---|---|---|---|
| 1 | **Ruta** | `config/routes.php:69` | Asocia `POST /ordenes/{id}/iniciar` con `OrdenController::iniciar` y exige `auth` + `role:tecnico`. |
| 2 | **Controlador** | `app/Controllers/OrdenController.php:233` (`iniciar`) | Carga la orden, verifica la propiedad, llama al servicio y responde con un mensaje y una redirección. No tiene SQL ni HTML. |
| 3 | **Modelo** | `app/Models/OrdenTrabajo.php:195` (`moverEstado`) | Cambia el estado con `WHERE id = :id AND estado = :desde` (bloqueo optimista). |
| 4 | **Vista** | `app/Views/ordenes/show.php:87` | Muestra el botón "Iniciar reparación" solo si la acción está en `$acciones`. |
| 5 | **Conexión PDO** | `app/Core/Database.php:26` (`new PDO`), línea 29 `EMULATE_PREPARES => false` | Una sola conexión, errores como excepciones y preparación real en el servidor MySQL. |
| 6 | **Sesión** | `app/Core/Session.php:14` (`start`), `:37` (inactividad); `app/Core/Auth.php:20` (`regenerate` al iniciar sesión) | Cookie HttpOnly + SameSite, modo estricto, expira por inactividad, ID nuevo al autenticarse. |
| 7 | **Autorización** | `app/Middleware/RoleMiddleware.php:24` (rol) + `OrdenController.php:290` (`autorizada`) + `app/Services/OrdenService.php:63` (`puedeVer`) | Dos capas: el rol por ruta y la propiedad del registro (¿la orden es suya?). |
| 8 | **Validación** | `app/Core/Validator.php:24` (`rule`) y reglas del dominio en `OrdenService.php:286` (`iniciarReparacion` exige técnico asignado + estado Aprobada + stock) | Formato en el controlador; reglas del negocio en el servicio. |
| 9 | **Consulta preparada** | `app/Core/Model.php:21` (`query`: `prepare` + `bindValue`); `OrdenService.php:303` (`SELECT … FOR UPDATE` con `?`) | Los datos del usuario nunca se concatenan al SQL. |

**Otros puntos que conviene conocer:**
- `app/Core/Router.php:39` (`dispatch`) y `:55` (CSRF en todo POST).
- `app/Core/Csrf.php:21` (`hash_equals`).
- `app/Core/helpers.php:13` (`e()`, el escape XSS).
- `app/Services/AuthService.php:71` (`password_verify`) y `:91` (bloqueo progresivo).
- `app/Services/OrdenService.php:31` (`TRANSICIONES`) y `:82` (`acciones`).
- `app/Core/Csv.php:33` (inyección de fórmulas).
- `app/Core/ErrorHandler.php:27` (páginas de error y log).

---

## 5. Preguntas probables y respuestas cortas

| Pregunta | Respuesta |
|---|---|
| ¿Por qué un front controller? | Toda petición entra por `public/index.php`: un solo punto para cargar la configuración, la sesión, el manejo de errores y el router. El resto del código queda fuera de `public/`. |
| ¿Qué pasa si alguien oculta el botón pero manda el POST a mano? | Se rechaza igual: la ruta exige el rol (`RoleMiddleware`) y el servicio vuelve a validar el estado y la propiedad. Ocultar botones es solo UX. |
| ¿Cómo evitan la inyección SQL en el `ORDER BY`, que no admite parámetros? | Con **lista blanca**: `?sort=` solo puede ser una clave de un arreglo del modelo (`ORDEN`); cualquier otro valor vuelve al predeterminado. |
| ¿Por qué `EMULATE_PREPARES = false`? | Para que MySQL reciba la consulta y los parámetros por separado (preparación real), no un texto armado por PHP. |
| ¿Hash o cifrado? ¿Por qué Argon2id? | Hash: es de un solo sentido. Argon2id resiste ataques con GPU; si el PHP no lo trae, se usa bcrypt (`PASSWORD_DEFAULT`). El seed usa bcrypt y se re-hashea al primer login. |
| ¿Cómo funciona el bloqueo por fuerza bruta? | Se cuentan los fallos por correo desde el último acceso exitoso; desde el 5.º se espera 1, 2, 4… minutos (máx. 15). Además, 30 fallos por IP en 15 minutos bloquean la IP. El mensaje es genérico. |
| ¿Qué es el bloqueo optimista? | El `UPDATE … WHERE estado = :desde`: si otro usuario ya cambió la orden, se actualizan 0 filas y se muestra "otro usuario la modificó". |
| ¿Y si dos técnicos usan el último repuesto a la vez? | `SELECT … FOR UPDATE` bloquea la fila del repuesto dentro de la transacción; el segundo espera, ve el stock nuevo y falla limpiamente. Probado 5/5 veces con concurrencia real. Además hay un `CHECK (stock >= 0)` en la BD. |
| ¿Por qué el stock no se edita en el formulario del repuesto? | Para que cada unidad tenga trazabilidad: solo cambia por entradas, salidas o ajustes, y la suma del kardex siempre coincide con el stock. |
| ¿Por qué el precio del repuesto no viene del formulario? | El navegador es manipulable: el servidor toma el precio del catálogo y lo **congela** en la cotización (R9). |
| ¿Qué guarda la auditoría y qué no? | Quién, qué acción, sobre qué registro, antes/después e IP. **Nunca** contraseñas, hashes ni tokens (`Auditoria::CAMPOS_SECRETOS`). |
| ¿Por qué el CSV usa `;` y antepone `'` a algunas celdas? | `;` es el separador de Excel en español. La `'` evita que una celda que empieza por `=`, `+`, `-` o `@` se ejecute como fórmula (inyección CSV). |
| ¿Cómo se hace una transacción? | `beginTransaction` → operaciones → `commit`; ante cualquier excepción, `rollBack`. Ver `Model::transaction` y `OrdenService::enTransaccion`. |
| ¿Dónde está la regla "no reparar sin aprobación"? | En la máquina de estados: desde `cotizada` solo se puede ir a `aprobada` o `rechazada`, y `iniciar` exige `aprobada` (`OrdenService::TRANSICIONES` y `exigirTecnico`). |
| ¿Por qué el registro no vincula un documento que ya existe? | Porque cualquiera que conozca la cédula de otra persona vería sus vehículos y podría aprobar sus cotizaciones (suplantación). Ese cliente pide su acceso en recepción, donde el asesor verifica su identidad. |
| ¿Por qué el mensaje de "datos ya existentes" no dice cuál? | Para no permitir averiguar quién es cliente del taller ni qué correos están registrados (enumeración). |
| ¿Cómo evitan que un bot cree cuentas en masa? | Con un campo trampa invisible (honeypot), un límite de 5 registros por IP por hora y el token CSRF. |
| ¿Qué hicieron ustedes y qué hizo la IA? | Respondan con su bitácora (`docs/BITACORA_IA.md` §4): sus decisiones de alcance y reglas, y **sus propias modificaciones**. |

---

## 6. Modificaciones en vivo para practicar

El docente pedirá "una modificación pequeña de lógica o presentación". **Practiquen estas sin IA**, cada uno por separado:

| # | Pedido probable | Dónde se cambia | Pista |
|---|---|---|---|
| 1 | "Bloquee tras 3 intentos en vez de 5" | `AuthService.php`, constante `FALLOS_PERMITIDOS` | Una línea; probar con 3 claves malas. |
| 2 | "Que la sesión expire a los 5 minutos" | `.env` → `SESSION_LIFETIME=5` | Sin tocar código: explica por qué existe `.env`. |
| 3 | "Agregue el campo *kilometraje de salida* a la orden" | `schema.sql` (`ALTER TABLE`), `OrdenService::liquidarYEntregar` (columna en `$extra`), vista `liquidar.php` (input), validación en el controlador | Recorre las 4 capas MVC. |
| 4 | "No permita vehículos anteriores a 1980" | `VehiculoController::validar` → `between:1980,…`; `form.php` → `min="1980"` | Validación en cliente **y** en servidor. |
| 5 | "Muestre el teléfono del cliente en el listado de órdenes" | `OrdenTrabajo::listar` (agregar `c.telefono` al SELECT) + `ordenes/index.php` (columna con `e()`) | SQL → vista, siempre escapando. |
| 6 | "Permita que el asesor haga ajustes de inventario" | `config/routes.php`: cambiar `$admin` por `$recepcion` en las rutas de ajuste + mostrar el botón en `repuestos/show.php` | El permiso real está en la ruta, no en el botón. |
| 7 | "Cambie el IVA al 16 %" | `.env` → `TALLER_IVA=0.16` | Las cotizaciones ya enviadas conservan su valor (ítems congelados). |
| 8 | "Agregue un indicador: órdenes vencidas" | `OrdenTrabajo` (método con `fecha_estimada < CURDATE()` y `sqlAbiertas()`), `DashboardController`, `dashboard/asesor.php` | Consulta preparada nueva + vista. |
| 9 | "Cambie el color del estado *Cotizada*" | `app.css` → `.estado-cotizada` | Verificar el contraste ≥ 4,5:1. |

---

## 7. Cómo estudiar entre los dos

1. **Día 1:** lean juntos `ANALISIS_Y_DISENO.md` §8 y la sección 4 de esta guía. Cada uno traza en voz alta la petición de "iniciar reparación".
2. **Día 2:** cada uno toma la mitad de las preguntas de la sección 5 y se las **explica al otro** sin mirar.
3. **Día 3:** cada uno hace 3 modificaciones de la sección 6 sin IA; el otro las revisa. Anótenlas en la bitácora (§4).
4. **Día 4:** ensayo completo cronometrado (sección 2) y pruebas cruzadas: uno pregunta, el otro responde ubicando el código.

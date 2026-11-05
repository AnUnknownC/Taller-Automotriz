# Plan de pruebas · Torque

> Entregable 6 (RNF-10): plan con más de 20 casos, que incluye un flujo completo por rol, pruebas de acceso, de validación y de seguridad, con sus resultados y evidencias.

## 1. Estrategia

| Nivel | Herramienta | Qué cubre | Dónde |
|---|---|---|---|
| **Aceptación automática** | `tests/run_tests.php` (PHP + curl) | 56 casos por HTTP: seguridad, permisos, flujo completo con los 4 roles, inventario, reportes, autorregistro | Se ejecuta en XAMPP: ver el manual, sección A.4 |
| **Funcionales por sprint** | Manual + navegador automatizado | 155 casos documentados con su resultado | `pruebas-sprint1.md` … `pruebas-sprint4.md` |
| **Accesibilidad** | axe-core 4.10 (WCAG 2.0/2.1/2.2 A y AA) + teclado | 35 pantallas × 2 anchos (escritorio y móvil) | Sección 5 |
| **Responsive** | Navegador a 360, 390, 768 y 1366 px | Sin scroll horizontal; tablas convertidas en tarjetas | Sección 6 y `docs/img/` |
| **Rendimiento** | `curl` + `EXPLAIN` con 20.000 órdenes | Tiempos e índices | `rendimiento.md` |

**Entorno:** PHP 8.0–8.3, MariaDB 10.4–10.11, Chrome. **Datos:** `database/instalar_completo.sql`, con datos ficticios.

## 2. Resultado de la suite automática

Última ejecución: `tests/resultados/` → **56 de 56 ✅** (≈4 s). Se ejecutó dos veces seguidas con `--reset`, con el mismo resultado (reproducible).

| ID | Grupo | Caso | Resultado esperado |
|---|---|---|---|
| A-01 | Autenticación | Página de login disponible | 200 |
| A-02 | Autenticación | Ruta protegida sin sesión | Redirige a `/login` |
| A-03 | Autenticación | Login de los 4 roles | Cada uno llega a su tablero |
| A-04 | Autenticación | Contraseña incorrecta | Mensaje genérico, sin revelar si el correo existe |
| A-05 | Autenticación | Usuario inactivo | Mismo mensaje genérico; no entra |
| A-06 | RS-01 | Ninguna contraseña en texto plano | Solo hashes `$2y$` o `$argon2id$` |
| A-07 | RS-01 | Rehash a Argon2id al iniciar sesión | Hash actualizado |
| A-08 | RS-07 | 6 intentos fallidos | "Demasiados intentos… espera" |
| A-09 | RS-03 | Cookie de sesión | `HttpOnly; SameSite=Lax` |
| A-10 | RS-03 | Logout y luego acceso a ruta protegida | Redirige a `/login` |
| P-01 | RS-04 | 7 rutas × 4 roles (28 combinaciones) | 200 o 403 según la matriz |
| P-02 | RS-04 | El asesor crea un administrador por POST directo | 403 |
| P-03 | RS-04 | Un cliente abre la orden de otro cliente | 403 (el dueño: 200) |
| P-04 | RS-04 | Un técnico abre la orden de otro técnico | 403 |
| P-05 | RF-11 | Accesos denegados auditados | Registro `acceso_denegado` |
| P-06 | RF-03 | El admin se quita su propio rol | El rol no cambia |
| S-01 | RS-02 | Login con `' OR '1'='1` | No entra |
| S-02 | RS-02 | Inyección en `q`, `sort`, `dir`, `estado` | 200; la tabla sigue intacta |
| S-03 | RS-05 | POST sin token CSRF | 419 |
| S-04 | RS-06 | Cliente con nombre `<script>alert(1)</script>` | Se muestra escapado |
| S-05 | R9 | Precio de repuesto manipulado (envía $1) | Se guarda el precio del catálogo |
| S-06 | RF-12 | Ruta inexistente / método incorrecto | 404 / 405 propios |
| V-01 | RF-10 | Documento duplicado y teléfono inválido | Error en cada campo |
| V-02 | RF-10 | Placa `12ABC` y año 1900 | Error en cada campo |
| V-03 | Regla | Desactivar un cliente con órdenes abiertas | Bloqueado |
| V-04 | R5 | Segunda orden abierta para el mismo vehículo | Bloqueado |
| V-05 | R11 | Kilometraje menor al último ingreso | Bloqueado |
| V-06 | Regla | Precio de venta menor al costo | Error |
| F-01 | **Flujo · asesor** | Asignar técnico | Recibida → Diagnóstico |
| F-02 | **Flujo · técnico** | Registrar diagnóstico | Guardado |
| F-03 | **Flujo · asesor** | Enviar cotización | → Cotizada |
| F-04 | **Flujo · asesor** | Aprobación telefónica | → Aprobada, canal `telefono` |
| F-05 | R1 | Iniciar una orden rechazada | Bloqueado |
| F-06 | **Flujo · técnico** · R2 | Iniciar reparación | Stock −6 y salida en el kardex |
| F-07 | **Flujo · técnico** | Marcar como lista | → Lista |
| F-08 | **Flujo · asesor** · R4 | Liquidar sin confirmar / confirmando | No se entrega / entregada por $410.550 |
| F-09 | Regla | Liquidar una orden rechazada | Solo el diagnóstico: $71.400 |
| F-10 | **Flujo · cliente** | Aprobar desde el portal (y un cliente ajeno) | Canal `portal` (ajeno: 403) |
| F-11 | RF-11 | Historial de la orden | 7 transiciones con su autor |
| F-12 | RF-04 | Liquidar una orden solo aprobada | El estado no cambia |
| I-01 | Inventario | Entrada de mercancía | Stock +10 y kardex |
| I-02 | R2 | Ajuste que deja el stock negativo | Bloqueado |
| I-03 | R12 | Suma de movimientos = stock en todos los repuestos | 0 diferencias |
| R-01 | RF-09 | El indicador de órdenes coincide con SQL | Iguales |
| R-02 | RF-09 | Formato del CSV | `text/csv`, BOM, `;` |
| R-03 | Seguridad | Inyección de fórmulas en el CSV | Celda con `'` delante |
| R-04 | RF-11 | Exportaciones auditadas | Registros `exportar_csv` |
| RG-01 | Registro | El login enlaza al registro; con sesión, `/registro` redirige | 200 / 303 |
| RG-02 | Registro · RS-04 | Registro válido enviando `rol=administrador` | Entra como **cliente**; cliente y cuenta vinculados |
| RG-03 | RS-01 · RF-11 | Contraseña del registro | Hash; auditado sin la contraseña |
| RG-04 | Seguridad | Documento ya registrado por el taller (sin portal) | Mensaje genérico; **no** se vincula (evita suplantación) |
| RG-05 | Seguridad | Correo ya usado | El mismo mensaje genérico (sin enumeración) |
| RG-06 | RF-10 | Sin autorización de datos, clave débil, celular inválido | Error en cada campo |
| RG-07 | Anti-abuso | Campo trampa (honeypot) lleno | No se crea la cuenta |
| RG-08 | RS-05 | Registro sin token CSRF | 419 |
| RG-09 | Anti-abuso | 6.º registro desde la misma IP en una hora | Bloqueado |

**Flujo completo por rol:**
- **Admin:** A-03, P-01, P-06, I-02 y R-01.
- **Asesor:** F-01, F-03, F-04 y F-08.
- **Técnico:** F-02, F-06 y F-07.
- **Cliente:** F-10.

## 3. Ataques de prueba exigidos por la sección 05

| Control | Prueba requerida por el documento | Casos | Evidencia |
|---|---|---|---|
| RS-01 | No hay contraseñas en texto plano en MySQL, logs ni código | A-06, A-07 | phpMyAdmin → `usuarios.password_hash` |
| RS-02 | `' OR '1'='1` y entradas inesperadas no alteran la consulta | S-01, S-02, P4-07 | — |
| RS-03 | Salir invalida la sesión; la ruta protegida no abre después de salir o expirar | A-10, P-15 (expiración, manual) | — |
| RS-04 | Recurso ajeno por URL o POST → denegación | P-01…P-04 | `docs/img/14-error-403.png` |
| RS-05 | POST sin token válido es rechazado | S-03 | — |
| RS-06 | `<script>` se muestra como texto | S-04 | `docs/img/21-seguridad-xss-escapado.png` |
| RS-07 | Restricción tras intentos fallidos reiterados | A-08 | `docs/img/20-seguridad-fuerza-bruta.png` |
| RS-08 | Instalación con `.env.example`; sin contraseñas reales ni trazas sensibles | Revisión de `.env.example`, `APP_DEBUG=false`, `storage/` y `app/` bloqueados por `.htaccess` | `docs/img/22-error-404.png` |

## 4. Pruebas funcionales por sprint

| Archivo | Alcance | Casos | Resultado |
|---|---|---|---|
| `pruebas-sprint1.md` | Núcleo, login, seguridad, tableros | 21 | 20 ✅ · 1 manual (expiración por inactividad) |
| `pruebas-sprint2.md` | Usuarios, clientes, vehículos | 28 | 28 ✅ |
| `pruebas-sprint2b.md` | Proveedores, repuestos, kardex | 20 | 20 ✅ |
| `pruebas-sprint3a.md` | Recepción, asignación, diagnóstico, cotización | 26 | 26 ✅ |
| `pruebas-sprint3b.md` | Aprobación, reparación, liquidación, concurrencia de stock | 22 | 22 ✅ |
| `pruebas-sprint4.md` | Reportes, indicadores, CSV, histórico | 14 | 14 ✅ |

**Total:** 131 casos funcionales + 56 automáticos.

## 5. Accesibilidad (RNF-04)

**Auditoría automática con axe-core** (reglas WCAG 2.0/2.1/2.2 A y AA + buenas prácticas) sobre 35 pantallas de los 4 roles, a 1366 y a 375 px.

| Ronda | Violaciones | Corrección |
|---|---|---|
| 1 | 4 tipos: contraste de `.text-secondary` (4,2:1), marca sin nombre en móvil, `aria-label` sobre `<i>`, paginación menor a 24 px | Colores de texto a #56616e (5,7:1), `aria-label` en la marca, texto oculto para lectores, objetivos de 32 px |
| 2 | Contraste de los botones de contorno de Bootstrap | Paleta propia de botones (8,0:1) |
| 3 | Enlace dentro de una alerta informativa | Los enlaces en alertas heredan el color y van en negrita |
| **Final** | **0 violaciones** | — |

**Navegación con teclado** (verificada con un navegador automatizado):
- El foco inicial queda en el correo.
- Con Tab se recorren todos los controles, con un contorno visible de 3 px.
- El botón de mostrar contraseña se activa con Espacio y actualiza `aria-pressed`.
- "Saltar al contenido" lleva el foco al `<main>`.
- En móvil, el menú se abre con Enter y se cierra con Escape.

**Otras medidas:**
- Etiquetas `<label for>` en todos los campos.
- Errores asociados con `aria-describedby` y `aria-invalid`.
- `aria-current` en el menú, la paginación y el progreso de la orden.
- Los estados de la orden siempre llevan texto además del color.
- Se respeta `prefers-reduced-motion`.

## 6. Responsive y compatibilidad (RNF-03, RNF-09)

- Sin scroll horizontal a 360, 390, 768 y 1366 px en todas las pantallas (comprobado con `scrollWidth`).
- En menos de 768 px las tablas se convierten en tarjetas con etiqueta.
- La tabla de cotización oculta "Valor unit." en celular y lo muestra bajo el concepto.
- Evidencias: `docs/img/02`, `15`–`18` (celular), `19` (tablet), el resto (escritorio).
- Sin errores en la consola de Chrome en ninguna de las pruebas con navegador.

**Pendiente del equipo:** repetir el recorrido en **Firefox o Edge** (RNF-09 pide un segundo navegador) y anotar el resultado aquí.

## 7. Defectos encontrados y corregidos durante las pruebas

| # | Defecto | Cómo se detectó | Corrección |
|---|---|---|---|
| 1 | Tildes dañadas al importar el SQL | Prueba de login (S1) | `SET NAMES utf8mb4` en los SQL |
| 2 | Menú lateral transparente en escritorio | Captura de pantalla (S1) | Fondo explícito en `.offcanvas-lg` |
| 3 | La contraseña del portal iba a quedar en la auditoría | Revisión de la auditoría (S2) | Lista de campos secretos ampliada |
| 4 | El correo seguía obligatorio al desmarcar "crear acceso" | Prueba con navegador (S2) | El estado original se guarda una sola vez |
| 5 | El total de la cotización leía la `<option>` en vez del `<input>` | Prueba con navegador (S3A) | Atributo `data-precio-catalogo` separado |
| 6 | La tabla de cotización se cortaba en el celular | Captura a 390 px (S3B) | Columna oculta en `sm` |
| 7 | `min="1" step="100"` rechazaba precios como $66.000 | Prueba con navegador (S2B) | `step="1"` |
| 8 | Entregas del histórico en la madrugada | Revisión del reporte (S4) | Generación en horario laboral |
| 9 | Promedio por técnico con criterio distinto al general | Comparación de cifras (S4) | Mismo criterio (solo reparadas) |
| 10 | Listado de abiertas sin índice (full scan) | `EXPLAIN` con 20.000 órdenes (S5) | `IN (...)` + índices compuestos |
| 11 | 4 tipos de violaciones de accesibilidad | axe-core (S5) | Ver la sección 5 |

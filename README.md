# Torque · Gestión de Taller Automotriz

Proyecto final — Desarrollo Web y Móvil (Universidad EAN). Caso 08: Taller automotriz.
Stack: PHP 8 orientado a objetos con MVC propio, MySQL/MariaDB, Bootstrap 5 y JavaScript, sobre XAMPP.

**Estado:**
- Sprint 1 completo: núcleo, seguridad, login y tablero por rol.
- Sprint 2 completo:
  - 2A: CRUD de usuarios, clientes y vehículos.
  - 2B: proveedores, repuestos, entradas de mercancía, ajustes y kardex.
- Sprint 3 completo: orden de trabajo de extremo a extremo.
  - 3A: recepción, asignación, diagnóstico y cotización.
  - 3B: aprobación, reparación con descuento de stock, liquidación simulada y entrega.
- Sprint 4 completo: reportes de órdenes, ingresos simulados y repuestos, con filtros, indicadores y exportación CSV.
- Sprint 5 completo: 56 pruebas automáticas, accesibilidad WCAG 2.2 AA (0 violaciones), optimización con índices y documentación de entrega.

## Documentación de entrega

| Entregable del documento | Archivo |
|---|---|
| 1. Código fuente MVC + `.env.example` | Este repositorio |
| 2. Base de datos | `database/instalar_completo.sql` (o `schema.sql` + `seed.sql`) |
| 3. Análisis (empresa, problema, objetivos, actores, navegación, historias, permisos) | `docs/ANALISIS_Y_DISENO.md` + `docs/PROYECTO.md` §7 |
| 4. Diseño técnico (ER, MVC, rutas, flujos) | `docs/ANALISIS_Y_DISENO.md` + `docs/PROYECTO.md` §4–5 |
| 5. Diseño de experiencia (guía visual, responsive) | `docs/GUIA_DISENO.md` + `docs/img/` |
| 6. Seguridad y pruebas (matriz + 20 o más pruebas) | `docs/MATRIZ_REQUISITOS.md` + `tests/PLAN_DE_PRUEBAS.md` |
| 7. Uso de IA | `docs/BITACORA_IA.md` (**completar §4 antes de entregar**) |
| 8. Manual | `docs/MANUAL.md` |
| 9. Sustentación | `docs/GUIA_SUSTENTACION.md` |

## Pruebas automáticas

```bat
C:\xampp\php\php.exe tests\run_tests.php --reset
```

- Reinstala la base y ejecuta 56 pruebas de seguridad, permisos, el flujo completo con los 4 roles, inventario, reportes y autorregistro.
- La evidencia queda en `tests/resultados/`.
- **Borra los datos actuales.**

## Instalación en XAMPP

1. **Copiar el proyecto**
   - Copia la carpeta `taller` dentro de `C:\xampp\htdocs\`.
   - Debe quedar así: `C:\xampp\htdocs\taller\public\index.php`.
2. **Encender servicios.** En el XAMPP Control Panel, inicia **Apache** y **MySQL**.
3. **Crear la base de datos.** Abre <http://localhost/phpmyadmin>, ve a la pestaña **Importar** e importa `database/instalar_completo.sql`.
   - Ese archivo crea la BD `taller_automotriz` desde cero y carga los datos ficticios.
   - Equivale a importar `schema.sql` y después `seed.sql`.
   - Si la BD ya existía, se borra y se vuelve a crear.
4. **Configurar el entorno**
   - Copia `.env.example` como `.env`, en la misma carpeta.
   - Con XAMPP por defecto (usuario `root` sin contraseña) no hay que cambiar nada.
5. **Abrir la aplicación:** <http://localhost/taller/>

> Si cambias el nombre de la carpeta, la app lo detecta sola: `http://localhost/<carpeta>/`.

## Cuentas de demostración

Todas usan la contraseña `Taller2026*`. Son credenciales solo para el entorno de evaluación.

| Rol | Correo | Qué ve |
|---|---|---|
| Administrador | admin@torque.test | Indicadores globales, órdenes por estado, auditoría |
| Asesor | asesor@torque.test | Órdenes recientes, pendientes, stock mínimo |
| Técnico | tecnico@torque.test | Solo sus órdenes asignadas |
| Técnico | tecnico2@torque.test | Sin órdenes: muestra el estado vacío |
| Cliente | cliente@torque.test | Solo sus vehículos y órdenes |
| Cliente | cliente2@torque.test | Tiene una cotización pendiente |
| Inactivo | inactivo@torque.test | No puede entrar (prueba RF-03) |

Un cliente también puede **crear su propia cuenta** con el botón *Crear cuenta de cliente* del login (`/registro`).

## Datos de demostración

- **36 órdenes ya entregadas** en los últimos ~3 meses (`OT-2026-0001` a `OT-2026-0036`), para que los reportes tengan contenido.
- **4 órdenes en curso** para demostrar el flujo en vivo:

| Orden | Vehículo | Estado |
|---|---|---|
| `OT-2026-0037` | BQX731, de Óscar | Cotizada: el cliente puede aprobarla desde `cliente2@` |
| `OT-2026-0038` | KLM482, de María | En diagnóstico (Camilo) |
| `OT-2026-0039` | TRK905 | Recibida, sin técnico |
| `OT-2026-0040` | HTS219 | Recibida, sin técnico |

Las fechas son relativas al día de instalación (siempre "recientes") y en horario laboral. El kardex de cada repuesto cuadra con su stock.

## Estructura (MVC)

```
app/Core/         Router, Controller, Model (PDO), View, Session, Csrf, Auth, Validator, ErrorHandler
app/Middleware/   Auth, Guest, Role (autorización en servidor), Csrf
app/Controllers/  Reciben la petición y coordinan; no tienen SQL ni HTML
app/Models/       Consultas preparadas y reglas de datos
app/Services/     Casos de uso complejos (AuthService: login + bloqueo)
app/Views/        Presentación; toda salida pasa por e() = htmlspecialchars
config/           app.php (lee .env), routes.php (mapa de rutas), menu.php (menú por rol)
public/           index.php (front controller) y assets locales (Bootstrap, iconos, css, js)
storage/logs/     Log de errores técnicos (no accesible por web)
database/         schema.sql, seed.sql
```

**Recorrido de una petición:**

1. El navegador pide `GET /dashboard`.
2. `.htaccess` la envía a `public/index.php`.
3. `Router` busca la ruta en `config/routes.php` y ejecuta su middleware (`auth`).
4. `DashboardController::index()` llama a los modelos (PDO con consultas preparadas).
5. Se renderiza la vista `dashboard/<rol>.php`.

## Reglas de negocio implementadas (RF-13)

| Módulo | Regla |
|---|---|
| Órdenes | La máquina de estados de `OrdenService` rechaza cualquier transición no definida. |
| Órdenes | R5: un vehículo no puede tener dos órdenes abiertas a la vez. |
| Órdenes | R11: el kilometraje no puede ser menor al del último ingreso. |
| Órdenes | Solo el técnico asignado diagnostica, y solo mientras la orden está en diagnóstico. |
| Órdenes | No se cotiza sin diagnóstico. La cotización se corrige solo mientras el cliente no responde. |
| Órdenes | R9: el precio de los repuestos sale del catálogo y queda congelado (se ignora el precio que envía el navegador). |
| Órdenes | Si dos usuarios cambian el estado a la vez, gana el primero (bloqueo optimista). |
| Órdenes | R1: no se repara sin aprobación. El cliente responde en el portal, o el asesor registra la respuesta presencial o telefónica (queda el canal y quién la registró). El rechazo exige motivo. |
| Órdenes | R2: al iniciar la reparación se descuentan los repuestos y se registra la salida en el kardex, en una transacción con `FOR UPDATE`. Si falta stock no se descuenta nada (probado con concurrencia real). |
| Órdenes | R4: la liquidación simulada y la entrega ocurren en la misma transacción, así que no hay entrega sin liquidación. |
| Órdenes | Si se rechaza la cotización, se cobra solo el diagnóstico (`TARIFA_DIAGNOSTICO`) más IVA. |

**Reglas de inventario:**

| Módulo | Regla |
|---|---|
| Inventario | El stock **nunca se edita a mano**: solo cambia con entradas (proveedor), salidas (reparación) y ajustes (admin, con motivo). Todo queda en el kardex. |
| Inventario | Cada repuesto nace con stock 0. |
| Inventario | El precio de venta no puede ser menor al costo. |
| Inventario | Un ajuste no puede dejar el stock negativo. |
| Inventario | No se desactiva un repuesto cotizado en una orden que aún no inicia la reparación. |
| Inventario | El kardex verifica que la suma de los movimientos coincida con el stock. |

**Reglas de los maestros:**

| Módulo | Regla |
|---|---|
| Usuarios | Nadie cambia su propio rol ni se desactiva a sí mismo. |
| Usuarios | Siempre queda al menos un administrador activo. |
| Usuarios | Las cuentas de cliente solo se crean desde Clientes y no pueden cambiar de rol. |
| Clientes | El documento es único por tipo. |
| Clientes | Si se pide acceso al portal, el correo es obligatorio y único; el cliente y su cuenta se crean en una transacción. |
| Clientes | No se desactiva un cliente con órdenes abiertas. Al desactivarlo se bloquea su portal y se desactivan sus vehículos. |
| Vehículos | La placa sigue el formato colombiano (ABC123 / ABC12D) y es única. El VIN sigue la norma ISO 3779. |
| Vehículos | Solo se asocian a clientes activos. |
| Vehículos | Con órdenes abiertas no se desactivan ni cambian de dueño. |

## Seguridad implementada (sección 05)

| ID | Dónde está |
|---|---|
| RS-01 | `AuthService`: `password_verify`, rehash automático a Argon2id si el PHP lo soporta. El seed usa bcrypt. |
| RS-02 | `Core/Model::query()`: siempre `prepare` + `bindValue`, con `EMULATE_PREPARES=false`. El `ORDER BY` usa lista blanca. |
| RS-03 | `Core/Session`: cookies HttpOnly y SameSite=Lax (Secure con HTTPS), modo estricto, `session_regenerate_id` al entrar, expiración por inactividad. |
| RS-04 | `Middleware/RoleMiddleware`: deniega con 403 y lo registra en la auditoría. Los modelos filtran por propietario (técnico y cliente). |
| RS-05 | `Router`: todo POST valida el token CSRF; si falla, responde 419. |
| RS-06 | Función `e()` en todas las vistas, más `Validator` en el servidor. |
| RS-07 | Bloqueo progresivo: desde el 5.º fallo, espera de 1, 2, 4… minutos (máximo 15), y límite por IP. El mensaje es genérico. |
| RS-08 | `.env` fuera del repositorio, errores ocultos (`APP_DEBUG=false`), log en `storage/`, `.htaccess` que bloquea `app/`, `config/` y `storage/`. |

## Fallas frecuentes

1. **Todas las páginas dan 404 o "Not Found" de Apache.** `mod_rewrite` está apagado o `AllowOverride` no está en `All`.
   - En `C:\xampp\apache\conf\httpd.conf`, verifica que `LoadModule rewrite_module` no esté comentado.
   - Verifica que el bloque `<Directory "C:/xampp/htdocs">` tenga `AllowOverride All`.
   - Reinicia Apache.
2. **"Algo salió mal" (500) al iniciar sesión.** MySQL está apagado, la BD no se importó o los datos de `.env` no coinciden.
   - Pon `APP_DEBUG=true` en `.env` para ver el detalle.
   - Revisa `storage/logs/`.
   - Errores típicos: `SQLSTATE[HY000] [2002]` (MySQL apagado) o `[1049]` (falta importar `schema.sql`).
3. **Tildes dañadas (MarÃ­a).** El SQL se importó con otro juego de caracteres.
   - Vuelve a importar `schema.sql` y `seed.sql`; ambos declaran `SET NAMES utf8mb4`.
   - En phpMyAdmin, deja el juego de caracteres del archivo en `utf-8`.

Si al iniciar sesión te sale "Demasiados intentos", es el bloqueo RS-07 funcionando. Espera, o vacía la tabla `intentos_login` en el entorno de pruebas.

## Recursos externos

| Recurso | Versión | Licencia | Ubicación |
|---|---|---|---|
| Bootstrap | 5.3.3 | MIT | `public/assets/vendor/bootstrap/` |
| Bootstrap Icons | 1.11.3 | MIT | `public/assets/vendor/bootstrap-icons/` |

Se incluyen copias locales, así que no dependen de internet.

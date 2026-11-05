# Matriz de requisitos · Torque

> Entregable 6: estado de cada requisito del documento, dónde está implementado y con qué prueba se verifica.
> Estado: ✅ cumplido · 🟡 cumplido con tarea pendiente del equipo. Los IDs de prueba están en `tests/PLAN_DE_PRUEBAS.md`.

## Requisitos funcionales

| ID | Requisito | Estado | Implementación | Evidencia |
|---|---|---|---|---|
| RF-01 | Autenticación | ✅ | `AuthController`, `AuthService::intentar`, `password_verify`, mensaje genérico; autorregistro de clientes (`RegistroController`) | A-01…A-05, A-10, RG-01…RG-09 |
| RF-02 | Cuatro roles reales | ✅ | `RoleMiddleware` por ruta (`config/routes.php`) + propiedad en `OrdenService::puedeVer`; matriz en `PROYECTO.md` §7 | P-01 (28 combinaciones), P-03, P-04 |
| RF-03 | Gestión de usuarios | ✅ | `UsuarioController`: alta, edición, activación y desactivación; no se puede cambiar el rol propio; mínimo un admin | P-02, P-06, P2-04…10 |
| RF-04 | Proceso principal de extremo a extremo | ✅ | `OrdenService::TRANSICIONES` (máquina de estados), 4 roles, bloqueo optimista | F-01…F-12 |
| RF-05 | CRUD real con 4 o más entidades relacionadas | ✅ | Clientes, vehículos, órdenes, diagnósticos, cotizaciones, repuestos, proveedores, liquidaciones (8) | P2, P2B, P3 |
| RF-06 | Integridad y relaciones | ✅ | 15 tablas InnoDB, FK, `UNIQUE` (placa, documento, código, NIT, VIN), `CHECK` (stock ≥ 0, año, cantidades), eliminación lógica (`activo`) | `schema.sql`, V-01…V-06 |
| RF-07 | Interfaz por rol | ✅ | 4 tableros distintos, menú por rol (`config/menu.php`), accesos directos, estados vacíos | `docs/img/03`, `08`, `15`, `17` |
| RF-08 | Búsqueda, filtro, orden y paginación en 2 o más listados | ✅ | `ListParams` + `Model::paginate` en 8 listados (usuarios, clientes, vehículos, órdenes, repuestos, proveedores, auditoría, reporte) | S-02, P2-17, P3-24 |
| RF-09 | Tablero con 3 o más indicadores, reporte filtrable y CSV | ✅ | Indicadores por rol, `ReporteController` (3 reportes), `Csv::descargar` | R-01…R-04, P4-01…14 |
| RF-10 | Validación en cliente y en servidor | ✅ | HTML5 + `app.js` (cliente); `Validator` + reglas del dominio (servidor) | V-01, V-02, P3-09, `docs/img/13` |
| RF-11 | Historial y auditoría | ✅ | `auditoria` (antes/después, sin secretos), `orden_historial_estados`, kardex | P-05, F-11, R-04 |
| RF-12 | Navegación integral | ✅ | Pantallas 403/404/405/419/500, mensajes flash, migas de pan, confirmación (`data-confirm`) en acciones destructivas | S-06, `docs/img/14`, `22` |
| RF-13 | 3 o más reglas de negocio propias | ✅ | 12 reglas (R1…R12), ver `PROYECTO.md` §6 | V-03…V-05, F-05, F-06, F-08, S-05, I-02 |
| RF-14 | Datos de demostración | ✅ | `seed.sql`: 7 cuentas, 12 clientes, 16 vehículos, 40 órdenes (36 históricas + 4 en curso), kardex coherente | P4-13 |

## Seguridad

| ID | Control | Estado | Implementación | Evidencia |
|---|---|---|---|---|
| RS-01 | `password_hash` / Argon2id con respaldo | ✅ | `AuthService::hash` / `algoritmo()`, rehash al iniciar sesión | A-06, A-07 |
| RS-02 | PDO preparado | ✅ | `Model::query` (`prepare` + `bindValue`), `EMULATE_PREPARES=false`, listas blancas en `ORDER BY` | S-01, S-02 |
| RS-03 | Sesiones seguras | ✅ | `Session` (HttpOnly, SameSite, Secure con HTTPS, modo estricto, expiración por inactividad); `session_regenerate_id` en `Auth::login` | A-09, A-10 |
| RS-04 | Autorización en servidor y propiedad | ✅ | `RoleMiddleware` + `OrdenController::autorizada` + filtros por dueño en los modelos | P-01…P-04 |
| RS-05 | CSRF | ✅ | `Csrf` (`hash_equals`), validado en el `Router` para **todo** POST | S-03 |
| RS-06 | Escape de salida y validación | ✅ | `e()` en todas las vistas; `Validator` (tipo, longitud, formato) | S-04 |
| RS-07 | Fuerza bruta | ✅ | `AuthService::segundosDeBloqueo`: por correo (1, 2, 4… hasta 15 min) y por IP | A-08 |
| RS-08 | Configuración y archivos seguros | ✅ | `.env` fuera del repositorio, `APP_DEBUG=false`, log en `storage/`, `.htaccess` deniega `app/`, `config/`, `storage/`, `database/`, `tests/` | Revisión, `docs/img/22` |

## No funcionales

| ID | Dimensión | Estado | Cómo se cumple | Evidencia |
|---|---|---|---|---|
| RNF-01 | Usabilidad | ✅ | Siguiente paso destacado en cada orden, búsqueda por placa, estados vacíos, mensajes claros, carga en botones ("Guardando…") | Capturas `docs/img/` |
| RNF-02 | Diseño visual propio | ✅ | Identidad "Torque": grafito + ámbar de señalización + placa colombiana | `docs/GUIA_DISENO.md` |
| RNF-03 | Responsive | ✅ | Sin scroll horizontal a 360, 390, 768 y 1366 px; tablas convertidas en tarjetas | Plan §6, `docs/img/02`, `15`–`19` |
| RNF-04 | Accesibilidad WCAG 2.2 AA | ✅ | 0 violaciones axe-core en 35 pantallas × 2 anchos; teclado verificado | Plan §5 |
| RNF-05 | Calidad del código | ✅ | MVC con capa de servicios, `Validator` y `ListParams` reutilizables, parciales de vista, constantes en lugar de cadenas mágicas | `ANALISIS_Y_DISENO.md` §8 |
| RNF-06 | Rendimiento | ✅ | Paginación en BD, índices compuestos, medición con 20.000 órdenes | `tests/rendimiento.md` |
| RNF-07 | Confiabilidad | ✅ | Transacciones en las operaciones multitabla (cliente + cuenta, cotización, reparación + stock, liquidación + entrega); `FOR UPDATE`; bloqueo optimista | F-06, P3B-11 (concurrencia real) |
| RNF-08 | Mantenibilidad | ✅ | README, `instalar_completo.sql`, `.env.example`, configuración independiente del equipo | `docs/MANUAL.md` |
| RNF-09 | Compatibilidad | 🟡 | Chrome sin errores de consola; HTML, CSS y JS estándar | **Pendiente:** probar en Firefox o Edge y anotarlo |
| RNF-10 | Pruebas | ✅ | 56 automáticas reproducibles + 131 funcionales documentadas | `tests/PLAN_DE_PRUEBAS.md` |

## Habilidades técnicas demostrables (sección 07, mínimo 6)

| # | Habilidad | Dónde se evidencia |
|---|---|---|
| 1 ✱ | Modelado de dominio | Máquina de estados, 12 reglas, matriz de permisos, ER, historias de usuario (`ANALISIS_Y_DISENO.md`) |
| 2 ✱ | PHP MVC | Router, middleware, controladores, servicios, modelos, vistas y manejo de errores propios (`app/`) |
| 3 ✱ | Seguridad | RS-01…RS-08 + protección contra inyección en CSV + precio desde el catálogo |
| 4 ✱ | UI/UX y accesibilidad | Guía de diseño, responsive, 0 violaciones WCAG |
| 5 | Consultas SQL avanzadas | JOIN múltiples, `GROUP BY`/`AVG`/`TIMESTAMPDIFF`, subconsultas, `FOR UPDATE`, transacciones, índices (`Reporte.php`, `OrdenService.php`) |
| 6 | JavaScript útil | Editor de cotización con totales en vivo, validación con `setCustomValidity`, secciones condicionales (`cotizacion.js`, `app.js`) |
| 7 | Pruebas y depuración | `run_tests.php` (56 casos), axe-core, prueba de concurrencia real, 11 defectos corregidos |
| 9 | Visualización de datos | Indicadores y gráficas de barras de una serie con tabla de datos (`reportes/`) |
| 10 | Optimización y calidad | `EXPLAIN` + índices compuestos: listado 4,7 veces más rápido (`rendimiento.md`) |
| 8 | Git y documentación | Documentación completa. **Pendiente del equipo:** historial de commits significativo en el repositorio |

✱ Obligatorias.

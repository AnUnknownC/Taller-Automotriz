# Manual de instalación y uso · Torque

> Entregable 8. Instalación desde cero en XAMPP, cuentas de prueba, uso por rol y solución de fallas frecuentes.

---

## Parte A · Instalación desde cero

### A.1 Requisitos

| Componente | Versión probada | Notas |
|---|---|---|
| XAMPP | 8.0.30 (PHP 8.0, MariaDB 10.4) | Funciona también con PHP 8.1–8.3 |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `curl` (solo para las pruebas automáticas) | Vienen activas en XAMPP |
| Apache | `mod_rewrite` activo y `AllowOverride All` | Ver la falla 1 |
| Navegador | Chrome, Edge o Firefox actuales | — |

No se necesita internet: Bootstrap y los íconos están incluidos en `public/assets/vendor/`.

### A.2 Pasos

1. **Copiar el proyecto.** Descomprime `taller.zip` en `C:\xampp\htdocs\`. Debe quedar `C:\xampp\htdocs\taller\public\index.php`.
2. **Encender servicios.** En el *XAMPP Control Panel*, pulsa **Start** en **Apache** y en **MySQL**.
3. **Crear la base de datos:**
   1. Abre <http://localhost/phpmyadmin> y entra a la pestaña **Importar**, sin seleccionar ninguna base.
   2. Elige `taller\database\instalar_completo.sql`, deja el juego de caracteres en **utf-8** y pulsa **Importar**.
   3. Debe aparecer `taller_automotriz` con 15 tablas.
   > El archivo **borra y vuelve a crear** la base `taller_automotriz`. Equivale a importar `schema.sql` y después `seed.sql`.
4. **Configurar.** Copia `.env.example` como `.env`, en la misma carpeta. Con XAMPP por defecto (usuario `root` sin contraseña) no hay que cambiar nada.
5. **Abrir** <http://localhost/taller/> e inicia sesión con una cuenta de prueba.
6. **Verificar (recomendado):** ejecuta las pruebas automáticas (ver la sección A.4).

### A.3 Variables de `.env`

| Variable | Valor por defecto | Para qué sirve |
|---|---|---|
| `APP_DEBUG` | `false` | `true` muestra el detalle de los errores 500 (**solo en desarrollo**) |
| `SESSION_LIFETIME` | `20` | Minutos de inactividad antes de cerrar la sesión |
| `TALLER_IVA` | `0.19` | IVA de la liquidación simulada |
| `TARIFA_DIAGNOSTICO` | `60000` | Valor que se cobra si el cliente rechaza la cotización |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | `127.0.0.1`, `3306`, `taller_automotriz`, `root`, vacío | Conexión a MySQL |

`.env` **no se sube al repositorio** (está en `.gitignore`). Nunca pongas contraseñas reales en `.env.example`.

### A.4 Pruebas automáticas

Con Apache y MySQL encendidos, desde la carpeta del proyecto:

```bat
cd C:\xampp\htdocs\taller
C:\xampp\php\php.exe tests\run_tests.php --reset
```

- `--reset` reinstala la base desde cero (borra los datos actuales) y ejecuta **56 pruebas**: seguridad, permisos, el flujo completo con los 4 roles, inventario, reportes y autorregistro.
- El resultado queda en `tests\resultados\resultado_AAAAMMDD_HHMMSS.md`.
- Si la carpeta tiene otro nombre: `--url=http://localhost/otra-carpeta`.

---

## Parte B · Cuentas de prueba

Todas usan la contraseña **`Taller2026*`** (solo para el entorno de evaluación).

| Rol | Correo | Para demostrar |
|---|---|---|
| Administrador | `admin@torque.test` | Usuarios, auditoría, ajustes de inventario, todos los reportes |
| Asesor | `asesor@torque.test` | Recepción, asignación, cotización, liquidación, entradas |
| Técnico | `tecnico@torque.test` | Camilo: órdenes OT-0037 y OT-0038 |
| Técnico | `tecnico2@torque.test` | Julián: sin órdenes en curso (estado vacío) |
| Cliente | `cliente@torque.test` | María: KLM482 y HTS219, sin cotización pendiente |
| Cliente | `cliente2@torque.test` | Óscar: **OT-2026-0037 con cotización pendiente** para aprobar |
| Inactivo | `inactivo@torque.test` | No puede entrar (prueba de desactivación) |

**Datos incluidos:** 12 clientes, 16 vehículos, 7 repuestos, 3 proveedores, 36 órdenes históricas entregadas (para los reportes) y 4 órdenes en curso.

---

## Parte C · Uso por rol

### C.1 Asesor: de la recepción a la entrega

1. **Recibir un vehículo.** *Inicio → Nueva orden*, escribe la placa (p. ej. `JPN347`) y pulsa **Buscar**.
   - Si el vehículo no existe, el sistema ofrece registrarlo.
   - Si ya tiene una orden abierta, lo impide e indica cuál es.
   - Completa el motivo, el kilometraje (no puede ser menor al del último ingreso) y la fecha estimada. Pulsa **Registrar ingreso**.
2. **Asignar técnico.** En la ficha de la orden, en *Siguiente paso*, elige el técnico (se muestra cuántas órdenes tiene en curso). La orden pasa a *En diagnóstico*.
3. **Cotizar.** Cuando el técnico registra el diagnóstico, aparece **Armar cotización**.
   - Agrega repuestos (el precio sale del catálogo y no se edita) y mano de obra (horas × valor).
   - Los totales con IVA se calculan mientras escribes.
   - Al pulsar **Enviar cotización al cliente**, la orden pasa a *Cotizada*.
4. **Respuesta del cliente.** Si el cliente tiene portal, la responde él. Si llama o viene en persona, usa **Aprobar** o **Rechazar** en la ficha e indica el medio.
5. **Entregar.** Cuando el técnico marca la orden como lista (o si la cotización fue rechazada), aparece **Liquidar y entregar**. Elige el método de pago (simulado), confirma la entrega y registra.

**Otras tareas:** *Clientes* y *Vehículos* (crear, editar, desactivar), *Inventario → Entrada de mercancía* y *Reportes* de órdenes y repuestos.

### C.2 Técnico (ideal desde el celular)

1. En **Mis órdenes** aparecen solo las asignadas a ti, la más antigua primero.
2. Abre la orden y escribe el **diagnóstico** (hallazgos y trabajo recomendado). Puedes corregirlo hasta que el asesor envíe la cotización.
3. Cuando el cliente aprueba, pulsa **Iniciar reparación**: los repuestos se descuentan del inventario. Si falta alguno, el sistema dice cuál y no descuenta nada.
4. Al terminar, pulsa **Marcar como lista**, con una nota opcional para el asesor.
5. En *Inventario* puedes consultar el stock (sin costos).

### C.3 Cliente

0. **Crear la cuenta** (si el taller no se la creó):
   - En el login, pulsa **Crear cuenta de cliente** y completa documento, nombre, celular, correo y contraseña; acepta el tratamiento de datos.
   - Entrarás de inmediato a tu tablero. Cuando lleves tu vehículo, el asesor lo registrará a tu nombre y aparecerá ahí.
   - Si el taller **ya te tenía registrado** (por tu documento), por seguridad no se vincula automáticamente: pide en recepción que activen tu acceso.
1. En el tablero, si hay una cotización pendiente aparece **Revisar y responder**.
2. Revisa el diagnóstico y el detalle de la cotización. **Aprueba**, o **Rechaza** indicando el motivo. Si rechazas, solo se cobra el diagnóstico.
3. En cualquier momento ves tus vehículos, el progreso de cada orden y su historial.

### C.4 Administrador

- **Usuarios:** crear cuentas internas (admin, asesor, técnico), editar, cambiar contraseñas y desactivar. Las cuentas de cliente se crean desde *Clientes*.
- **Inventario:** catálogo de repuestos (código, costo, precio, stock mínimo), **ajustes** con motivo y kardex. El stock nunca se edita a mano.
- **Proveedores:** registro y desactivación.
- **Reportes:** órdenes (tiempos y tasa de aprobación), ingresos simulados, repuestos más usados, todos con **Exportar CSV**.
- **Auditoría:** quién hizo qué y cuándo, con filtros.

---

## Parte D · Fallas frecuentes y solución

| # | Síntoma | Causa | Solución |
|---|---|---|---|
| 1 | Todas las páginas dan **"Not Found"** de Apache (no la página 404 de Torque) | `mod_rewrite` apagado o `AllowOverride None` | En `C:\xampp\apache\conf\httpd.conf`: quita el `#` de `LoadModule rewrite_module modules/mod_rewrite.so`; en el bloque `<Directory "C:/xampp/htdocs">` pon `AllowOverride All`. Reinicia Apache. |
| 2 | **"Algo salió mal" (500)** al entrar | MySQL apagado, base no importada o datos de `.env` incorrectos | Pon `APP_DEBUG=true` en `.env` y recarga para ver el detalle (o revisa `storage\logs\`). `SQLSTATE[HY000] [2002]` = MySQL apagado; `[1049]` = falta importar la base; `[1045]` = usuario o contraseña de BD incorrectos. Vuelve a `APP_DEBUG=false` al terminar. |
| 3 | Tildes dañadas (**MarÃ­a**) | El SQL se importó con otro juego de caracteres | Vuelve a importar `instalar_completo.sql` con el juego de caracteres en **utf-8** (el archivo declara `SET NAMES utf8mb4`). |
| 4 | Al iniciar sesión dice **"Demasiados intentos fallidos"** | Es la protección contra fuerza bruta (RS-07) | Espera el tiempo indicado, o en el entorno de pruebas vacía la tabla `intentos_login` desde phpMyAdmin. |
| 5 | **"El formulario expiró" (419)** | La pestaña quedó abierta mucho tiempo o se cerró la sesión | Recarga la página e intenta de nuevo; es la protección CSRF (RS-05). |
| 6 | Falla `run_tests.php` con **"Falta la extensión curl"** | `curl` desactivada en el PHP de consola | En `C:\xampp\php\php.ini` quita el `;` de `extension=curl`. |
| 7 | En el registro: **"Se crearon demasiadas cuentas desde esta conexión"** | Límite anti-abuso: 5 cuentas por IP por hora | Espera una hora, o en pruebas borra de `auditoria` las filas con `accion = 'registro_cliente'`. |

---

## Parte E · Antes de una instalación real

1. Cambia **todas** las contraseñas de prueba, o crea usuarios nuevos y desactiva los de prueba.
2. Deja `APP_DEBUG=false` y usa un usuario de MySQL con contraseña y permisos solo sobre `taller_automotriz`.
3. Publica bajo **HTTPS**: la cookie de sesión se marca `Secure` automáticamente.
4. Haz respaldos periódicos de la base (phpMyAdmin → Exportar).

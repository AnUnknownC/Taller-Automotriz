<?php
declare(strict_types=1);

/**
 * Pruebas automáticas de aceptación (RNF-10): recorren la aplicación por HTTP como lo haría un usuario.
 *
 * Uso (Windows, desde la carpeta del proyecto, con Apache y MySQL encendidos):
 *     C:\xampp\php\php.exe tests\run_tests.php --reset
 *     C:\xampp\php\php.exe tests\run_tests.php --reset --url=http://localhost/taller
 *
 *   --reset  Reinstala la base de datos desde database/instalar_completo.sql antes de probar
 *            (necesario: las pruebas crean y modifican órdenes). ¡Borra los datos actuales!
 *
 * Resultado: tabla en consola + tests/resultados/resultado_AAAAMMDD_HHMMSS.md (evidencia).
 * Código de salida 0 si todo pasa, 1 si alguna prueba falla.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script se ejecuta desde la consola.\n");
}
if (!extension_loaded('curl')) {
    exit("Falta la extensión curl. En C:\\xampp\\php\\php.ini quita el ';' de 'extension=curl'.\n");
}

$opciones = getopt('', ['reset', 'url::']);
$BASE = rtrim($opciones['url'] ?? 'http://localhost/taller', '/');
define('RAIZ', dirname(__DIR__));
date_default_timezone_set('America/Bogota');

// ---------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------

/** Cliente HTTP con su propia sesión (cookies), como un navegador por usuario. */
final class Navegador
{
    public int $status = 0;
    public string $html = '';
    public string $location = '';
    public array $headers = [];
    private string $cookies;
    private string $base;
    private string $origen;

    public function __construct(string $base)
    {
        $this->base = $base;
        $p = parse_url($base);
        $this->origen = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        $this->cookies = tempnam(sys_get_temp_dir(), 'tt');
    }

    public function get(string $ruta): self
    {
        return $this->pedir('GET', $ruta);
    }

    /** POST con el token CSRF de la sesión (salvo que $conToken sea false). */
    public function post(string $ruta, array $datos = [], bool $conToken = true): self
    {
        if ($conToken && !isset($datos['_csrf'])) {
            $datos['_csrf'] = $this->token();
        }
        return $this->pedir('POST', $ruta, $datos);
    }

    /** Lee el token CSRF de una página de la sesión actual. */
    public function token(): string
    {
        $this->get('/dashboard');
        if ($this->status !== 200) {
            $this->get('/login');
        }
        preg_match('/name="_csrf" value="([^"]+)"/', $this->html, $m);
        return $m[1] ?? '';
    }

    public function login(string $email, string $clave = 'Taller2026*'): self
    {
        $this->get('/login');
        preg_match('/name="_csrf" value="([^"]+)"/', $this->html, $m);
        return $this->pedir('POST', '/login', ['_csrf' => $m[1] ?? '', 'email' => $email, 'password' => $clave]);
    }

    /** Mensajes flash: sigue la redirección (si la hubo) y los extrae de la página. */
    public function flash(): string
    {
        if ($this->location !== '') {
            $this->pedir('GET', $this->location);
        }
        preg_match_all('/class="alert alert-[a-z]+[^"]*"[^>]*>.*?<div>(.*?)<\/div>/s', $this->html, $m);
        return trim(html_entity_decode(strip_tags(implode(' | ', $m[1])), ENT_QUOTES, 'UTF-8'));
    }

    private function pedir(string $metodo, string $ruta, array $datos = []): self
    {
        if (str_starts_with($ruta, 'http')) {
            $url = $ruta;
        } elseif (str_starts_with($ruta, (string) parse_url($this->base, PHP_URL_PATH) . '/')) {
            $url = $this->origen . $ruta;                 // Location: /taller/... (ruta absoluta del servidor)
        } else {
            $url = $this->base . $ruta;                    // ruta interna: /dashboard
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEJAR      => $this->cookies,
            CURLOPT_COOKIEFILE     => $this->cookies,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $respuesta = curl_exec($ch);
        if ($respuesta === false) {
            throw new RuntimeException('Sin conexión con ' . $url . ': ' . curl_error($ch) . '. ¿Apache está encendido y la URL es correcta?');
        }
        $tamHeader = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->html = substr($respuesta, $tamHeader);
        $this->headers = [];
        foreach (explode("\r\n", substr($respuesta, 0, $tamHeader)) as $linea) {
            if (strpos($linea, ':') !== false) {
                [$k, $v] = explode(':', $linea, 2);
                $this->headers[strtolower(trim($k))] = trim($v);
            }
        }
        $this->location = $this->headers['location'] ?? '';
        curl_close($ch);
        return $this;
    }
}

$resultados = [];
$grupo = '';
function grupo(string $nombre): void
{
    global $grupo;
    $grupo = $nombre;
    echo "\n\033[1m== $nombre ==\033[0m\n";
}
function prueba(string $id, string $descripcion, callable $fn): void
{
    global $resultados, $grupo;
    try {
        $detalle = $fn();
        $ok = $detalle === true || (is_string($detalle) && strpos($detalle, 'FALLA') !== 0);
        $detalle = $detalle === true ? '' : (string) $detalle;
    } catch (Throwable $e) {
        $ok = false;
        $detalle = 'FALLA: ' . $e->getMessage();
    }
    $resultados[] = compact('id', 'grupo', 'descripcion', 'ok', 'detalle');
    printf("  %s %-7s %s%s\n", $ok ? "\033[32m✔\033[0m" : "\033[31m✘\033[0m", $id, $descripcion, $detalle !== '' ? "  → $detalle" : '');
}
/** Verificación: devuelve el detalle si se cumple, o 'FALLA: ...' si no. */
function esperar(bool $condicion, string $detalle = ''): string
{
    return $condicion ? ($detalle !== '' ? $detalle : 'ok') : 'FALLA: ' . ($detalle !== '' ? $detalle : 'condición no cumplida');
}

// ---------------------------------------------------------------------
// Conexión a la BD (para verificar efectos y, con --reset, reinstalar)
// ---------------------------------------------------------------------
$env = [];
foreach (@file(RAIZ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
    if ($linea[0] !== '#' && strpos($linea, '=') !== false) {
        [$k, $v] = explode('=', $linea, 2);
        $env[trim($k)] = trim(trim($v), '"\'');
    }
}
$dsnSinBd = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306');
$pdo = new PDO($dsnSinBd, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if (isset($opciones['reset'])) {
    echo "Reinstalando la base de datos desde database/instalar_completo.sql…\n";
    $pdo->exec(file_get_contents(RAIZ . '/database/instalar_completo.sql'));
    $pdo = new PDO($dsnSinBd, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
$pdo->exec('USE `' . ($env['DB_NAME'] ?? 'taller_automotriz') . '`');
$valor = function (string $sql, array $p = []) use ($pdo) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
};

echo "Probando $BASE\n";
$inicio = microtime(true);

$anon    = new Navegador($BASE);
$admin   = new Navegador($BASE);
$asesor  = new Navegador($BASE);
$tecnico = new Navegador($BASE);   // Camilo (id 3)
$tecnico2 = new Navegador($BASE);  // Julián (id 4)
$cliente = new Navegador($BASE);   // María (usuario 5): KLM482, HTS219
$cliente2 = new Navegador($BASE);  // Óscar (usuario 6): BQX731 → OT-2026-0037 (id 1)

// ---------------------------------------------------------------------
grupo('Autenticación y sesión (RF-01, RS-01, RS-03, RS-07)');
prueba('A-01', 'Página de login disponible', fn () => esperar($anon->get('/login')->status === 200));
prueba('A-02', 'Ruta protegida sin sesión redirige al login', fn () => esperar($anon->get('/dashboard')->status === 303 && str_contains($anon->location, '/login')));
prueba('A-03', 'Login correcto de los 4 roles', function () use ($admin, $asesor, $tecnico, $tecnico2, $cliente, $cliente2) {
    foreach ([[$admin, 'admin'], [$asesor, 'asesor'], [$tecnico, 'tecnico'], [$tecnico2, 'tecnico2'], [$cliente, 'cliente'], [$cliente2, 'cliente2']] as [$n, $u]) {
        if (!str_contains($n->login("$u@torque.test")->location, '/dashboard')) {
            return "FALLA: $u no pudo entrar";
        }
    }
    return true;
});
prueba('A-04', 'Contraseña incorrecta: mensaje genérico', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->login('asesor@torque.test', 'clave-mala');
    return esperar(str_contains($n->flash(), 'Correo o contraseña incorrectos'), $n->flash());
});
prueba('A-05', 'Usuario inactivo no entra (mismo mensaje genérico)', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->login('inactivo@torque.test');
    return esperar(str_contains($n->flash(), 'Correo o contraseña incorrectos'));
});
prueba('A-06', 'Contraseñas guardadas como hash (nunca texto plano)', fn () => esperar(
    (int) $valor("SELECT COUNT(*) FROM usuarios WHERE password_hash NOT LIKE '$2y$%' AND password_hash NOT LIKE '\$argon2id$%'") === 0
    && (int) $valor("SELECT COUNT(*) FROM usuarios WHERE password_hash LIKE '%Taller2026%'") === 0));
prueba('A-07', 'Rehash a Argon2id al iniciar sesión (si el PHP lo soporta)', fn () => defined('PASSWORD_ARGON2ID')
    ? esperar(str_starts_with((string) $valor("SELECT password_hash FROM usuarios WHERE email='admin@torque.test'"), '$argon2id$'))
    : 'ok (este PHP no trae Argon2id: se usa bcrypt)');
prueba('A-08', 'Fuerza bruta: bloqueo progresivo tras 5 fallos', function () {
    global $BASE;
    $n = new Navegador($BASE);
    for ($i = 1; $i <= 5; $i++) {
        $n->login('nadie@torque.test', "intento$i");
    }
    $n->login('nadie@torque.test', 'otro');
    return esperar(str_contains($n->flash(), 'Demasiados intentos'), $n->flash());
});
prueba('A-09', 'Cookie de sesión HttpOnly y SameSite', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->get('/login');
    $c = $n->headers['set-cookie'] ?? '';
    return esperar(stripos($c, 'HttpOnly') !== false && stripos($c, 'SameSite=Lax') !== false, $c);
});
prueba('A-10', 'Cerrar sesión invalida la sesión', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->login('admin@torque.test');
    $n->post('/logout');
    return esperar($n->get('/dashboard')->status === 303);
});

// ---------------------------------------------------------------------
grupo('Autorización y propiedad de registros (RF-02, RS-04)');
$matriz = [
    '/usuarios'           => ['admin' => 200, 'asesor' => 403, 'tecnico' => 403, 'cliente' => 403],
    '/auditoria'          => ['admin' => 200, 'asesor' => 403, 'tecnico' => 403, 'cliente' => 403],
    '/clientes'           => ['admin' => 200, 'asesor' => 200, 'tecnico' => 403, 'cliente' => 403],
    '/ordenes/crear'      => ['admin' => 403, 'asesor' => 200, 'tecnico' => 403, 'cliente' => 403],
    '/repuestos'          => ['admin' => 200, 'asesor' => 200, 'tecnico' => 200, 'cliente' => 403],
    '/repuestos/1/ajuste' => ['admin' => 200, 'asesor' => 403, 'tecnico' => 403, 'cliente' => 403],
    '/reportes/ingresos'  => ['admin' => 200, 'asesor' => 403, 'tecnico' => 403, 'cliente' => 403],
];
prueba('P-01', 'Matriz de permisos por URL (7 rutas × 4 roles)', function () use ($matriz, $admin, $asesor, $tecnico, $cliente) {
    $n = compact('admin', 'asesor', 'tecnico', 'cliente');
    foreach ($matriz as $ruta => $esperado) {
        foreach ($esperado as $rol => $codigo) {
            if (($obtenido = $n[$rol]->get($ruta)->status) !== $codigo) {
                return "FALLA: $rol en $ruta dio $obtenido (esperado $codigo)";
            }
        }
    }
    return '28 combinaciones correctas';
});
prueba('P-02', 'El asesor no puede crear usuarios por POST directo', fn () => esperar($asesor->post('/usuarios', ['nombre' => 'X', 'email' => 'x@x.co', 'rol' => 'administrador', 'password' => 'Clave2026x', 'password_confirm' => 'Clave2026x'])->status === 403));
prueba('P-03', 'Un cliente no ve la orden de otro cliente', fn () => esperar($cliente->get('/ordenes/1')->status === 403 && $cliente2->get('/ordenes/1')->status === 200));
prueba('P-04', 'Un técnico no ve órdenes asignadas a otro', fn () => esperar($tecnico2->get('/ordenes/2')->status === 403 && $tecnico->get('/ordenes/2')->status === 200));
prueba('P-05', 'Los accesos denegados quedan auditados', fn () => esperar((int) $valor("SELECT COUNT(*) FROM auditoria WHERE accion='acceso_denegado'") > 0));
prueba('P-06', 'El admin no puede quitarse su propio rol', function () use ($admin, $valor) {
    $admin->post('/usuarios/1', ['nombre' => 'Laura Méndez', 'email' => 'admin@torque.test', 'rol' => 'tecnico']);
    return esperar($valor('SELECT r.nombre FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=1') === 'administrador');
});

// ---------------------------------------------------------------------
grupo('Ataques de prueba (RS-02, RS-05, RS-06)');
prueba('S-01', "Login con ' OR '1'='1 no entra", function () {
    global $BASE;
    $n = new Navegador($BASE);
    return esperar(!str_contains($n->login("' OR '1'='1", "' OR '1'='1")->location, '/dashboard'));
});
prueba('S-02', 'Inyección en búsqueda y orden no altera la consulta', function () use ($asesor, $valor) {
    $antes = (int) $valor('SELECT COUNT(*) FROM vehiculos');
    $asesor->get("/vehiculos?q=%27%20OR%201%3D1%20--%20&sort=placa;DROP%20TABLE%20vehiculos&dir=asc,1&estado=x%27");
    return esperar($asesor->status === 200 && (int) $valor('SELECT COUNT(*) FROM vehiculos') === $antes);
});
prueba('S-03', 'POST sin token CSRF → 419', fn () => esperar($asesor->post('/clientes', ['nombre' => 'x'], false)->status === 419));
prueba('S-04', 'XSS: <script> se guarda y se muestra escapado', function () use ($asesor) {
    $asesor->post('/clientes', ['tipo_documento' => 'CC', 'documento' => '55667788', 'nombre' => '<script>alert(1)</script>', 'telefono' => '3001234567']);
    $html = $asesor->get('/clientes?q=script')->html;
    return esperar(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($html, '<script>alert(1)'));
});
prueba('S-05', 'Precio manipulado en la cotización se ignora (R9)', function () use ($asesor, $tecnico, $valor) {
    $tecnico->post('/ordenes/2/diagnostico', ['descripcion' => 'Bobina del cilindro 3 defectuosa y bujías desgastadas.']);
    $asesor->post('/ordenes/2/cotizacion', ['items' => [
        ['tipo' => 'repuesto', 'repuesto_id' => '4', 'cantidad' => '4', 'precio_unitario' => '1'],
        ['tipo' => 'mano_obra', 'descripcion' => 'Cambio de bujías y bobina', 'cantidad' => '2', 'precio_unitario' => '55000'],
    ]]);
    $precio = (float) $valor("SELECT i.precio_unitario FROM cotizacion_items i JOIN cotizaciones c ON c.id=i.cotizacion_id WHERE c.orden_id=2 AND i.tipo='repuesto'");
    return esperar($precio === 41000.0, "guardado \$$precio (catálogo)");
});
prueba('S-06', 'Ruta inexistente → 404 propio; método incorrecto → 405', fn () => esperar($admin->get('/no-existe')->status === 404 && $admin->get('/logout')->status === 405));

// ---------------------------------------------------------------------
grupo('Validación (RF-10) y reglas de los maestros');
prueba('V-01', 'Cliente con documento duplicado y teléfono inválido', function () use ($asesor) {
    $asesor->post('/clientes', ['tipo_documento' => 'CC', 'documento' => '1020304050', 'nombre' => 'Dup', 'telefono' => '12']);
    $html = $asesor->get('/clientes/crear')->html;
    return esperar(str_contains($html, 'Ya existe un cliente con ese documento') && str_contains($html, 'entre 7 y 15 dígitos'));
});
prueba('V-02', 'Vehículo con placa inválida y año fuera de rango', function () use ($asesor) {
    $asesor->post('/vehiculos', ['cliente_id' => '1', 'placa' => '12ABC', 'marca' => 'X', 'modelo' => 'Y', 'anio' => '1900']);
    $html = $asesor->get('/vehiculos/crear')->html;
    return esperar(str_contains($html, 'Formato de placa inválido') && str_contains($html, 'El año debe estar entre'));
});
prueba('V-03', 'No se desactiva un cliente con órdenes abiertas', function () use ($asesor) {
    $asesor->post('/clientes/2/estado');
    return esperar(str_contains($asesor->flash(), 'No se puede desactivar'));
});
prueba('V-04', 'R5: no se abren dos órdenes para el mismo vehículo', function () use ($asesor) {
    $asesor->post('/ordenes', ['vehiculo_id' => '1', 'placa' => 'KLM482', 'motivo_ingreso' => 'Otra revisión', 'kilometraje_ingreso' => '95000']);
    return esperar(str_contains($asesor->flash(), 'ya tiene la orden'));
});
prueba('V-05', 'R11: el kilometraje no puede retroceder', function () use ($asesor) {
    $asesor->post('/ordenes', ['vehiculo_id' => '5', 'placa' => 'JPN347', 'motivo_ingreso' => 'Revisión general', 'kilometraje_ingreso' => '100']);
    return esperar(str_contains($asesor->flash(), 'no puede ser menor'));
});
prueba('V-06', 'Repuesto: precio de venta menor al costo', function () use ($admin) {
    $admin->post('/repuestos', ['codigo' => 'TEST-01', 'nombre' => 'Prueba', 'costo' => '100000', 'precio_venta' => '90000', 'stock_minimo' => '1']);
    return esperar(str_contains($admin->get('/repuestos/crear')->html, 'no puede ser menor al costo'));
});

// ---------------------------------------------------------------------
grupo('Proceso principal de extremo a extremo (RF-04) · 4 roles');
$aceiteAntes = (int) $valor('SELECT stock FROM repuestos WHERE id = 2');
prueba('F-01', 'Asesor: asigna técnico (TRK905, Recibida → Diagnóstico)', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/3/asignar', ['tecnico_id' => '4']);
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') === 'diagnostico');
});
prueba('F-02', 'Técnico: registra el diagnóstico', function () use ($tecnico2, $valor) {
    $tecnico2->post('/ordenes/3/diagnostico', ['descripcion' => 'Aceite oscuro por hollín; cambio de aceite (6 L) y revisión.']);
    return esperar((int) $valor('SELECT COUNT(*) FROM diagnosticos WHERE orden_id=3') === 1);
});
prueba('F-03', 'Asesor: envía la cotización (→ Cotizada)', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/3/cotizacion', ['items' => [
        ['tipo' => 'repuesto', 'repuesto_id' => '2', 'cantidad' => '6'],
        ['tipo' => 'mano_obra', 'descripcion' => 'Cambio de aceite motor diésel', 'cantidad' => '1.5', 'precio_unitario' => '50000'],
    ]]);
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') === 'cotizada');
});
prueba('F-04', 'Asesor registra la aprobación telefónica (cliente sin portal)', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/3/respuesta', ['decision' => 'aprobar', 'canal' => 'telefono']);
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') === 'aprobada'
        && $valor('SELECT canal_respuesta FROM cotizaciones WHERE orden_id=3') === 'telefono');
});
prueba('F-05', 'R1: no se inicia sin aprobación (OT-0038 rechazada por el asesor)', function () use ($asesor, $tecnico, $valor) {
    $asesor->post('/ordenes/2/respuesta', ['decision' => 'rechazar', 'canal' => 'presencial', 'motivo' => 'Muy costosa']);
    $tecnico->post('/ordenes/2/iniciar');
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=2') === 'rechazada');
});
prueba('F-06', 'Técnico inicia la reparación: stock -6 y salida en el kardex (R2)', function () use ($tecnico2, $valor, $aceiteAntes) {
    $tecnico2->post('/ordenes/3/iniciar');
    $ahora = (int) $valor('SELECT stock FROM repuestos WHERE id=2');
    return esperar($ahora === $aceiteAntes - 6 && (int) $valor("SELECT COUNT(*) FROM movimientos_inventario WHERE orden_id=3 AND tipo='salida'") === 1,
        "aceite $aceiteAntes → $ahora");
});
prueba('F-07', 'Técnico marca la orden como lista', function () use ($tecnico2, $valor) {
    $tecnico2->post('/ordenes/3/terminar', ['nota' => 'Revisar filtro de combustible en el próximo servicio']);
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') === 'lista');
});
prueba('F-08', 'R4: no se entrega sin confirmar; luego se liquida y entrega', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/3/liquidar', ['metodo' => 'tarjeta']);
    if ($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') !== 'lista') {
        return 'FALLA: se entregó sin confirmar';
    }
    $asesor->post('/ordenes/3/liquidar', ['metodo' => 'tarjeta', 'confirma_entrega' => '1']);
    $total = (float) $valor('SELECT total FROM liquidaciones WHERE orden_id=3');
    // 6 × 45.000 + 1,5 × 50.000 = 345.000 + 19 % = 410.550
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=3') === 'entregada' && abs($total - 410550) < 0.01, "total \$$total");
});
prueba('F-09', 'Cotización rechazada: se cobra solo el diagnóstico ($71.400)', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/2/liquidar', ['metodo' => 'efectivo', 'confirma_entrega' => '1']);
    return esperar((float) $valor('SELECT total FROM liquidaciones WHERE orden_id=2') === 71400.0);
});
prueba('F-10', 'Cliente aprueba su cotización desde el portal; otro cliente no puede', function () use ($cliente, $cliente2, $valor) {
    if ($cliente->post('/ordenes/1/respuesta', ['decision' => 'aprobar'])->status !== 403) {
        return 'FALLA: un cliente ajeno pudo responder';
    }
    $cliente2->post('/ordenes/1/respuesta', ['decision' => 'aprobar']);
    return esperar($valor('SELECT canal_respuesta FROM cotizaciones WHERE orden_id=1') === 'portal');
});
prueba('F-11', 'El historial registra cada transición con su autor', fn () => esperar((int) $valor('SELECT COUNT(*) FROM orden_historial_estados WHERE orden_id=3') === 7));
prueba('F-12', 'Transición inválida: no se puede liquidar una orden aprobada', function () use ($asesor, $valor) {
    $asesor->post('/ordenes/1/liquidar', ['metodo' => 'efectivo', 'confirma_entrega' => '1']);
    return esperar($valor('SELECT estado FROM ordenes_trabajo WHERE id=1') === 'aprobada');
});

// ---------------------------------------------------------------------
grupo('Inventario');
prueba('I-01', 'Entrada de mercancía suma stock y queda en el kardex', function () use ($asesor, $valor) {
    $antes = (int) $valor('SELECT stock FROM repuestos WHERE id=3');
    $asesor->post('/inventario/entrada', ['repuesto_id' => '3', 'proveedor_id' => '1', 'cantidad' => '10', 'costo_unitario' => '14500', 'nota' => 'Prueba automática']);
    return esperar((int) $valor('SELECT stock FROM repuestos WHERE id=3') === $antes + 10);
});
prueba('I-02', 'Un ajuste no puede dejar el stock negativo', function () use ($admin) {
    $admin->post('/repuestos/5/ajuste', ['sentido' => 'restar', 'cantidad' => '99', 'motivo' => 'Prueba automática']);
    return esperar(str_contains($admin->flash(), 'dejaría el stock'));
});
prueba('I-03', 'Kardex: la suma de movimientos = stock en TODOS los repuestos', fn () => esperar((int) $valor(
    "SELECT COUNT(*) FROM repuestos r WHERE r.stock <> (SELECT COALESCE(SUM(CASE tipo WHEN 'salida' THEN -cantidad ELSE cantidad END),0) FROM movimientos_inventario m WHERE m.repuesto_id=r.id)") === 0));

// ---------------------------------------------------------------------
grupo('Reportes y CSV (RF-09)');
prueba('R-01', 'Reporte de órdenes: el total coincide con SQL', function () use ($admin, $valor) {
    $desde = date('Y-m-d', strtotime('-89 days'));
    $html = $admin->get("/reportes/ordenes?desde=$desde&hasta=" . date('Y-m-d'))->html;
    preg_match('/Órdenes ingresadas<\/span>\s*<span class="kpi-value">(\d+)/u', $html, $m);
    $sql = (int) $valor('SELECT COUNT(*) FROM ordenes_trabajo WHERE fecha_ingreso >= ?', [$desde . ' 00:00:00']);
    $app = isset($m[1]) ? (int) $m[1] : -1;
    return esperar($app === $sql, "app $app = SQL $sql");
});
prueba('R-02', 'CSV: tipo correcto, BOM UTF-8 y separador ;', function () use ($asesor) {
    $asesor->get('/reportes/ordenes?formato=csv');
    return esperar(str_contains($asesor->headers['content-type'] ?? '', 'text/csv') && str_starts_with($asesor->html, "\xEF\xBB\xBF") && str_contains($asesor->html, 'Orden;Placa'));
});
prueba('R-03', 'CSV: protección contra inyección de fórmulas', function () use ($asesor, $pdo) {
    $pdo->exec("UPDATE clientes SET nombre='=HYPERLINK(\"http://x\")' WHERE id=4");
    $csv = $asesor->get('/reportes/ordenes?formato=csv&desde=' . date('Y-m-d', strtotime('-200 days')))->html;
    $pdo->exec("UPDATE clientes SET nombre='Carolina Ríos' WHERE id=4");
    return esperar(str_contains($csv, "'=HYPERLINK") && !preg_match('/;"?=HYPERLINK/', $csv));
});
prueba('R-04', 'Las exportaciones quedan auditadas', fn () => esperar((int) $valor("SELECT COUNT(*) FROM auditoria WHERE accion='exportar_csv'") >= 2));

// ---------------------------------------------------------------------
grupo('Autorregistro de clientes');
/** Formulario de registro con su propio token CSRF (navegador sin sesión). */
function registrar(Navegador $n, array $datos): Navegador
{
    $n->get('/registro');
    preg_match('/name="_csrf" value="([^"]+)"/', $n->html, $m);
    return $n->post('/registro', $datos + ['_csrf' => $m[1] ?? '']);
}
$base = ['tipo_documento' => 'CC', 'nombre' => 'Valentina Cruz', 'telefono' => '311 222 3344',
         'password' => 'Portal2026', 'password_confirm' => 'Portal2026', 'acepta_datos' => '1'];
prueba('RG-01', 'El login enlaza al registro; con sesión iniciada /registro redirige al tablero', function () use ($admin) {
    global $BASE;
    $n = new Navegador($BASE);
    return esperar(str_contains($n->get('/login')->html, '/registro') && $n->get('/registro')->status === 200
        && $admin->get('/registro')->status === 303);
});
prueba('RG-02', 'Registro válido: entra al tablero como CLIENTE (aunque envíe rol=administrador)', function () use ($base, $valor) {
    global $BASE;
    $n = new Navegador($BASE);
    registrar($n, $base + ['documento' => '1.019.876.543', 'email' => 'VALE@correo.test', 'rol' => 'administrador', 'rol_id' => '1']);
    $ok = str_contains($n->location, '/dashboard') && $n->get('/dashboard')->status === 200 && str_contains($n->html, 'Hola, Valentina Cruz');
    $rol = $valor("SELECT r.nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.email = 'vale@correo.test'");
    $vinculo = (int) $valor("SELECT COUNT(*) FROM clientes c JOIN usuarios u ON u.id = c.usuario_id WHERE c.documento = '1019876543' AND u.email = 'vale@correo.test'");
    return esperar($ok && $rol === 'cliente' && $vinculo === 1, "rol $rol, cliente vinculado: $vinculo");
});
prueba('RG-03', 'La contraseña queda como hash y el registro se audita sin ella', fn () => esperar(
    str_starts_with((string) $valor("SELECT password_hash FROM usuarios WHERE email = 'vale@correo.test'"), '$')
    && (int) $valor("SELECT COUNT(*) FROM auditoria WHERE accion = 'registro_cliente' AND datos_despues NOT LIKE '%Portal2026%'") === 1));
prueba('RG-04', 'Documento ya registrado por el taller: mensaje genérico y NO se vincula', function () use ($base, $valor) {
    global $BASE;
    $n = new Navegador($BASE);
    registrar($n, $base + ['documento' => '52344556', 'email' => 'impostor@correo.test']);   // Carolina Ríos (sin portal)
    return esperar(str_contains($n->flash(), 'No pudimos crear la cuenta')
        && $valor("SELECT usuario_id FROM clientes WHERE documento = '52344556'") === null
        && (int) $valor("SELECT COUNT(*) FROM usuarios WHERE email = 'impostor@correo.test'") === 0);
});
prueba('RG-05', 'Correo ya usado: el mismo mensaje genérico (no revela cuál dato existe)', function () use ($base) {
    global $BASE;
    $n = new Navegador($BASE);
    registrar($n, $base + ['documento' => '1098000111', 'email' => 'admin@torque.test']);
    return esperar(str_contains($n->flash(), 'No pudimos crear la cuenta'));
});
prueba('RG-06', 'Validación: sin autorización de datos, clave débil y celular inválido', function () use ($base) {
    global $BASE;
    $n = new Navegador($BASE);
    $datos = ['documento' => '1098000222', 'email' => 'otro@correo.test', 'telefono' => '12', 'password' => 'corta', 'password_confirm' => 'corta'] + $base;
    unset($datos['acepta_datos']);
    registrar($n, $datos);
    $html = $n->get('/registro')->html;
    return esperar(str_contains($html, 'tratamiento de tus datos') && str_contains($html, 'entre 8 y 64') && str_contains($html, 'entre 7 y 15 dígitos'));
});
prueba('RG-07', 'Campo trampa (honeypot) lleno: no se crea la cuenta', function () use ($base, $valor) {
    global $BASE;
    $n = new Navegador($BASE);
    registrar($n, $base + ['documento' => '1098000333', 'email' => 'bot@correo.test', 'sitio_web' => 'http://spam.test']);
    return esperar((int) $valor("SELECT COUNT(*) FROM usuarios WHERE email = 'bot@correo.test'") === 0);
});
prueba('RG-08', 'POST de registro sin token CSRF → 419', function () use ($base) {
    global $BASE;
    return esperar((new Navegador($BASE))->post('/registro', $base + ['documento' => '1098000444', 'email' => 'x@correo.test'], false)->status === 419);
});
prueba('RG-09', 'Límite: máximo 5 cuentas por IP por hora', function () use ($base, $valor) {
    global $BASE;
    for ($i = 1; $i <= 5; $i++) {   // la 1.ª ya se creó en RG-02
        registrar(new Navegador($BASE), $base + ['documento' => "10980010$i", 'email' => "masivo$i@correo.test"]);
    }
    $creadas = (int) $valor("SELECT COUNT(*) FROM usuarios WHERE email LIKE 'masivo%@correo.test'");
    return esperar($creadas === 4, "se crearon $creadas de 5 intentos; el 5.º fue bloqueado");
});

// ---------------------------------------------------------------------
$segundos = round(microtime(true) - $inicio, 1);
$ok = count(array_filter($resultados, fn ($r) => $r['ok']));
$total = count($resultados);
echo "\n" . ($ok === $total ? "\033[32m" : "\033[31m") . "Resultado: $ok de $total pruebas pasaron ($segundos s)\033[0m\n";

// Evidencia en Markdown
@mkdir(__DIR__ . '/resultados', 0775, true);
$archivo = __DIR__ . '/resultados/resultado_' . date('Ymd_His') . '.md';
$md = "# Resultado de pruebas automáticas\n\n- Fecha: " . date('Y-m-d H:i:s') . "\n- URL: $BASE\n- PHP " . PHP_VERSION
    . "\n- Resultado: **$ok de $total** ($segundos s)\n\n| ID | Grupo | Prueba | Resultado | Detalle |\n|---|---|---|---|---|\n";
foreach ($resultados as $r) {
    $md .= sprintf("| %s | %s | %s | %s | %s |\n", $r['id'], $r['grupo'], $r['descripcion'], $r['ok'] ? '✅' : '❌', str_replace('|', '/', $r['detalle']));
}
file_put_contents($archivo, $md);
echo "Evidencia: tests/resultados/" . basename($archivo) . "\n";
exit($ok === $total ? 0 : 1);

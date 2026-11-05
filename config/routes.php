<?php
declare(strict_types=1);

/**
 * Mapa de rutas.  $router->MÉTODO(ruta, [Controlador, método], [middleware]).
 * Middleware: 'guest' | 'auth' | 'role:rol1,rol2'.  Todo POST valida CSRF automáticamente.
 * {id} solo acepta números.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuditoriaController;
use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\DashboardController;
use App\Controllers\OrdenController;
use App\Controllers\UsuarioController;
use App\Controllers\VehiculoController;

// ---- Acceso ----
$router->get('/',        [AuthController::class, 'home']);
$router->get('/login',   [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login',  [AuthController::class, 'login'],     ['guest']);
$router->post('/logout', [AuthController::class, 'logout'],    ['auth']);

$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);

// ---- Usuarios: solo administrador ----
$admin = ['auth', 'role:administrador'];
$router->get('/usuarios',              [UsuarioController::class, 'index'],  $admin);
$router->get('/usuarios/crear',        [UsuarioController::class, 'create'], $admin);
$router->post('/usuarios',             [UsuarioController::class, 'store'],  $admin);
$router->get('/usuarios/{id}/editar',  [UsuarioController::class, 'edit'],   $admin);
$router->post('/usuarios/{id}',        [UsuarioController::class, 'update'], $admin);
$router->post('/usuarios/{id}/estado', [UsuarioController::class, 'toggle'], $admin);

$router->get('/auditoria', [AuditoriaController::class, 'index'], $admin);

// ---- Clientes y vehículos: administrador y asesor ----
$recepcion = ['auth', 'role:administrador,asesor'];
$router->get('/clientes',              [ClienteController::class, 'index'],  $recepcion);
$router->get('/clientes/crear',        [ClienteController::class, 'create'], $recepcion);
$router->post('/clientes',             [ClienteController::class, 'store'],  $recepcion);
$router->get('/clientes/{id}',         [ClienteController::class, 'show'],   $recepcion);
$router->get('/clientes/{id}/editar',  [ClienteController::class, 'edit'],   $recepcion);
$router->post('/clientes/{id}',        [ClienteController::class, 'update'], $recepcion);
$router->post('/clientes/{id}/estado', [ClienteController::class, 'toggle'], $recepcion);

$router->get('/vehiculos',              [VehiculoController::class, 'index'],  $recepcion);
$router->get('/vehiculos/crear',        [VehiculoController::class, 'create'], $recepcion);
$router->post('/vehiculos',             [VehiculoController::class, 'store'],  $recepcion);
$router->get('/vehiculos/{id}',         [VehiculoController::class, 'show'],   $recepcion);
$router->get('/vehiculos/{id}/editar',  [VehiculoController::class, 'edit'],   $recepcion);
$router->post('/vehiculos/{id}',        [VehiculoController::class, 'update'], $recepcion);
$router->post('/vehiculos/{id}/estado', [VehiculoController::class, 'toggle'], $recepcion);

// ---- Órdenes de trabajo (proceso principal) ----
// La ficha la ven todos los roles; OrdenController::autorizada() verifica que la orden sea suya.
$asesor = ['auth', 'role:asesor'];
$router->get('/ordenes',                  [OrdenController::class, 'index'],             $recepcion);
$router->get('/ordenes/crear',            [OrdenController::class, 'create'],            $asesor);
$router->post('/ordenes',                 [OrdenController::class, 'store'],             $asesor);
$router->get('/ordenes/{id}',             [OrdenController::class, 'show'],              ['auth']);
$router->post('/ordenes/{id}/asignar',    [OrdenController::class, 'asignar'],           $asesor);
$router->post('/ordenes/{id}/diagnostico',[OrdenController::class, 'diagnostico'],       ['auth', 'role:tecnico']);
$router->get('/ordenes/{id}/cotizacion',  [OrdenController::class, 'editarCotizacion'],  $asesor);
$router->post('/ordenes/{id}/cotizacion', [OrdenController::class, 'guardarCotizacion'], $asesor);
$router->post('/ordenes/{id}/respuesta',  [OrdenController::class, 'responder'],         ['auth', 'role:asesor,cliente']);
$router->post('/ordenes/{id}/iniciar',    [OrdenController::class, 'iniciar'],           ['auth', 'role:tecnico']);
$router->post('/ordenes/{id}/terminar',   [OrdenController::class, 'terminar'],          ['auth', 'role:tecnico']);
$router->get('/ordenes/{id}/liquidar',    [OrdenController::class, 'liquidacion'],       $asesor);
$router->post('/ordenes/{id}/liquidar',   [OrdenController::class, 'liquidar'],          $asesor);

// ---- Inventario ----
$inventario = ['auth', 'role:administrador,asesor,tecnico'];
$router->get('/repuestos',               [App\Controllers\RepuestoController::class, 'index'],       $inventario);
$router->get('/repuestos/crear',         [App\Controllers\RepuestoController::class, 'create'],      $admin);
$router->post('/repuestos',              [App\Controllers\RepuestoController::class, 'store'],       $admin);
$router->get('/repuestos/{id}',          [App\Controllers\RepuestoController::class, 'show'],        $inventario);
$router->get('/repuestos/{id}/editar',   [App\Controllers\RepuestoController::class, 'edit'],        $admin);
$router->post('/repuestos/{id}',         [App\Controllers\RepuestoController::class, 'update'],      $admin);
$router->post('/repuestos/{id}/estado',  [App\Controllers\RepuestoController::class, 'toggle'],      $admin);
$router->get('/repuestos/{id}/ajuste',   [App\Controllers\RepuestoController::class, 'formAjuste'],  $admin);
$router->post('/repuestos/{id}/ajuste',  [App\Controllers\RepuestoController::class, 'ajuste'],      $admin);
$router->get('/inventario/entrada',      [App\Controllers\RepuestoController::class, 'formEntrada'], $recepcion);
$router->post('/inventario/entrada',     [App\Controllers\RepuestoController::class, 'entrada'],     $recepcion);

$router->get('/proveedores',              [App\Controllers\ProveedorController::class, 'index'],  $recepcion);
$router->get('/proveedores/crear',        [App\Controllers\ProveedorController::class, 'create'], $admin);
$router->post('/proveedores',             [App\Controllers\ProveedorController::class, 'store'],  $admin);
$router->get('/proveedores/{id}/editar',  [App\Controllers\ProveedorController::class, 'edit'],   $admin);
$router->post('/proveedores/{id}',        [App\Controllers\ProveedorController::class, 'update'], $admin);
$router->post('/proveedores/{id}/estado', [App\Controllers\ProveedorController::class, 'toggle'], $admin);

// ---- Reportes (RF-09) ----
$router->get('/reportes',           [App\Controllers\ReporteController::class, 'ordenes'],   $recepcion);
$router->get('/reportes/ordenes',   [App\Controllers\ReporteController::class, 'ordenes'],   $recepcion);
$router->get('/reportes/ingresos',  [App\Controllers\ReporteController::class, 'ingresos'],  $admin);
$router->get('/reportes/repuestos', [App\Controllers\ReporteController::class, 'repuestos'], $recepcion);

// ---- Autorregistro de clientes (solo visitantes sin sesión) ----
$router->get('/registro',  [App\Controllers\RegistroController::class, 'show'],  ['guest']);
$router->post('/registro', [App\Controllers\RegistroController::class, 'store'], ['guest']);

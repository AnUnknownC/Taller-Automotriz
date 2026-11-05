<?php
declare(strict_types=1);

/**
 * Front controller: TODA petición entra por aquí (ver .htaccess).
 * Flujo: Router -> Middleware (CSRF, auth, rol) -> Controlador -> Modelo (PDO) -> Vista.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/bootstrap.php';

$router = new App\Core\Router();
require BASE_PATH . '/config/routes.php';
$router->dispatch();

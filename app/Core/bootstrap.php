<?php
declare(strict_types=1);

/**
 * Arranque de la aplicación: autoload, configuración, manejo de errores y sesión.
 * Lo carga public/index.php (front controller).
 */

// Autoloader PSR-4 simple: App\Core\Router -> app/Core/Router.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Session;

// RS-08: nunca mostrar errores técnicos al usuario ni argumentos (contraseñas) en trazas
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('zend.exception_ignore_args', '1');
error_reporting(E_ALL);

ErrorHandler::register();

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config/app.php');
date_default_timezone_set((string) Config::get('app.timezone', 'America/Bogota'));

Session::start();
App\Core\FormState::load();

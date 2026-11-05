<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'             => Env::get('APP_NAME', 'Torque · Gestión de Taller'),
        'timezone'         => Env::get('APP_TIMEZONE', 'America/Bogota'),
        'session_lifetime' => (int) Env::get('SESSION_LIFETIME', '20'), // minutos de inactividad
    ],
    'taller' => [
        // Valores de la liquidación SIMULADA (no es facturación legal)
        'iva'                => (float) Env::get('TALLER_IVA', '0.19'),
        'tarifa_diagnostico' => (float) Env::get('TARIFA_DIAGNOSTICO', '60000'),
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'taller_automotriz'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
    ],
];

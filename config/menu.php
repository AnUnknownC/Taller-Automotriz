<?php
declare(strict_types=1);

/**
 * Menú lateral por rol (RF-07). Solo se listan módulos que ya existen:
 * cada sprint agrega aquí sus entradas (no hay enlaces "decorativos").
 * Ocultar un enlace NO es seguridad: la protección real está en config/routes.php.
 */
return [
    ['label' => 'Inicio',    'icon' => 'speedometer2', 'path' => '/dashboard', 'roles' => ['administrador', 'asesor', 'tecnico', 'cliente']],
    ['label' => 'Órdenes',   'icon' => 'clipboard2-check', 'path' => '/ordenes', 'roles' => ['administrador', 'asesor']],
    ['label' => 'Clientes',  'icon' => 'people',       'path' => '/clientes',  'roles' => ['administrador', 'asesor']],
    ['label' => 'Vehículos', 'icon' => 'car-front',    'path' => '/vehiculos', 'roles' => ['administrador', 'asesor']],
    ['label' => 'Inventario',  'icon' => 'box-seam',  'path' => '/repuestos',   'roles' => ['administrador', 'asesor', 'tecnico']],
    ['label' => 'Proveedores', 'icon' => 'truck',     'path' => '/proveedores', 'roles' => ['administrador', 'asesor']],
    ['label' => 'Reportes',    'icon' => 'graph-up',  'path' => '/reportes',    'roles' => ['administrador', 'asesor']],
    ['label' => 'Usuarios',  'icon' => 'person-gear',  'path' => '/usuarios',  'roles' => ['administrador']],
    ['label' => 'Auditoría', 'icon' => 'shield-check', 'path' => '/auditoria', 'roles' => ['administrador']],
];

<?php
/** Layout de la aplicación autenticada. @var string $content @var string $title */
$user = current_user();
$menu = array_filter(
    require BASE_PATH . '/config/menu.php',
    fn (array $item): bool => in_array($user['rol'] ?? '', $item['roles'], true)
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%277%27 fill=%27%23f0a71a%27/%3E%3Cpath d=%27M9 23l8-8%27 stroke=%27%231c232c%27 stroke-width=%273.5%27 stroke-linecap=%27round%27/%3E%3Ccircle cx=%2720%27 cy=%2712%27 r=%275%27 fill=%27none%27 stroke=%27%231c232c%27 stroke-width=%273%27/%3E%3C/svg%3E">
    <title><?= e($title ?? 'Inicio') ?> · <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app">
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="topbar">
    <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu-lateral"
            aria-controls="menu-lateral" aria-label="Abrir menú">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <a class="brand" href="<?= e(url('/dashboard')) ?>" aria-label="Torque, ir al inicio">
        <span class="brand-mark" aria-hidden="true"><i class="bi bi-wrench-adjustable"></i></span>
        <span class="brand-name">Torque</span>
    </a>
    <div class="topbar-user">
        <span class="user-meta">
            <span class="user-name"><?= e($user['nombre']) ?></span>
            <span class="role-chip role-<?= e($user['rol']) ?>"><?= e(rol_label($user['rol'])) ?></span>
        </span>
        <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-logout">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="d-none d-sm-inline"> Salir</span>
                <span class="visually-hidden d-sm-none">Cerrar sesión</span>
            </button>
        </form>
    </div>
</header>

<div class="shell">
    <nav class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="menu-lateral" aria-label="Menú principal">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold">Menú</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    data-bs-target="#menu-lateral" aria-label="Cerrar menú"></button>
        </div>
        <ul class="nav flex-column">
            <?php foreach ($menu as $item): $activo = is_current($item['path']); ?>
                <li class="nav-item">
                    <a class="nav-link<?= $activo ? ' active' : '' ?>" href="<?= e(url($item['path'])) ?>"
                       <?= $activo ? 'aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= e($item['icon']) ?>" aria-hidden="true"></i>
                        <?= e($item['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <main id="contenido" class="content" tabindex="-1">
        <?= App\Core\View::partial('flash') ?>
        <?= $content ?>
    </main>
</div>

<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

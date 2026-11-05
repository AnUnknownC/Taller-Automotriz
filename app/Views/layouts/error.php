<?php /** Layout mínimo para 403/404/405/419/500. @var string $content */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%277%27 fill=%27%23f0a71a%27/%3E%3Cpath d=%27M9 23l8-8%27 stroke=%27%231c232c%27 stroke-width=%273.5%27 stroke-linecap=%27round%27/%3E%3Ccircle cx=%2720%27 cy=%2712%27 r=%275%27 fill=%27none%27 stroke=%27%231c232c%27 stroke-width=%273%27/%3E%3C/svg%3E">
    <title><?= e($title ?? 'Error') ?> · Torque</title>
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth">
<main class="auth-shell"><?= $content ?></main>
</body>
</html>

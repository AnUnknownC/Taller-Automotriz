<?php
/** @var int $status @var string $message @var string|null $detail */
$textos = [
    403 => ['Acceso denegado', 'No tienes permiso para ver esta página.', 'shield-lock'],
    404 => ['Página no encontrada', 'La dirección no existe o fue movida.', 'signpost-split'],
    405 => ['Método no permitido', 'Esta acción no se puede realizar de esta forma.', 'slash-circle'],
    419 => ['Formulario expirado', 'El formulario ya no es válido. Recarga la página e intenta de nuevo.', 'hourglass-split'],
    500 => ['Algo salió mal', 'Ocurrió un error inesperado. Ya quedó registrado; intenta de nuevo en un momento.', 'tools'],
];
[$titulo, $texto, $icono] = $textos[$status] ?? $textos[500];
$logueado = false;
try {
    $logueado = App\Core\Auth::check();
} catch (Throwable $e) {
    // Si la BD no responde, no se puede saber; se ofrece volver al inicio.
}
?>
<section class="error-card" aria-labelledby="error-titulo">
    <div class="error-code" aria-hidden="true"><i class="bi bi-<?= e($icono) ?>"></i> <?= (int) $status ?></div>
    <h1 id="error-titulo"><?= e($titulo) ?></h1>
    <p><?= e($message !== '' ? $message : $texto) ?></p>
    <?php if (!empty($detail)): ?>
        <pre class="error-detail"><?= e($detail) ?></pre>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= e(url($logueado ? '/dashboard' : '/login')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> <?= $logueado ? 'Volver al inicio' : 'Ir al inicio de sesión' ?>
    </a>
</section>

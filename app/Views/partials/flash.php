<?php
/** RF-12: retroalimentación de éxito/error tras cada acción. */
$iconos = ['success' => 'check-circle', 'danger' => 'exclamation-octagon', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
foreach (App\Core\Session::pullFlashes() as $flash):
    $tipo = array_key_exists($flash['type'], $iconos) ? $flash['type'] : 'info';
?>
    <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show d-flex gap-2 align-items-start"
         role="<?= $tipo === 'danger' ? 'alert' : 'status' ?>">
        <i class="bi bi-<?= e($iconos[$tipo]) ?> flex-shrink-0" aria-hidden="true"></i>
        <div><?= e($flash['message']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar mensaje"></button>
    </div>
<?php endforeach; ?>

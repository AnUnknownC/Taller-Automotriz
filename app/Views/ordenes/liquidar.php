<?php /** @var array $orden @var array $totales */
$id = (int) $orden['id'];
$metodo = old('metodo', 'efectivo');
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/ordenes')) ?>">Órdenes</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url("/ordenes/$id")) ?>"><?= e($orden['codigo']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Liquidar</li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap">Liquidar y entregar <?= placa($orden['placa']) ?>
            <span class="fs-5 fw-normal"><?= e($orden['cliente']) ?></span></h1>
    </div>
</header>

<div class="alert alert-info d-flex gap-2 align-items-start" role="note">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    <div><strong>Simulación:</strong> no se procesa ningún pago real ni se genera una factura legal.
        Solo queda el registro del cobro para los reportes del taller.</div>
</div>

<form method="post" action="<?= e(url("/ordenes/$id/liquidar")) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <?php if ($totales['solo_diagnostico']): ?>
        <p class="mb-2"><span class="estado estado-rechazada">Cotización rechazada</span> Se cobra solo el diagnóstico.</p>
    <?php endif; ?>

    <?= App\Core\View::partial('cotizacion_items', ['items' => $totales['items'], 'totales' => $totales]) ?>

    <fieldset class="mt-4">
        <legend class="fs-6 fw-bold">Método de pago (simulado) <span class="req" aria-hidden="true">*</span></legend>
        <div class="metodos">
            <?php foreach (App\Models\Liquidacion::METODOS as $k => $l): ?>
                <input type="radio" class="btn-check" name="metodo" id="metodo-<?= $k ?>" value="<?= $k ?>" <?= $metodo === $k ? 'checked' : '' ?> required>
                <label class="btn btn-outline-secondary" for="metodo-<?= $k ?>">
                    <i class="bi bi-<?= ['efectivo' => 'cash', 'tarjeta' => 'credit-card', 'transferencia' => 'bank'][$k] ?>" aria-hidden="true"></i> <?= e($l) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="form-check mt-4">
        <input class="form-check-input<?= invalid('confirma_entrega') ?>" type="checkbox" id="confirma_entrega" name="confirma_entrega" value="1" required <?= aria_error('confirma_entrega') ?>>
        <label class="form-check-label" for="confirma_entrega">Confirmo que el cliente recibe el vehículo <?= e($orden['placa']) ?>.</label>
        <?= field_error('confirma_entrega') ?: '<div class="invalid-feedback">Debes confirmar la entrega.</div>' ?>
    </div>

    <div class="form-actions">
        <a href="<?= e(url("/ordenes/$id")) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Registrando…">
            Registrar liquidación por <?= pesos($totales['total']) ?> y entregar
        </button>
    </div>
</form>

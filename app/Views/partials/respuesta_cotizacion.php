<?php
/**
 * Aprobar / rechazar la cotización. Lo usan el cliente (portal) y el asesor (registra la respuesta presencial o telefónica).
 * @var int $id @var bool $esAsesor @var float $total
 */
$motivos = ['Muy costosa', 'Lo haré en otro taller', 'Ya no necesito el arreglo'];
?>
<div class="respuesta-cot mt-3">
    <?php if ($esAsesor): ?>
        <p class="small text-secondary mb-2"><i class="bi bi-telephone" aria-hidden="true"></i>
            ¿El cliente respondió en persona o por teléfono? Registra su decisión; quedará constancia de que la anotaste tú.</p>
    <?php else: ?>
        <p class="mb-2 fw-semibold">¿Autorizas este trabajo por <?= pesos($total) ?>?</p>
    <?php endif; ?>

    <div class="d-flex gap-2 flex-wrap">
        <form method="post" action="<?= e(url("/ordenes/$id/respuesta")) ?>" class="flex-grow-1"
              data-confirm="<?= e('¿Aprobar la cotización por ' . pesos($total) . '? El taller iniciará la reparación.') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="decision" value="aprobar">
            <?php if ($esAsesor): ?>
                <label class="visually-hidden" for="canal-aprobar">Medio</label>
                <select name="canal" id="canal-aprobar" class="form-select form-select-sm mb-2" required>
                    <option value="presencial">En persona</option>
                    <option value="telefono">Por teléfono</option>
                </select>
            <?php endif; ?>
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-lg" aria-hidden="true"></i> Aprobar</button>
        </form>
        <button type="button" class="btn btn-outline-danger flex-grow-1 align-self-end" data-bs-toggle="collapse"
                data-bs-target="#rechazo-<?= $id ?>" aria-expanded="false" aria-controls="rechazo-<?= $id ?>">
            <i class="bi bi-x-lg" aria-hidden="true"></i> Rechazar
        </button>
    </div>

    <form method="post" action="<?= e(url("/ordenes/$id/respuesta")) ?>" id="rechazo-<?= $id ?>" class="collapse mt-3 needs-validation rechazo" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="decision" value="rechazar">
        <fieldset>
            <legend class="fs-6 fw-semibold">¿Por qué se rechaza?</legend>
            <?php foreach ($motivos as $n => $m): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="motivo" id="motivo-<?= $n ?>" value="<?= e($m) ?>" required>
                    <label class="form-check-label" for="motivo-<?= $n ?>"><?= e($m) ?></label>
                </div>
            <?php endforeach; ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="motivo" id="motivo-otro" value="otro" required>
                <label class="form-check-label" for="motivo-otro">Otro</label>
            </div>
            <label class="visually-hidden" for="motivo_otro">Otro motivo</label>
            <input type="text" name="motivo_otro" id="motivo_otro" class="form-control form-control-sm mt-1" maxlength="255" placeholder="Escribe el motivo">
        </fieldset>
        <?php if ($esAsesor): ?>
            <label class="form-label mt-2 mb-1" for="canal-rechazo">Medio</label>
            <select name="canal" id="canal-rechazo" class="form-select form-select-sm" required>
                <option value="presencial">En persona</option>
                <option value="telefono">Por teléfono</option>
            </select>
        <?php endif; ?>
        <p class="small text-secondary mt-2 mb-2">Solo se cobrará el diagnóstico (<?= pesos(App\Core\Config::get('taller.tarifa_diagnostico')) ?> + IVA) y el vehículo se entrega sin reparar.</p>
        <button type="submit" class="btn btn-danger">Confirmar rechazo</button>
    </form>
</div>

<?php
/**
 * Editor de cotización. JS solo ayuda (agregar filas, totales en vivo);
 * el servidor recalcula todo y toma el precio de cada repuesto del catálogo.
 * @var array $orden @var array|null $diagnostico @var array $items @var array $catalogo @var float $iva
 */
$id = (int) $orden['id'];
if (!$items) {
    $items = [['tipo' => 'mano_obra', 'repuesto_id' => '', 'descripcion' => '', 'cantidad' => '1', 'precio_unitario' => '']];
}

/** Una fila del editor (se usa también como plantilla para JS con $n = '__N__'). */
$fila = function ($n, array $i) use ($catalogo): string {
    ob_start(); ?>
    <tr class="item-fila" data-fila>
        <td data-label="Tipo">
            <label class="visually-hidden" for="tipo-<?= $n ?>">Tipo</label>
            <select id="tipo-<?= $n ?>" name="items[<?= $n ?>][tipo]" class="form-select form-select-sm" data-tipo>
                <option value="mano_obra" <?= ($i['tipo'] ?? '') === 'mano_obra' ? 'selected' : '' ?>>Mano de obra</option>
                <option value="repuesto" <?= ($i['tipo'] ?? '') === 'repuesto' ? 'selected' : '' ?>>Repuesto</option>
            </select>
        </td>
        <td data-label="Concepto" class="concepto">
            <label class="visually-hidden" for="rep-<?= $n ?>">Repuesto</label>
            <select id="rep-<?= $n ?>" name="items[<?= $n ?>][repuesto_id]" class="form-select form-select-sm" data-repuesto>
                <option value="">Elige un repuesto…</option>
                <?php foreach ($catalogo as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" data-precio-catalogo="<?= (float) $r['precio_venta'] ?>" data-stock="<?= (int) $r['stock'] ?>"
                        <?= (string) ($i['repuesto_id'] ?? '') === (string) $r['id'] ? 'selected' : '' ?>>
                        <?= e($r['nombre']) ?> (<?= e($r['codigo']) ?>) · stock <?= (int) $r['stock'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label class="visually-hidden" for="desc-<?= $n ?>">Descripción del trabajo</label>
            <input type="text" id="desc-<?= $n ?>" name="items[<?= $n ?>][descripcion]" class="form-control form-control-sm" data-descripcion
                   value="<?= e(($i['tipo'] ?? '') === 'mano_obra' ? ($i['descripcion'] ?? '') : '') ?>" maxlength="200" placeholder="Ej.: Cambio de pastillas y purga">
            <small class="text-danger d-block" data-aviso-stock hidden></small>
        </td>
        <td data-label="Cant.">
            <label class="visually-hidden" for="cant-<?= $n ?>">Cantidad</label>
            <input type="number" id="cant-<?= $n ?>" name="items[<?= $n ?>][cantidad]" class="form-control form-control-sm text-end" data-cantidad
                   value="<?= e((string) (float) ($i['cantidad'] ?? 1)) ?>" min="0.5" max="100" step="0.5" required>
        </td>
        <td data-label="Valor unit.">
            <label class="visually-hidden" for="precio-<?= $n ?>">Valor unitario</label>
            <input type="number" id="precio-<?= $n ?>" name="items[<?= $n ?>][precio_unitario]" class="form-control form-control-sm text-end" data-precio
                   value="<?= e(($i['precio_unitario'] ?? '') !== '' ? (string) (float) $i['precio_unitario'] : '') ?>" min="0" step="1">
        </td>
        <td data-label="Subtotal" class="text-end text-nowrap" data-subtotal>$0</td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" data-quitar aria-label="Quitar ítem"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </td>
    </tr>
    <?php return (string) ob_get_clean();
};
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/ordenes')) ?>">Órdenes</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url("/ordenes/$id")) ?>"><?= e($orden['codigo']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Cotización</li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap">Cotización <?= placa($orden['placa']) ?>
            <span class="fs-5 fw-normal"><?= e($orden['marca'] . ' ' . $orden['modelo']) ?> · <?= e($orden['cliente']) ?></span></h1>
    </div>
</header>

<?php if ($diagnostico): ?>
    <section class="panel mb-3" aria-labelledby="t-diag">
        <h2 id="t-diag" class="panel-title">Diagnóstico de <?= e($diagnostico['tecnico']) ?></h2>
        <p class="texto-largo mb-0"><?= e($diagnostico['descripcion']) ?></p>
    </section>
<?php endif; ?>

<form method="post" action="<?= e(url("/ordenes/$id/cotizacion")) ?>" class="panel" id="cotizacion" data-iva="<?= (float) $iva ?>">
    <?= csrf_field() ?>
    <div class="table-responsive">
        <table class="table table-ordenes table-editor align-middle mb-0">
            <thead>
            <tr>
                <th scope="col" style="width: 11rem">Tipo</th>
                <th scope="col">Concepto</th>
                <th scope="col" style="width: 6.5rem">Cant.</th>
                <th scope="col" style="width: 9rem">Valor unit.</th>
                <th scope="col" style="width: 8rem" class="text-end">Subtotal</th>
                <th scope="col" style="width: 3rem"><span class="visually-hidden">Quitar</span></th>
            </tr>
            </thead>
            <tbody data-filas>
            <?php foreach (array_values($items) as $n => $i): ?>
                <?= $fila($n, $i) ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <template id="plantilla-fila"><?= $fila('__N__', ['tipo' => 'repuesto', 'cantidad' => '1']) ?></template>

    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mt-3">
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-agregar="repuesto"><i class="bi bi-plus-lg" aria-hidden="true"></i> Repuesto</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-agregar="mano_obra"><i class="bi bi-plus-lg" aria-hidden="true"></i> Mano de obra</button>
        </div>
        <dl class="totales" aria-live="polite">
            <dt>Subtotal</dt><dd data-total-subtotal>$0</dd>
            <dt>IVA <?= (int) round($iva * 100) ?> %</dt><dd data-total-iva>$0</dd>
            <dt class="total">Total</dt><dd class="total" data-total>$0</dd>
        </dl>
    </div>
    <p class="form-text">El precio de los repuestos se toma del catálogo y queda fijo en la cotización. La mano de obra se cotiza por horas.</p>

    <div class="form-actions">
        <a href="<?= e(url("/ordenes/$id")) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Enviando…">
            <?= $orden['estado'] === App\Models\OrdenTrabajo::DIAGNOSTICO ? 'Enviar cotización al cliente' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
<script src="<?= e(asset('js/cotizacion.js')) ?>" defer></script>

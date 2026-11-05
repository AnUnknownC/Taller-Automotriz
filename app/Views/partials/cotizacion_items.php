<?php
/** Detalle de cotización reutilizable (ficha de la orden y, en 3B, liquidación). @var array $items @var array $totales @var bool $verStock */
$verStock = $verStock ?? false;
?>
<div class="table-responsive">
    <table class="table table-cotizacion align-middle mb-0">
        <thead>
        <tr>
            <th scope="col">Concepto</th>
            <th scope="col" class="text-end">Cant.</th>
            <th scope="col" class="text-end text-nowrap d-none d-sm-table-cell">Valor unit.</th>
            <th scope="col" class="text-end">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i):
            $sinStock = $verStock && $i['tipo'] === 'repuesto' && isset($i['repuesto_stock']) && (int) $i['repuesto_stock'] < (float) $i['cantidad']; ?>
            <tr>
                <td>
                    <span class="tag"><?= $i['tipo'] === 'repuesto' ? 'Repuesto' : 'Mano de obra' ?></span>
                    <?= e($i['descripcion']) ?>
                    <span class="d-block d-sm-none small text-secondary"><?= e(rtrim(rtrim(number_format((float) $i['cantidad'], 2, ',', '.'), '0'), ',')) ?> × <?= pesos($i['precio_unitario']) ?></span>
                    <?php if ($sinStock): ?>
                        <span class="d-block small text-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            Stock actual: <?= (int) $i['repuesto_stock'] ?>. Hay que reabastecer antes de reparar.</span>
                    <?php endif; ?>
                </td>
                <td class="text-end"><?= e(rtrim(rtrim(number_format((float) $i['cantidad'], 2, ',', '.'), '0'), ',')) ?></td>
                <td class="text-end text-nowrap d-none d-sm-table-cell"><?= pesos($i['precio_unitario']) ?></td>
                <td class="text-end text-nowrap"><?= pesos((float) $i['cantidad'] * (float) $i['precio_unitario']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr><td class="d-none d-sm-table-cell"></td><th scope="row" colspan="2" class="text-end fw-normal">Mano de obra</th><td class="text-end"><?= pesos($totales['mano_obra']) ?></td></tr>
        <tr><td class="d-none d-sm-table-cell"></td><th scope="row" colspan="2" class="text-end fw-normal">Repuestos</th><td class="text-end"><?= pesos($totales['repuestos']) ?></td></tr>
        <tr><td class="d-none d-sm-table-cell"></td><th scope="row" colspan="2" class="text-end fw-normal">IVA <?= (int) round($totales['tasa'] * 100) ?> %</th><td class="text-end"><?= pesos($totales['iva']) ?></td></tr>
        <tr class="total"><td class="d-none d-sm-table-cell"></td><th scope="row" colspan="2" class="text-end">Total</th><td class="text-end"><?= pesos($totales['total']) ?></td></tr>
        </tfoot>
    </table>
</div>

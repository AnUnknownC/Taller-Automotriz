<?php
/** @var array $f @var array $filas */
echo App\Core\View::partial('reporte_encabezado', ['f' => $f, 'activo' => 'repuestos', 'ruta' => '/reportes/repuestos']);
$verCosto = App\Core\Auth::hasRole('administrador');
?>
<div class="row g-3">
    <section class="col-lg-5" aria-labelledby="t-top">
        <div class="panel h-100">
            <h2 id="t-top" class="panel-title">Unidades retiradas de bodega</h2>
            <?php if (!$filas): ?>
                <div class="empty-state"><i class="bi bi-box-seam" aria-hidden="true"></i><p>No salieron repuestos en el periodo.</p></div>
            <?php else: ?>
                <?= App\Core\View::partial('barras_h', ['filas' => array_map(fn ($r) => [
                    'label' => $r['nombre'], 'valor' => (int) $r['unidades'], 'texto' => (string) (int) $r['unidades'],
                    'detalle' => (int) $r['ordenes'] . ' orden(es)'], $filas)]) ?>
            <?php endif; ?>
        </div>
    </section>
    <section class="col-lg-7" aria-labelledby="t-det">
        <div class="panel h-100">
            <h2 id="t-det" class="panel-title">Detalle</h2>
            <?php if ($filas): ?>
                <div class="table-responsive"><table class="table table-ordenes align-middle mb-0">
                    <thead><tr><th scope="col">Repuesto</th><th scope="col" class="text-end">Unid.</th><th scope="col" class="text-end">Órdenes</th>
                        <th scope="col" class="text-end">Vendido</th><?php if ($verCosto): ?><th scope="col" class="text-end">Margen</th><?php endif; ?><th scope="col" class="text-end">Stock</th></tr></thead>
                    <tbody><?php foreach ($filas as $r):
                        $bajo = (int) $r['stock'] <= (int) $r['stock_minimo'];
                        $margen = (float) $r['vendido'] > 0 ? ((float) $r['vendido'] - (int) $r['unidades'] * (float) $r['costo']) / (float) $r['vendido'] * 100 : 0; ?>
                        <tr>
                            <td data-label="Repuesto"><a href="<?= e(url('/repuestos/' . (int) $r['id'])) ?>"><?= e($r['nombre']) ?></a> <code class="codigo d-none d-xl-inline"><?= e($r['codigo']) ?></code></td>
                            <td data-label="Unid." class="text-end fw-semibold"><?= (int) $r['unidades'] ?></td>
                            <td data-label="Órdenes" class="text-end"><?= (int) $r['ordenes'] ?></td>
                            <td data-label="Vendido" class="text-end text-nowrap"><?= pesos($r['vendido']) ?></td>
                            <?php if ($verCosto): ?><td data-label="Margen" class="text-end" title="Sobre el último costo de compra"><?= number_format($margen, 0) ?> %</td><?php endif; ?>
                            <td data-label="Stock" class="text-end text-nowrap"><span class="<?= $bajo ? 'stock-bajo' : '' ?>"><?= $bajo ? '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i> ' : '' ?><?= (int) $r['stock'] ?></span>
                                <span class="small text-secondary">/ <?= (int) $r['stock_minimo'] ?></span></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
                <p class="form-text mb-0 mt-2">"Vendido" usa el precio congelado en cada cotización. <?= $verCosto ? 'El margen se calcula sobre el último costo de compra.' : '' ?></p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php
/** @var array $f @var array $resumen @var array $serie @var string $agrupacion @var array $metodos @var array $ultimas */
echo App\Core\View::partial('reporte_encabezado', ['f' => $f, 'activo' => 'ingresos', 'ruta' => '/reportes/ingresos']);
$fmtPeriodo = [
    'dia'    => fn ($p) => fecha($p, 'd/m'),
    'semana' => fn ($p) => 'Sem. ' . fecha($p, 'd/m'),
    'mes'    => fn ($p) => ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun', '07' => 'Jul',
                            '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'][substr($p, 5, 2)] . ' ' . substr($p, 0, 4),
][$agrupacion];
?>
<div class="alert alert-info d-flex gap-2 py-2" role="note"><i class="bi bi-info-circle" aria-hidden="true"></i>
    <div>Valores <strong>simulados</strong>: provienen de las liquidaciones registradas, no de pagos reales.</div></div>

<section class="kpis" aria-label="Resumen de ingresos">
    <div class="kpi"><span class="kpi-label">Total liquidado</span><span class="kpi-value"><?= pesos($resumen['total']) ?></span>
        <span class="small text-secondary"><?= (int) $resumen['liquidaciones'] ?> entrega(s)</span></div>
    <div class="kpi"><span class="kpi-label">Ticket promedio</span><span class="kpi-value"><?= $resumen['ticket'] ? pesos($resumen['ticket']) : '—' ?></span></div>
    <div class="kpi"><span class="kpi-label">Mano de obra · repuestos</span>
        <span class="kpi-value fs-4"><?= pesos($resumen['mano_obra']) ?></span><span class="small text-secondary">Repuestos: <?= pesos($resumen['repuestos']) ?> · IVA: <?= pesos($resumen['iva']) ?></span></div>
</section>

<div class="row g-3">
    <section class="col-lg-7" aria-labelledby="t-serie">
        <div class="panel h-100">
            <h2 id="t-serie" class="panel-title">Ingresos por <?= ['dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$agrupacion] ?></h2>
            <?php if (!$serie): ?>
                <div class="empty-state"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>Sin liquidaciones en el periodo.</p></div>
            <?php else: ?>
                <?= App\Core\View::partial('barras_h', ['filas' => array_map(fn ($s) => [
                    'label' => $fmtPeriodo($s['periodo']), 'valor' => (float) $s['total'], 'texto' => pesos($s['total']),
                    'detalle' => (int) $s['liquidaciones'] . ' entrega(s)'], $serie)]) ?>
            <?php endif; ?>
        </div>
    </section>
    <section class="col-lg-5" aria-labelledby="t-metodo">
        <div class="panel h-100">
            <h2 id="t-metodo" class="panel-title">Por método de pago (simulado)</h2>
            <?php if (!$metodos): ?>
                <div class="empty-state"><p>Sin datos.</p></div>
            <?php else: ?>
                <table class="table table-sm mb-0 tabla-datos">
                    <thead><tr><th scope="col">Método</th><th scope="col" class="text-end">Entregas</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">%</th></tr></thead>
                    <tbody><?php foreach ($metodos as $m): ?>
                        <tr><td><?= e(App\Models\Liquidacion::METODOS[$m['metodo']]) ?></td><td class="text-end"><?= (int) $m['liquidaciones'] ?></td>
                            <td class="text-end text-nowrap"><?= pesos($m['total']) ?></td>
                            <td class="text-end"><?= number_format((float) $m['total'] / max(1, (float) $resumen['total']) * 100, 0) ?> %</td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
    <section class="col-12" aria-labelledby="t-ult">
        <div class="panel">
            <h2 id="t-ult" class="panel-title">Últimas entregas del periodo <span class="small text-secondary fw-normal">(el CSV trae todas)</span></h2>
            <?php if (!$ultimas): ?>
                <div class="empty-state"><p>Sin liquidaciones en el periodo.</p></div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-ordenes align-middle mb-0">
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Orden</th><th scope="col">Cliente</th><th scope="col">Tipo</th><th scope="col" class="text-end">Total</th></tr></thead>
                    <tbody><?php foreach ($ultimas as $l): ?>
                        <tr><td data-label="Fecha" class="text-nowrap"><?= e(fecha($l['created_at'])) ?></td>
                            <td data-label="Orden"><a href="<?= e(url('/ordenes/' . (int) $l['orden_id'])) ?>"><?= e($l['codigo']) ?></a> <?= placa($l['placa']) ?></td>
                            <td data-label="Cliente"><?= e($l['cliente']) ?></td>
                            <td data-label="Tipo"><?= $l['solo_diagnostico'] ? '<span class="estado estado-rechazada">Solo diagnóstico</span>' : 'Reparación' ?></td>
                            <td data-label="Total" class="text-end text-nowrap fw-semibold"><?= pesos($l['total']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php
/** @var array $f @var App\Core\ListParams $p @var array $kpis @var array $tecnicos @var array $res @var array $listaTecnicos */
use App\Models\OrdenTrabajo as OT;

ob_start(); ?>
<div class="col-6 col-md-3">
    <label for="estado" class="form-label">Estado</label>
    <select id="estado" name="estado" class="form-select">
        <option value="">Todos</option>
        <option value="abiertas" <?= $f['estado'] === 'abiertas' ? 'selected' : '' ?>>Abiertas</option>
        <?php foreach (OT::ESTADOS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['estado'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-3">
    <label for="tecnico" class="form-label">Técnico</label>
    <select id="tecnico" name="tecnico" class="form-select">
        <option value="">Todos</option>
        <option value="-1" <?= $f['tecnico'] === -1 ? 'selected' : '' ?>>Sin asignar</option>
        <?php foreach ($listaTecnicos as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $f['tecnico'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?>
    </select>
</div>
<?php $extra = ob_get_clean();
echo App\Core\View::partial('reporte_encabezado', ['f' => $f, 'activo' => 'ordenes', 'ruta' => '/reportes/ordenes', 'extra' => $extra]);
$dias = fn ($horas) => $horas === null ? '—' : number_format((float) $horas / 24, 1, ',', '.') . ' días';
?>

<section class="kpis" aria-label="Indicadores del periodo">
    <div class="kpi"><span class="kpi-label">Órdenes ingresadas</span><span class="kpi-value"><?= (int) $kpis['total'] ?></span></div>
    <div class="kpi"><span class="kpi-label">Entregadas · abiertas</span><span class="kpi-value"><?= (int) $kpis['entregadas'] ?> <small class="fs-6 text-secondary fw-normal">· <?= (int) $kpis['abiertas'] ?></small></span></div>
    <div class="kpi"><span class="kpi-label">Tiempo promedio en taller</span><span class="kpi-value"><?= e($dias($kpis['horas_ciclo'])) ?></span>
        <span class="small text-secondary">Ingreso → entrega, órdenes reparadas</span></div>
    <div class="kpi"><span class="kpi-label">Tasa de aprobación</span>
        <span class="kpi-value"><?= $kpis['tasa_aprobacion'] === null ? '—' : number_format($kpis['tasa_aprobacion'], 0) . ' %' ?></span>
        <span class="small text-secondary"><?= (int) $kpis['aprobadas'] ?> aprobadas de <?= (int) $kpis['aprobadas'] + (int) $kpis['rechazadas'] ?> respondidas</span></div>
</section>

<div class="row g-3">
    <section class="col-12" aria-labelledby="t-tec">
        <div class="panel">
            <h2 id="t-tec" class="panel-title">Órdenes por técnico</h2>
            <?php if (!$tecnicos): ?>
                <div class="empty-state"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>Sin órdenes en el periodo.</p></div>
            <?php else: ?>
                <div class="row g-4 align-items-start">
                <div class="col-md-6">
                <?= App\Core\View::partial('barras_h', ['filas' => array_map(fn ($t) => [
                    'label' => $t['tecnico'], 'valor' => (int) $t['total'], 'texto' => (string) (int) $t['total'],
                    'detalle' => (int) $t['entregadas'] . ' entregadas · promedio ' . $dias($t['horas'])], $tecnicos)]) ?>
                </div>
                <div class="col-md-6">
                <table class="table table-sm mb-0 tabla-datos">
                    <caption class="visually-hidden">Datos de la gráfica de órdenes por técnico</caption>
                    <thead><tr><th scope="col">Técnico</th><th scope="col" class="text-end">Órdenes</th><th scope="col" class="text-end">Entregadas</th><th scope="col" class="text-end">Promedio <span class="visually-hidden">en taller de órdenes reparadas</span></th></tr></thead>
                    <tbody><?php foreach ($tecnicos as $t): ?>
                        <tr><td><?= e($t['tecnico']) ?></td><td class="text-end"><?= (int) $t['total'] ?></td><td class="text-end"><?= (int) $t['entregadas'] ?></td><td class="text-end text-nowrap"><?= e($dias($t['horas'])) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="col-12" aria-labelledby="t-lista">
        <div class="panel">
            <h2 id="t-lista" class="panel-title">Detalle (<?= (int) $res['total'] ?>)</h2>
            <?php if (!$res['rows']): ?>
                <div class="empty-state"><i class="bi bi-clipboard" aria-hidden="true"></i><p>No hay órdenes con estos filtros.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-ordenes align-middle mb-0">
                        <thead><tr>
                            <th scope="col" <?= aria_sort('codigo', $p) ?>><?= sort_link('Orden', 'codigo', $p, '/reportes/ordenes') ?></th>
                            <th scope="col">Vehículo</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Técnico</th>
                            <th scope="col">Estado</th>
                            <th scope="col" <?= aria_sort('ingreso', $p) ?>><?= sort_link('Ingreso', 'ingreso', $p, '/reportes/ordenes') ?></th>
                            <th scope="col" class="text-end" <?= aria_sort('dias', $p) ?>><?= sort_link('Días', 'dias', $p, '/reportes/ordenes') ?></th>
                            <th scope="col" class="text-end" <?= aria_sort('total', $p) ?>><?= sort_link('Total', 'total', $p, '/reportes/ordenes') ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($res['rows'] as $o): ?>
                            <tr>
                                <td data-label="Orden" class="text-nowrap"><a class="fw-semibold" href="<?= e(url('/ordenes/' . (int) $o['id'])) ?>"><?= e($o['codigo']) ?></a></td>
                                <td data-label="Vehículo"><?= placa($o['placa']) ?> <span class="text-secondary"><?= e($o['marca'] . ' ' . $o['modelo']) ?></span></td>
                                <td data-label="Cliente"><?= e($o['cliente']) ?></td>
                                <td data-label="Técnico"><?= $o['tecnico'] ? e($o['tecnico']) : '<span class="text-secondary">Sin asignar</span>' ?></td>
                                <td data-label="Estado"><?= estado_badge($o['estado']) ?></td>
                                <td data-label="Ingreso" class="text-nowrap"><?= e(fecha($o['fecha_ingreso'], 'd/m/Y')) ?></td>
                                <td data-label="Días" class="text-end"><?= number_format((float) $o['horas'] / 24, 1, ',', '.') ?></td>
                                <td data-label="Total" class="text-end text-nowrap"><?= $o['total'] !== null ? pesos($o['total']) : '<span class="text-secondary">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= App\Core\View::partial('pagination', ['pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/reportes/ordenes', 'query' => $p->query(['pagina' => null])]) ?>
            <?php endif; ?>
        </div>
    </section>
</div>

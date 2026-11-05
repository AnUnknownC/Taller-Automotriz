<?php /** @var array $repuesto @var array $kardex @var bool $cuadra @var array $pendientes */
$id = (int) $repuesto['id'];
$activo = (int) $repuesto['activo'] === 1;
$esAdmin = App\Core\Auth::hasRole('administrador');
$bajo = (int) $repuesto['stock'] <= (int) $repuesto['stock_minimo'];
$margen = (float) $repuesto['precio_venta'] > 0 ? ((float) $repuesto['precio_venta'] - (float) $repuesto['costo']) / (float) $repuesto['precio_venta'] * 100 : 0;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/repuestos')) ?>">Inventario</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($repuesto['codigo']) ?></li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap"><?= e($repuesto['nombre']) ?> <?= activo_badge($repuesto['activo']) ?></h1>
        <p class="text-secondary mb-0"><code class="codigo"><?= e($repuesto['codigo']) ?></code><?= $repuesto['marca'] ? ' · ' . e($repuesto['marca']) : '' ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($activo && App\Core\Auth::hasRole('administrador', 'asesor')): ?>
            <a href="<?= e(url('/inventario/entrada', ['repuesto' => $id])) ?>" class="btn btn-primary"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Entrada</a>
        <?php endif; ?>
        <?php if ($esAdmin): ?>
            <a href="<?= e(url("/repuestos/$id/ajuste")) ?>" class="btn btn-outline-secondary"><i class="bi bi-sliders" aria-hidden="true"></i> Ajuste</a>
            <a href="<?= e(url("/repuestos/$id/editar")) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
            <form method="post" action="<?= e(url("/repuestos/$id/estado")) ?>" data-confirm="<?= e($activo ? '¿Desactivar este repuesto? Ya no se podrá cotizar.' : '¿Activar este repuesto?') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $activo ? 'Desactivar' : 'Activar' ?></button>
            </form>
        <?php endif; ?>
    </div>
</header>

<section class="kpis" aria-label="Resumen del repuesto">
    <div class="kpi<?= $bajo ? ' kpi-alerta' : '' ?>">
        <span class="kpi-label">Stock actual<?= $bajo ? ' · en mínimo' : '' ?></span>
        <span class="kpi-value"><?= (int) $repuesto['stock'] ?> <small class="fs-6 text-secondary fw-normal">/ mín. <?= (int) $repuesto['stock_minimo'] ?></small></span>
    </div>
    <div class="kpi"><span class="kpi-label">Precio de venta</span><span class="kpi-value fs-3"><?= pesos($repuesto['precio_venta']) ?></span></div>
    <?php if (App\Core\Auth::hasRole('administrador', 'asesor')): ?>
        <div class="kpi"><span class="kpi-label">Último costo · margen</span><span class="kpi-value fs-3"><?= pesos($repuesto['costo']) ?> <small class="fs-6 text-secondary fw-normal"><?= number_format($margen, 0) ?> %</small></span></div>
    <?php endif; ?>
</section>

<?php if ($pendientes): ?>
    <div class="alert alert-info d-flex gap-2" role="status"><i class="bi bi-clipboard-check" aria-hidden="true"></i>
        <div>Cotizado en órdenes que aún no inician reparación:
            <?php foreach ($pendientes as $n => $o): ?><?= $n ? ', ' : '' ?><a href="<?= e(url('/ordenes/' . (int) $o['id'])) ?>"><?= e($o['codigo']) ?></a><?php endforeach; ?>.</div></div>
<?php endif; ?>

<section class="panel" aria-labelledby="t-kardex">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
        <h2 id="t-kardex" class="panel-title mb-0">Kardex (últimos 100 movimientos)</h2>
        <span class="small <?= $cuadra ? 'text-success' : 'text-danger fw-bold' ?>">
            <i class="bi bi-<?= $cuadra ? 'check-circle' : 'x-octagon' ?>" aria-hidden="true"></i>
            <?= $cuadra ? 'La suma de movimientos coincide con el stock' : 'La suma de movimientos NO coincide con el stock' ?>
        </span>
    </div>
    <?php if (!$kardex): ?>
        <div class="empty-state"><i class="bi bi-journal" aria-hidden="true"></i><p>Sin movimientos todavía. Registra una entrada de mercancía.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead><tr>
                    <th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col" class="text-end">Cantidad</th>
                    <th scope="col" class="text-end">Saldo</th><th scope="col">Referencia</th><th scope="col">Usuario</th>
                </tr></thead>
                <tbody>
                <?php foreach ($kardex as $m): ?>
                    <tr>
                        <td data-label="Fecha" class="text-nowrap"><?= e(fecha($m['created_at'])) ?></td>
                        <td data-label="Tipo"><span class="mov mov-<?= e($m['tipo']) ?>"><?= e(App\Models\MovimientoInventario::TIPOS[$m['tipo']]) ?></span></td>
                        <td data-label="Cantidad" class="text-end fw-bold <?= $m['efecto'] < 0 ? 'text-danger' : 'text-success' ?>"><?= $m['efecto'] > 0 ? '+' : '' ?><?= (int) $m['efecto'] ?></td>
                        <td data-label="Saldo" class="text-end"><?= (int) $m['saldo'] ?></td>
                        <td data-label="Referencia">
                            <?php if ($m['orden_codigo']): ?><a href="<?= e(url('/ordenes/' . (int) $m['orden_id'])) ?>"><?= e($m['orden_codigo']) ?></a><?php endif; ?>
                            <?php if ($m['proveedor']): ?><?= e($m['proveedor']) ?><?= $m['costo_unitario'] ? ' · ' . pesos($m['costo_unitario']) . ' c/u' : '' ?><?php endif; ?>
                            <?php if ($m['motivo'] && !$m['orden_codigo']): ?><span class="d-block small text-secondary"><?= e($m['motivo']) ?></span><?php endif; ?>
                        </td>
                        <td data-label="Usuario"><?= e($m['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

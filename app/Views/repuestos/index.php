<?php /** @var array $res @var App\Core\ListParams $p @var int $enMinimo */
$esAdmin = App\Core\Auth::hasRole('administrador');
$puedeEntrada = App\Core\Auth::hasRole('administrador', 'asesor');
$soloBajo = $p->filters['bajo'] === '1';
?>
<header class="page-head">
    <div>
        <h1>Inventario de repuestos</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> repuesto(s) con estos filtros.</p>
    </div>
    <div class="shortcuts">
        <?php if ($puedeEntrada): ?>
            <a href="<?= e(url('/inventario/entrada')) ?>" class="btn btn-primary"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Entrada de mercancía</a>
        <?php endif; ?>
        <?php if ($esAdmin): ?>
            <a href="<?= e(url('/repuestos/crear')) ?>" class="btn btn-outline-secondary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo repuesto</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($enMinimo > 0 && !$soloBajo): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-center flex-wrap" role="status">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <div class="flex-grow-1"><?= (int) $enMinimo ?> repuesto(s) están en su stock mínimo o por debajo.</div>
        <a href="<?= e(url('/repuestos', ['bajo' => 1])) ?>" class="btn btn-sm btn-dark">Ver cuáles</a>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(url('/repuestos')) ?>" class="panel filtros" role="search" aria-label="Filtrar repuestos">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>"><input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="q" class="form-label">Buscar código, nombre o marca</label>
            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($p->q) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <?php foreach (['activos' => 'Activos', 'inactivos' => 'Inactivos', 'todos' => 'Todos'] as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $p->filters['estado'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="bajo" name="bajo" value="1" <?= $soloBajo ? 'checked' : '' ?>>
                <label class="form-check-label" for="bajo">Solo en stock mínimo</label>
            </div>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
            <a href="<?= e(url('/repuestos')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de repuestos">
    <?php if (!$res['rows']): ?>
        <div class="empty-state"><i class="bi bi-box-seam" aria-hidden="true"></i><p>No hay repuestos con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead><tr>
                    <th scope="col" <?= aria_sort('codigo', $p) ?>><?= sort_link('Código', 'codigo', $p, '/repuestos') ?></th>
                    <th scope="col" <?= aria_sort('nombre', $p) ?>><?= sort_link('Repuesto', 'nombre', $p, '/repuestos') ?></th>
                    <th scope="col" class="text-end" <?= aria_sort('precio', $p) ?>><?= sort_link('Precio', 'precio', $p, '/repuestos') ?></th>
                    <th scope="col" class="text-end" <?= aria_sort('stock', $p) ?>><?= sort_link('Stock', 'stock', $p, '/repuestos') ?></th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($res['rows'] as $r): $bajo = (int) $r['stock'] <= (int) $r['stock_minimo']; ?>
                    <tr>
                        <td data-label="Código"><code class="codigo"><?= e($r['codigo']) ?></code></td>
                        <td data-label="Repuesto"><a href="<?= e(url('/repuestos/' . (int) $r['id'])) ?>" class="fw-semibold"><?= e($r['nombre']) ?></a>
                            <?php if ($r['marca']): ?><span class="text-secondary small"> · <?= e($r['marca']) ?></span><?php endif; ?></td>
                        <td data-label="Precio" class="text-end text-nowrap"><?= pesos($r['precio_venta']) ?></td>
                        <td data-label="Stock" class="text-end text-nowrap">
                            <span class="<?= $bajo ? 'stock-bajo' : 'fw-semibold' ?>"><?= $bajo ? '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i> ' : '' ?><?= (int) $r['stock'] ?></span>
                            <span class="small text-secondary">/ mín. <?= (int) $r['stock_minimo'] ?></span>
                            <?php if ($bajo): ?><span class="visually-hidden">(en stock mínimo)</span><?php endif; ?>
                        </td>
                        <td data-label="Estado"><?= activo_badge($r['activo']) ?></td>
                        <td data-label="Acciones" class="acciones">
                            <a href="<?= e(url('/repuestos/' . (int) $r['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text" aria-hidden="true"></i> Kardex</a>
                            <?php if ($puedeEntrada && (int) $r['activo'] === 1): ?>
                                <a href="<?= e(url('/inventario/entrada', ['repuesto' => (int) $r['id']])) ?>" class="btn btn-sm btn-outline-secondary" aria-label="Registrar entrada de <?= e($r['nombre']) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Entrada</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', ['pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/repuestos', 'query' => $p->query(['pagina' => null])]) ?>
    <?php endif; ?>
</section>

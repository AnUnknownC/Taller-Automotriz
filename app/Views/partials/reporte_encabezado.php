<?php
/**
 * Encabezado común de reportes: pestañas + filtro de periodo + exportar CSV.
 * @var array $f @var string $activo @var string $ruta @var string $extra (HTML de filtros adicionales)
 */
$pestanas = ['ordenes' => ['Órdenes', 'clipboard2-data']];
if (App\Core\Auth::hasRole('administrador')) {
    $pestanas['ingresos'] = ['Ingresos simulados', 'cash-stack'];
}
$pestanas['repuestos'] = ['Repuestos más usados', 'box-seam'];
$query = array_filter(['desde' => $f['desde'], 'hasta' => $f['hasta'], 'estado' => $f['estado'] ?? '', 'tecnico' => !empty($f['tecnico']) ? $f['tecnico'] : '']);
$rapidos = ['7 días' => 6, '30 días' => 29, '90 días' => 89, 'Este año' => (int) date('z')];
?>
<header class="page-head">
    <div>
        <h1>Reportes</h1>
        <p class="text-secondary mb-0">Del <?= e(fecha($f['desde'], 'd/m/Y')) ?> al <?= e(fecha($f['hasta'], 'd/m/Y')) ?>. Todas las cifras salen de la base de datos.</p>
    </div>
    <a href="<?= e(url($ruta, $query + ['formato' => 'csv'])) ?>" class="btn btn-primary"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Exportar CSV</a>
</header>

<nav class="nav nav-tabs reporte-tabs mb-3" aria-label="Tipo de reporte">
    <?php foreach ($pestanas as $k => [$label, $icono]): ?>
        <a class="nav-link<?= $k === $activo ? ' active' : '' ?>" href="<?= e(url('/reportes/' . $k, ['desde' => $f['desde'], 'hasta' => $f['hasta']])) ?>"
           <?= $k === $activo ? 'aria-current="page"' : '' ?>><i class="bi bi-<?= $icono ?>" aria-hidden="true"></i> <?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url($ruta)) ?>" class="panel filtros" aria-label="Filtros del reporte">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?= e($f['desde']) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-6 col-md-2">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($f['hasta']) ?>" required>
        </div>
        <?= $extra ?? '' ?>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Aplicar</button>
            <a href="<?= e(url($ruta)) ?>" class="btn btn-outline-secondary" aria-label="Restablecer filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
    <div class="rapidos mt-2" aria-label="Periodos rápidos">
        <?php foreach ($rapidos as $label => $n): ?>
            <a href="<?= e(url($ruta, ['desde' => date('Y-m-d', strtotime("-$n days")), 'hasta' => date('Y-m-d')] + array_diff_key($query, ['desde' => 1, 'hasta' => 1]))) ?>"
               class="estado-chip"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</form>

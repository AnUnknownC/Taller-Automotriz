<?php
/** Paginación reutilizable. @var int $pagina @var int $paginas @var string $ruta @var array $query */
if ($paginas <= 1) {
    return;
}
$enlace = fn (int $p): string => url($ruta, array_filter($query, fn ($v) => $v !== '') + ['pagina' => $p]);
$desde = max(1, $pagina - 2);
$hasta = min($paginas, $pagina + 2);
?>
<nav class="mt-3" aria-label="Paginación">
    <ul class="pagination pagination-sm flex-wrap mb-0">
        <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($enlace(max(1, $pagina - 1))) ?>" aria-label="Página anterior">&laquo;</a>
        </li>
        <?php for ($p = $desde; $p <= $hasta; $p++): ?>
            <li class="page-item<?= $p === $pagina ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($enlace($p)) ?>" <?= $p === $pagina ? 'aria-current="page"' : '' ?>><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?= $pagina >= $paginas ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($enlace(min($paginas, $pagina + 1))) ?>" aria-label="Página siguiente">&raquo;</a>
        </li>
    </ul>
</nav>

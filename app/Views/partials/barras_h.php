<?php
/**
 * Gráfica de barras horizontales de UNA serie (un solo color: la identidad la da la etiqueta).
 * Accesible: cada barra tiene su valor en texto y la tabla de datos va al lado o debajo.
 * @var array $filas [['label' => ..., 'valor' => float, 'texto' => '...', 'detalle' => '...']]
 */
$max = max(1, ...array_map(fn ($f) => (float) $f['valor'], $filas ?: [['valor' => 1]]));
?>
<ul class="barras barras-h">
    <?php foreach ($filas as $fila): $pct = (float) $fila['valor'] / $max * 100; ?>
        <li title="<?= e($fila['label'] . ': ' . $fila['texto'] . (!empty($fila['detalle']) ? ' · ' . $fila['detalle'] : '')) ?>">
            <span class="barras-label"><?= e($fila['label']) ?></span>
            <span class="barras-track" aria-hidden="true"><span class="barras-fill barras-serie" style="width: <?= round($pct, 1) ?>%"></span></span>
            <span class="barras-num"><?= e($fila['texto']) ?></span>
        </li>
    <?php endforeach; ?>
</ul>

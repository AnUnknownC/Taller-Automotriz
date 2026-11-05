<?php
declare(strict_types=1);

namespace App\Core;

/**
 * RF-09: exportación CSV lista para Excel en español.
 *  - BOM UTF-8 (tildes correctas) y separador ";" (el que usa Excel con configuración regional de Colombia).
 *  - Protección contra inyección de fórmulas (CSV injection): una celda que empieza por = + - @ o tabulador
 *    se antepone con ' para que Excel la trate como texto y no ejecute nada.
 */
final class Csv
{
    public static function descargar(string $nombre, array $encabezados, iterable $filas): void
    {
        $nombre = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre) . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, $encabezados, ';');
        foreach ($filas as $fila) {
            fputcsv($salida, array_map([self::class, 'celda'], $fila), ';');
        }
        fclose($salida);
        exit;
    }

    /** @param mixed $valor */
    public static function celda($valor): string
    {
        $texto = (string) ($valor ?? '');
        if ($texto !== '' && !is_numeric($texto) && in_array($texto[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $texto;
        }
        return $texto;
    }
}

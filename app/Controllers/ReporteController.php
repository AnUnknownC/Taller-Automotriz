<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\ListParams;
use App\Core\Request;
use App\Core\Session;
use App\Models\Liquidacion;
use App\Models\OrdenTrabajo;
use App\Models\Reporte;
use App\Models\Usuario;

/**
 * RF-09: reportes filtrables con exportación CSV.
 *  - Órdenes y repuestos: administrador y asesor (operativos).
 *  - Ingresos simulados: solo administrador.
 * Cada exportación queda en la auditoría.
 */
final class ReporteController extends Controller
{
    private const MAX_DIAS = 366;

    private Reporte $reportes;

    public function __construct()
    {
        $this->reportes = new Reporte();
    }

    /** GET /reportes/ordenes */
    public function ordenes(): void
    {
        $f = $this->filtros(true);
        if (Request::query('formato') === 'csv') {
            $this->auditarExportacion('ordenes', $f);
            $filas = array_map(fn ($o) => [
                $o['codigo'], $o['placa'], $o['marca'] . ' ' . $o['modelo'], $o['cliente'], $o['asesor'], $o['tecnico'] ?? 'Sin asignar',
                OrdenTrabajo::ESTADOS[$o['estado']], $o['fecha_ingreso'], $o['fecha_entrega'] ?? '',
                number_format((float) $o['horas'] / 24, 1, ',', ''), $o['total'] !== null ? round((float) $o['total']) : '',
                $o['metodo_simulado'] ? Liquidacion::METODOS[$o['metodo_simulado']] : '',
            ], $this->reportes->ordenes($f, null));
            Csv::descargar('reporte_ordenes', ['Orden', 'Placa', 'Vehículo', 'Cliente', 'Asesor', 'Técnico', 'Estado',
                'Ingreso', 'Entrega', 'Días en taller', 'Total liquidado (simulado)', 'Método (simulado)'], $filas);
        }

        $p = ListParams::fromRequest(Reporte::columnasOrdenOrdenes(), 'ingreso', 'desc', 15);
        $p->filters = $this->filtrosUrl($f);
        $this->view('reportes/ordenes', [
            'title'    => 'Reporte de órdenes',
            'f'        => $f,
            'p'        => $p,
            'kpis'     => $this->reportes->kpisOrdenes($f),
            'tecnicos' => $this->reportes->porTecnico($f),
            'res'      => $this->reportes->ordenes($f, $p),
            'listaTecnicos' => (new Usuario())->tecnicosActivos(),
        ]);
    }

    /** GET /reportes/ingresos (administrador) */
    public function ingresos(): void
    {
        $f = $this->filtros(false);
        if (Request::query('formato') === 'csv') {
            $this->auditarExportacion('ingresos', $f);
            $filas = array_map(fn ($l) => [
                $l['created_at'], $l['codigo'], $l['placa'], $l['cliente'], $l['solo_diagnostico'] ? 'Solo diagnóstico' : 'Reparación',
                round((float) $l['subtotal_mano_obra']), round((float) $l['subtotal_repuestos']), round((float) $l['impuestos']),
                round((float) $l['total']), Liquidacion::METODOS[$l['metodo_simulado']], $l['registrada_por'],
            ], $this->reportes->liquidaciones($f));
            Csv::descargar('reporte_ingresos_simulados', ['Fecha', 'Orden', 'Placa', 'Cliente', 'Tipo', 'Mano de obra', 'Repuestos',
                'IVA', 'Total', 'Método (simulado)', 'Registrada por'], $filas);
        }

        $dias = (int) ((strtotime($f['hasta']) - strtotime($f['desde'])) / 86400) + 1;
        $agrupacion = $dias <= 31 ? 'dia' : ($dias <= 120 ? 'semana' : 'mes');
        $this->view('reportes/ingresos', [
            'title'      => 'Ingresos simulados',
            'f'          => $f,
            'resumen'    => $this->reportes->resumenIngresos($f),
            'serie'      => $this->reportes->ingresosPorPeriodo($f, $agrupacion),
            'agrupacion' => $agrupacion,
            'metodos'    => $this->reportes->ingresosPorMetodo($f),
            'ultimas'    => array_slice($this->reportes->liquidaciones($f), 0, 10),
        ]);
    }

    /** GET /reportes/repuestos */
    public function repuestos(): void
    {
        $f = $this->filtros(false);
        $filas = $this->reportes->repuestosUsados($f);
        if (Request::query('formato') === 'csv') {
            $this->auditarExportacion('repuestos', $f);
            Csv::descargar('reporte_repuestos_usados', ['Código', 'Repuesto', 'Unidades usadas', 'Órdenes', 'Vendido (cotizado)',
                'Stock actual', 'Stock mínimo'], array_map(fn ($r) => [
                    $r['codigo'], $r['nombre'], (int) $r['unidades'], (int) $r['ordenes'], round((float) $r['vendido']),
                    (int) $r['stock'], (int) $r['stock_minimo'],
                ], $filas));
        }
        $this->view('reportes/repuestos', ['title' => 'Repuestos más usados', 'f' => $f, 'filas' => $filas]);
    }

    // ------------------------------------------------------------------

    /**
     * Filtros comunes. Periodo por defecto: últimos 30 días. Fechas inválidas, invertidas o
     * un rango de más de un año vuelven al valor por defecto con un aviso.
     */
    private function filtros(bool $deOrdenes): array
    {
        $hoy = date('Y-m-d');
        $desde = Request::query('desde', date('Y-m-d', strtotime('-29 days')));
        $hasta = Request::query('hasta', $hoy);
        $ok = $this->fecha($desde) && $this->fecha($hasta) && $desde <= $hasta
            && (strtotime($hasta) - strtotime($desde)) / 86400 < self::MAX_DIAS;
        if (!$ok) {
            Session::flash('warning', 'El periodo no era válido (fechas inexistentes, invertidas o de más de un año). Se muestran los últimos 30 días.');
            $desde = date('Y-m-d', strtotime('-29 days'));
            $hasta = $hoy;
        }
        $f = ['desde' => $desde, 'hasta' => $hasta, 'estado' => '', 'tecnico' => 0];
        if ($deOrdenes) {
            $estado = Request::query('estado');
            $f['estado'] = in_array($estado, array_merge(['abiertas'], array_keys(OrdenTrabajo::ESTADOS)), true) ? $estado : '';
            $tecnico = (int) Request::query('tecnico', '0');
            $f['tecnico'] = $tecnico === -1 || in_array($tecnico, array_map('intval', array_column((new Usuario())->tecnicosActivos(), 'id')), true) ? $tecnico : 0;
        }
        return $f;
    }

    private function filtrosUrl(array $f): array
    {
        return ['desde' => $f['desde'], 'hasta' => $f['hasta'], 'estado' => $f['estado'], 'tecnico' => $f['tecnico'] ? (string) $f['tecnico'] : ''];
    }

    private function fecha(string $valor): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $valor);
        return $d !== false && $d->format('Y-m-d') === $valor;
    }

    private function auditarExportacion(string $reporte, array $f): void
    {
        $this->audit('exportar_csv', 'reporte', null, null, ['reporte' => $reporte] + $f);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Auditoria;
use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use App\Models\Reporte;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;

/** RF-07: cada rol ve un tablero distinto, con datos reales de MySQL. */
final class DashboardController extends Controller
{
    /** GET /dashboard */
    public function index(): void
    {
        $rol = Auth::role();
        $data = ['title' => 'Inicio', 'usuario' => Auth::user()];

        switch ($rol) {
            case 'administrador':
                $ordenes = new OrdenTrabajo();
                $data += [
                    'usuariosActivos' => (new Usuario())->contarActivos(),
                    'usuariosPorRol'  => (new Usuario())->contarActivosPorRol(),
                    'ordenesAbiertas' => $ordenes->contarAbiertas(),
                    'porEstado'       => $ordenes->contarPorEstado(),
                    'bajoStock'       => (new Repuesto())->contarBajoStock(),
                    'ingresos30'      => (new Reporte())->resumenIngresos(['desde' => date('Y-m-d', strtotime('-29 days')), 'hasta' => date('Y-m-d')]),
                    'auditoria'       => (new Auditoria())->recientes(6),
                ];
                break;

            case 'asesor':
                $ordenes = new OrdenTrabajo();
                $porEstado = $ordenes->contarPorEstado();
                $data += [
                    'sinTecnico'   => $ordenes->contarSinTecnico(),
                    'pendientes'   => (new Cotizacion())->contarPendientes(),
                    'listas'       => $porEstado[OrdenTrabajo::LISTA],
                    'porEstado'    => $porEstado,
                    'recientes'    => $ordenes->recientes(8),
                    'repuestosBajos' => (new Repuesto())->bajoStock(5),
                ];
                break;

            case 'tecnico':
                $data += ['asignadas' => (new OrdenTrabajo())->activasDeTecnico(Auth::id())];
                break;

            case 'cliente':
                $data += [
                    'vehiculos'  => (new Vehiculo())->deUsuarioCliente(Auth::id()),
                    'ordenes'    => (new OrdenTrabajo())->deUsuarioCliente(Auth::id()),
                    'pendientes' => (new Cotizacion())->pendientesDeUsuarioCliente(Auth::id()),
                ];
                break;
        }

        $this->view('dashboard/' . $rol, $data);
    }
}

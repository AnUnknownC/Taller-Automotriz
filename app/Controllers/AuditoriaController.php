<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Auditoria;

/** RF-11: consulta del historial de auditoría (solo administrador, ver config/routes.php). */
final class AuditoriaController extends Controller
{
    private const POR_PAGINA = 15;

    /** GET /auditoria?accion=&q=&orden=desc&pagina=1 */
    public function index(): void
    {
        $modelo = new Auditoria();
        $acciones = $modelo->accionesDistintas();

        $accion = Request::query('accion');
        $accion = in_array($accion, $acciones, true) ? $accion : '';
        $q = mb_substr(Request::query('q'), 0, 100);
        $orden = Request::query('orden') === 'asc' ? 'asc' : 'desc';
        $pagina = max(1, (int) Request::query('pagina', '1'));

        $resultado = $modelo->buscar($accion, $q, $orden, $pagina, self::POR_PAGINA);
        $paginas = max(1, (int) ceil($resultado['total'] / self::POR_PAGINA));

        $this->view('auditoria/index', [
            'title'    => 'Auditoría',
            'filas'    => $resultado['filas'],
            'total'    => $resultado['total'],
            'acciones' => $acciones,
            'filtros'  => compact('accion', 'q', 'orden'),
            'pagina'   => min($pagina, $paginas),
            'paginas'  => $paginas,
        ]);
    }
}

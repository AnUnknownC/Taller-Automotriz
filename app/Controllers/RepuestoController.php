<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\FormState;
use App\Core\ListParams;
use App\Core\Request;
use App\Core\Validator;
use App\Models\MovimientoInventario;
use App\Models\Proveedor;
use App\Models\Repuesto;
use App\Services\InventarioService;
use App\Services\ReglaNegocioException;

/**
 * Inventario de repuestos.
 *  - Consultar: administrador, asesor y técnico.
 *  - Catálogo (crear/editar/desactivar) y ajustes: administrador.
 *  - Entradas de mercancía: administrador y asesor.
 * Reglas: código único · precio de venta ≥ costo · el stock solo cambia con movimientos ·
 * no se desactiva un repuesto que una orden pendiente va a consumir.
 */
final class RepuestoController extends Controller
{
    private Repuesto $repuestos;

    public function __construct()
    {
        $this->repuestos = new Repuesto();
    }

    /** GET /repuestos */
    public function index(): void
    {
        $p = ListParams::fromRequest(Repuesto::columnasOrden(), 'nombre');
        $estado = $p->filter('estado', ['activos', 'inactivos', 'todos'], 'activos');
        $bajo = $p->filter('bajo', ['1']) === '1';
        $this->view('repuestos/index', [
            'title' => 'Inventario', 'res' => $this->repuestos->listar($p, $estado, $bajo), 'p' => $p,
            'enMinimo' => $this->repuestos->contarBajoStock(),
        ]);
    }

    /** GET /repuestos/{id} — ficha con kardex */
    public function show(int $id): void
    {
        $repuesto = $this->orFail($this->repuestos->find($id));
        $mov = new MovimientoInventario();
        $this->view('repuestos/show', [
            'title'      => $repuesto['nombre'],
            'repuesto'   => $repuesto,
            'kardex'     => $mov->kardex($id, (int) $repuesto['stock']),
            'cuadra'     => $mov->saldoCalculado($id) === (int) $repuesto['stock'],
            'pendientes' => $this->repuestos->ordenesPendientes($id),
        ]);
    }

    /** GET /repuestos/crear (administrador) */
    public function create(): void
    {
        $this->view('repuestos/form', ['title' => 'Nuevo repuesto', 'repuesto' => null]);
    }

    /** POST /repuestos */
    public function store(): void
    {
        $d = $this->datos();
        $v = $this->validar($d, null);
        if ($v->fails()) {
            $this->fail('/repuestos/crear', $v->errors(), $d);
        }
        $id = $this->repuestos->crear($d);
        $this->audit('crear', 'repuesto', $id, null, $d);
        $this->success("Repuesto {$d['codigo']} creado con stock 0. Registra una entrada de mercancía para cargar existencias.", "/repuestos/$id");
    }

    /** GET /repuestos/{id}/editar */
    public function edit(int $id): void
    {
        $this->view('repuestos/form', ['title' => 'Editar repuesto', 'repuesto' => $this->orFail($this->repuestos->find($id))]);
    }

    /** POST /repuestos/{id} */
    public function update(int $id): void
    {
        $actual = $this->orFail($this->repuestos->find($id));
        $d = $this->datos();
        $v = $this->validar($d, $id);
        if ($v->fails()) {
            $this->fail("/repuestos/$id/editar", $v->errors(), $d);
        }
        $this->repuestos->actualizar($id, $d);
        $this->audit('editar', 'repuesto', $id, array_intersect_key($actual, $d), $d);
        $this->success('Cambios guardados. Las cotizaciones ya enviadas conservan su precio.', "/repuestos/$id");
    }

    /** POST /repuestos/{id}/estado */
    public function toggle(int $id): void
    {
        $repuesto = $this->orFail($this->repuestos->find($id));
        $activar = (int) $repuesto['activo'] === 0;
        if (!$activar && ($pend = $this->repuestos->ordenesPendientes($id))) {
            $this->error('No se puede desactivar: está cotizado en ' . implode(', ', array_column($pend, 'codigo')) . ', que aún no inicia reparación.', "/repuestos/$id");
        }
        $this->repuestos->cambiarEstado($id, $activar);
        $this->audit($activar ? 'activar' : 'desactivar', 'repuesto', $id, ['activo' => !$activar], ['activo' => $activar]);
        $this->success('Repuesto ' . ($activar ? 'activado.' : 'desactivado: ya no se podrá cotizar.'), "/repuestos/$id");
    }

    /** GET /inventario/entrada?repuesto=ID (administrador, asesor) */
    public function formEntrada(): void
    {
        $this->view('repuestos/entrada', [
            'title'       => 'Entrada de mercancía',
            'repuestos'   => $this->repuestos->activosParaSelect(),
            'proveedores' => (new Proveedor())->activosParaSelect(),
            'repuestoId'  => (int) Request::query('repuesto', '0'),
        ]);
    }

    /** POST /inventario/entrada */
    public function entrada(): void
    {
        $d = $this->only(['repuesto_id', 'proveedor_id', 'cantidad', 'costo_unitario', 'nota']);
        $d['costo_unitario'] = str_replace(['.', ' ', '$'], '', $d['costo_unitario']);
        $v = (new Validator($d))
            ->rule('repuesto_id', 'required|integer', 'El repuesto')
            ->rule('proveedor_id', 'required|integer', 'El proveedor')
            ->rule('cantidad', 'required|between:1,9999', 'La cantidad')
            ->rule('costo_unitario', 'numeric', 'El costo')
            ->rule('nota', 'max:200', 'La nota');
        $v->check($d['costo_unitario'] === '' || ((float) $d['costo_unitario'] > 0 && (float) $d['costo_unitario'] <= 50000000),
            'costo_unitario', 'El costo debe ser mayor a 0.');
        if ($v->fails()) {
            $this->fail('/inventario/entrada?repuesto=' . (int) $d['repuesto_id'], $v->errors(), $d);
        }
        try {
            $rep = (new InventarioService())->registrarEntrada((int) $d['repuesto_id'], (int) $d['proveedor_id'], (int) $d['cantidad'],
                $d['costo_unitario'] !== '' ? (float) $d['costo_unitario'] : null, $d['nota'] !== '' ? $d['nota'] : null, Auth::id());
        } catch (ReglaNegocioException $e) {
            FormState::flash($d, []);
            $this->error($e->getMessage(), '/inventario/entrada');
        }
        $this->success("Entrada registrada: +{$d['cantidad']} {$rep['nombre']}. Stock actual: {$rep['stock']}.", '/repuestos/' . (int) $rep['id']);
    }

    /** GET /repuestos/{id}/ajuste (administrador) */
    public function formAjuste(int $id): void
    {
        $this->view('repuestos/ajuste', ['title' => 'Ajuste de inventario', 'repuesto' => $this->orFail($this->repuestos->find($id))]);
    }

    /** POST /repuestos/{id}/ajuste */
    public function ajuste(int $id): void
    {
        $this->orFail($this->repuestos->find($id));
        $d = $this->only(['sentido', 'cantidad', 'motivo']);
        $v = (new Validator($d))
            ->rule('sentido', 'required|in:sumar,restar', 'El tipo de ajuste')
            ->rule('cantidad', 'required|between:1,9999', 'La cantidad')
            ->rule('motivo', 'required|min:5|max:200', 'El motivo');
        if ($v->fails()) {
            $this->fail("/repuestos/$id/ajuste", $v->errors(), $d);
        }
        $cantidad = (int) $d['cantidad'] * ($d['sentido'] === 'restar' ? -1 : 1);
        try {
            $rep = (new InventarioService())->ajustar($id, $cantidad, $d['motivo'], Auth::id());
        } catch (ReglaNegocioException $e) {
            FormState::flash($d, []);
            $this->error($e->getMessage(), "/repuestos/$id/ajuste");
        }
        $this->success('Ajuste registrado (' . ($cantidad > 0 ? '+' : '') . "$cantidad). Stock actual: {$rep['stock']}.", "/repuestos/$id");
    }

    private function datos(): array
    {
        $d = $this->only(['codigo', 'nombre', 'marca', 'costo', 'precio_venta', 'stock_minimo']);
        $d['codigo'] = strtoupper(str_replace(' ', '-', $d['codigo']));
        foreach (['costo', 'precio_venta'] as $k) {
            $d[$k] = str_replace(['.', ' ', '$'], '', $d[$k]);
        }
        return $d;
    }

    private function validar(array $d, ?int $id): Validator
    {
        $v = (new Validator($d))
            ->rule('codigo', 'required|max:30', 'El código')
            ->rule('nombre', 'required|min:3|max:120', 'El nombre')
            ->rule('marca', 'max:50', 'La marca')
            ->rule('costo', 'required|numeric', 'El costo')
            ->rule('precio_venta', 'required|numeric', 'El precio de venta')
            ->rule('stock_minimo', 'required|between:0,9999', 'El stock mínimo');
        $v->check((bool) preg_match(Repuesto::PATRON_CODIGO, $d['codigo']), 'codigo', 'Usa de 3 a 30 letras, números o guiones (ej. FRN-PAST-DEL).')
          ->check(!$this->repuestos->codigoEnUso($d['codigo'], $id), 'codigo', 'Ya existe un repuesto con ese código.');
        if (!$v->has('costo') && !$v->has('precio_venta')) {
            $v->check((float) $d['costo'] > 0, 'costo', 'El costo debe ser mayor a 0.')
              ->check((float) $d['precio_venta'] >= (float) $d['costo'], 'precio_venta', 'El precio de venta no puede ser menor al costo.');
        }
        return $v;
    }
}

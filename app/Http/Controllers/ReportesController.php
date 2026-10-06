<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    /**
     * Relación de gastos: requisiciones en ORDEN DE COMPRA (OC) y SURTIDAS (SU)
     * Filtros: IDDepartamento (opcional), Ejercicio (opcional)
     * Importe = Cantidad x PrecioUnitarioConIva del proveedor seleccionado
     */
    public function relacionGastos(Request $request)
    {
        try {
            $precio = "CASE
                WHEN d.Proveedor = d.IDproveedor1 THEN d.PrecioUnitarioConIva1
                WHEN d.Proveedor = d.IDproveedor2 THEN d.PrecioUnitarioConIva2
                WHEN d.Proveedor = d.IDproveedor3 THEN d.PrecioUnitarioConIva3
                ELSE 0 END";

            $query = DB::table('det_requisicion as d')
                ->join('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.Id', '=', 'd.IDRequisicion');
                })
                ->leftJoin('cat_departamentos as cd', 'cd.IDDepartamento', '=', 'r.IDDepartamento')
                ->whereIn('r.Status', ['OC', 'SU'])
                ->select(
                    'r.IDRequisicion',
                    'r.Ejercicio',
                    'r.Status',
                    'cd.Nombre_Departamento',
                    'r.Observaciones as Concepto',
                    'd.Descripcion',
                    'd.Cantidad',
                    DB::raw("($precio) as PrecioUnitarioConIva"),
                    DB::raw("(IFNULL(d.Cantidad,0) * IFNULL(($precio),0)) as Importe")
                )
                ->orderBy('r.Ejercicio')
                ->orderBy('r.IDRequisicion');

            if ($request->filled('IDDepartamento')) {
                $query->where('r.IDDepartamento', $request->IDDepartamento);
            }
            if ($request->filled('Ejercicio')) {
                $query->where('r.Ejercicio', $request->Ejercicio);
            }

            return ApiResponse::success($query->get(), 'Relación de gastos obtenida con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

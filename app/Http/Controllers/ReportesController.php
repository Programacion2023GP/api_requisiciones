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
            // Mismo cálculo que el PDF: (PrecioSinIva x Cantidad) + IVA - Retenciones, del proveedor seleccionado
            $precio = "CASE
                WHEN d.Proveedor = d.IDproveedor1 THEN d.PrecioUnitarioConIva1
                WHEN d.Proveedor = d.IDproveedor2 THEN d.PrecioUnitarioConIva2
                WHEN d.Proveedor = d.IDproveedor3 THEN d.PrecioUnitarioConIva3
                ELSE 0 END";
            $neto = "CASE
                WHEN d.Proveedor = d.IDproveedor1 THEN (IFNULL(d.PrecioUnitarioSinIva1,0) * IFNULL(d.Cantidad,0) * (1 + IFNULL(d.PorcentajeIVA1,0)/100) - IFNULL(d.Retenciones1,0))
                WHEN d.Proveedor = d.IDproveedor2 THEN (IFNULL(d.PrecioUnitarioSinIva2,0) * IFNULL(d.Cantidad,0) * (1 + IFNULL(d.PorcentajeIVA2,0)/100) - IFNULL(d.Retenciones2,0))
                WHEN d.Proveedor = d.IDproveedor3 THEN (IFNULL(d.PrecioUnitarioSinIva3,0) * IFNULL(d.Cantidad,0) * (1 + IFNULL(d.PorcentajeIVA3,0)/100) - IFNULL(d.Retenciones3,0))
                ELSE 0 END";

            DB::statement('SET SESSION group_concat_max_len = 100000');
            $query = DB::table('det_requisicion as d')
                ->join('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.Id', '=', 'd.IDRequisicion');
                })
                ->leftJoin('cat_departamentos as cd', 'cd.IDDepartamento', '=', 'r.IDDepartamento')
                ->whereIn('r.Status', ['OC', 'SU'])
                ->select(
                    'r.Id',
                    'r.IDRequisicion',
                    'r.Ejercicio',
                    'r.Status',
                    'cd.Nombre_Departamento',
                    'r.Observaciones as Concepto',
                    DB::raw("GROUP_CONCAT(CONCAT(d.Cantidad, ' - ', d.Descripcion) ORDER BY d.IDDetalle SEPARATOR ' | ') as Descripcion"),
                    DB::raw("ROUND(SUM($neto), 2) as Importe")
                )
                ->groupBy('r.Id', 'r.IDRequisicion', 'r.Ejercicio', 'r.Status', 'cd.Nombre_Departamento', 'r.Observaciones')
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

    /**
     * Datos para el PDF de una requisición (mismo formato que usa el módulo de requisiciones)
     */
    public function requisicionPdf(Request $request)
    {
        try {
            $pdfData = DB::table('requisiciones_view')
                ->where('Ejercicio', $request->Ejercicio)
                ->where('IDRequisicion', $request->IDRequisicion)
                ->first();
            $products = DB::table('products_details')
                ->where('Ejercicio', $request->Ejercicio)
                ->where('id', $request->Id)
                ->get();
            return ApiResponse::success(['pdfData' => $pdfData, 'products' => $products], 'PDF obtenido con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

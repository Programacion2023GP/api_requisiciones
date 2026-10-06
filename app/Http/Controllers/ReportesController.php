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
            // Basado en las mismas vistas que el PDF (requisiciones_view + products_details)
            // Total Neto = (PrecioSinIva x Cantidad) + IVA - Retenciones del proveedor seleccionado
            $neto = "CASE
                WHEN p.Proveedor IS NOT NULL AND p.Proveedor = p.Proveedor1 THEN (IFNULL(p.PrecioUnitarioSinIva1,0) * IFNULL(p.Cantidad,0) * (1 + IFNULL(p.PorcentajeIVA1,0)/100) - IFNULL(p.Retenciones1,0))
                WHEN p.Proveedor IS NOT NULL AND p.Proveedor = p.Proveedor2 THEN (IFNULL(p.PrecioUnitarioSinIva2,0) * IFNULL(p.Cantidad,0) * (1 + IFNULL(p.PorcentajeIVA2,0)/100) - IFNULL(p.Retenciones2,0))
                WHEN p.Proveedor IS NOT NULL AND p.Proveedor = p.Proveedor3 THEN (IFNULL(p.PrecioUnitarioSinIva3,0) * IFNULL(p.Cantidad,0) * (1 + IFNULL(p.PorcentajeIVA3,0)/100) - IFNULL(p.Retenciones3,0))
                ELSE 0 END";

            DB::statement('SET SESSION group_concat_max_len = 100000');
            $query = DB::table('requisiciones_view as r')
                ->join('products_details as p', function ($join) {
                    $join->on('p.id', '=', 'r.Id')
                        ->on('p.Ejercicio', '=', 'r.Ejercicio');
                })
                ->whereIn('r.Status', ['OC', 'SU'])
                ->select(
                    'r.Id',
                    'r.IDRequisicion',
                    'r.Ejercicio',
                    'r.Status',
                    'r.Nombre_Departamento',
                    'r.Observaciones as Concepto',
                    DB::raw("GROUP_CONCAT(CONCAT(p.Cantidad, ' - ', p.Descripcion) SEPARATOR ' | ') as Descripcion"),
                    DB::raw("ROUND(SUM($neto), 2) as Importe")
                )
                ->groupBy('r.Id', 'r.IDRequisicion', 'r.Ejercicio', 'r.Status', 'r.Nombre_Departamento', 'r.Observaciones')
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

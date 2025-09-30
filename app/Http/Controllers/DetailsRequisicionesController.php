<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Details;
use App\Models\Provedor;
use App\Models\Requisiciones;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetailsRequisicionesController extends Controller
{
   public function update(Request $request)
{
    try {
        // Procesar cada producto
        $i = 1;
        while ($request->has('IDDetalle' . $i)) {
            $IDDetalle = $request->input('IDDetalle' . $i);
            $detalle = Details::where('IDDetalle', $IDDetalle)->first();
            
            if ($detalle) {
                // Para CO (Cotización)
                if ($request->newStatus == "CO") {
                    // Los 3 proveedores son los mismos para todos los productos
                    $detalle->IDproveedor1 = $request->IDproveedor1;
                    $detalle->IDproveedor2 = $request->IDproveedor2;
                    $detalle->IDproveedor3 = $request->IDproveedor3;
                    
                    // Campos específicos por producto (3 campos por producto)
                    $baseField = ($i - 1) * 3 + 1;
                    
                    // Proveedor 1 de este producto (campo 1, 4, 7, etc.)
                    $detalle->PrecioUnitarioSinIva1 = $request->input('PrecioUnitarioSinIva' . $baseField, 0);
                    $detalle->PorcentajeIVA1 = $request->input('PorcentajeIVA' . $baseField, 0);
                    $detalle->ImporteIva1 = $request->input('ImporteIva' . $baseField, 0);
                    $detalle->PrecioUnitarioConIva1 = $request->input('PrecioUnitarioConIva' . $baseField, 0);
                    $detalle->Retenciones1 = $request->input('Retenciones' . $baseField, 0);
                    
                    // Proveedor 2 de este producto (campo 2, 5, 8, etc.)
                    $detalle->PrecioUnitarioSinIva2 = $request->input('PrecioUnitarioSinIva' . ($baseField + 1), 0);
                    $detalle->PorcentajeIVA2 = $request->input('PorcentajeIVA' . ($baseField + 1), 0);
                    $detalle->ImporteIva2 = $request->input('ImporteIva' . ($baseField + 1), 0);
                    $detalle->PrecioUnitarioConIva2 = $request->input('PrecioUnitarioConIva' . ($baseField + 1), 0);
                    $detalle->Retenciones2 = $request->input('Retenciones' . ($baseField + 1), 0);
                    
                    // Proveedor 3 de este producto (campo 3, 6, 9, etc.)
                    $detalle->PrecioUnitarioSinIva3 = $request->input('PrecioUnitarioSinIva' . ($baseField + 2), 0);
                    $detalle->PorcentajeIVA3 = $request->input('PorcentajeIVA' . ($baseField + 2), 0);
                    $detalle->ImporteIva3 = $request->input('ImporteIva' . ($baseField + 2), 0);
                    $detalle->PrecioUnitarioConIva3 = $request->input('PrecioUnitarioConIva' . ($baseField + 2), 0);
                    $detalle->Retenciones3 = $request->input('Retenciones' . ($baseField + 2), 0);
                }
                // Para OC (Orden de Compra)
                else {
                    $detalle->Proveedor = $request->Proveedor;
                }
                
                $detalle->save();
            }
            $i++;
        }

        // Resto del código igual...
        if ($request->newStatus == "OC") {
            Provedor::where('IDProveedor', $request->Proveedor)
                   ->update(['Comprado' => 1]);
        }

        if ($request->newStatus == "CO") {
            $requisicion = Requisiciones::where('IDRequisicion', $request->IDRequisicion)
                                      ->where('Ejercicio', $request->Ejercicio)
                                      ->first();
            if ($requisicion) {
                $requisicion->ObservacionesCot = $request->ObservacionesCot;
                $requisicion->update();
            }
        }

        return ApiResponse::success(null, $request->newStatus == "CO" 
            ? 'Se han insertado la cotización correctamente' 
            : 'La orden de compra se generó correctamente');
            
    } catch (Exception $e) {
        return ApiResponse::error($e->getMessage(), 500);
    }
}
    public function search(Request $request)
    {
        try {
            $details = DB::table('det_requisicion as d')
                ->leftJoin('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.IDRequisicion', '=', 'd.IDRequisicion');
                })
                ->where('d.IDDetalle', $request->IDDetalle)
                ->select(
                    'd.*',
                    'r.ObservacionesCot'
                )
                ->first();
            return ApiResponse::success($details, 'Detalles de requisiciones encontrados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

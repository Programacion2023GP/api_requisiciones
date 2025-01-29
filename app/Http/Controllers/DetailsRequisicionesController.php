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
            // Obtener los registros que coincidan con el IDRequisicion
            $detalles = Details::where('IDDetalle', $request->IDDetalle)->first();

            // Campos a actualizar si están presentes en la solicitud
            $camposPermitidos = [
                'IDproveedor1',
                'PrecioUnitarioSinIva1',
                'PorcentajeIVA1',
                'ImporteIva1',
                'PrecioUnitarioConIva1',
                'Retenciones1',
                'IDproveedor2',
                'PrecioUnitarioSinIva2',
                'PorcentajeIVA2',
                'ImporteIva2',
                'PrecioUnitarioConIva2',
                'Retenciones2',
                'IDproveedor3',
                'PrecioUnitarioSinIva3',
                'PorcentajeIVA3',
                'ImporteIva3',
                'PrecioUnitarioConIva3',
                'Retenciones3',
            ];
            if ($request->newStatus == "OC") {
                $camposPermitidos = [
                    'Proveedor',
                ];
            }
            // Iterar sobre cada registro y actualizar
     
                foreach ($camposPermitidos as $campo) {
                    if ($request->has($campo)) {
                        $detalles->{$campo} = $request->{$campo};
                    }
                
                $detalles->save();
            }
            if ($request->newStatus == "OC") {
                Provedor::where('Proveedor', $request->Proveedor)
                ->update(['Comprado' => 1]);
                            
            }
            // $requisicion = Requisiciones::where('IDRequisicion', $request->IDRequisicion)->where('Ejercicio', $request->Ejercicio)->first();
            // if ($request->newStatus == "CO") {
            //     $requisicion->ObservacionesCot = $request->ObservacionesCot;
            // }
            // $requisicion->Status = $request->newStatus;
            // $requisicion->save();
            return ApiResponse::success($detalles, $request->newStatus == "CO"? 'Se han insertado la cotización correctamente ':'la orden de compra se genero correctamente');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function search(Request $request){
        try {
           $details = DB::table('det_requisicion')->where('IDDetalle',$request->IDDetalle)->first();
            return ApiResponse::success($details, 'Detalles de requisiciones encontrados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

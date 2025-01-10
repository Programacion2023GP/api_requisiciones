<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Details;
use App\Models\Requisiciones;
use Exception;
use Illuminate\Http\Request;

class DetailsRequisicionesController extends Controller
{
    public function update(Request $request)
    {
        try {
            // Obtener los registros que coincidan con el IDRequisicion
            $detalles = Details::where('IDRequisicion', $request->IDRequisicion)->where('Ejercicio', $request->Ejercicio)->get();

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

            // Iterar sobre cada registro y actualizar
            foreach ($detalles as $detalle) {
                foreach ($camposPermitidos as $campo) {
                    if ($request->has($campo)) {
                        $detalle->{$campo} = $request->{$campo};
                    }
                }
                $detalle->save();
            }
            $requisicion = Requisiciones::where('IDRequisicion', $request->IDRequisicion)->where('Ejercicio', $request->Ejercicio)->first();
            $requisicion->ObservacionesCot = $request->ObservacionesCot;
            $requisicion->Status=$request->newStatus;
            $requisicion->save();
            return ApiResponse::success($detalles, 'Se han insertado los datos correctamente');
        } catch (Exception $e) {
            return ApiResponse::error("no se pudieron insertar los provedores en la requisición", 500);
        }
    }
}

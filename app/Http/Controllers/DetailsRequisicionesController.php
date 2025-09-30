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
        // Obtener todos los detalles de la requisición
        $detalles = [];
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^IDDetalle(\d+)$/', $key, $matches)) {
                $detalles[$matches[1]] = Details::firstOrNew([
                    'IDDetalle' => $value
                ]);
            }
        }

        // Leer todos los campos numéricos del request y agrupar por índice
        $campos = ['PrecioUnitarioSinIva', 'PorcentajeIVA', 'ImporteIva', 'PrecioUnitarioConIva', 'Retenciones'];
        $camposPorIndice = [];

        foreach ($request->all() as $key => $value) {
            foreach ($campos as $campo) {
                if (preg_match("/^{$campo}(\d+)$/", $key, $matches)) {
                    $indice = (int)$matches[1];
                    $camposPorIndice[$indice][$campo] = $value;
                }
            }
        }

        // Asignar valores a cada detalle
        $detalleCounter = 1;
        foreach ($detalles as $detalleIndex => $detalle) {
            for ($p = 1; $p <= 3; $p++) {
                $currentIndex = $detalleCounter;
                if (isset($camposPorIndice[$currentIndex])) {
                    foreach ($campos as $campo) {
                        $detalle->{$campo . $p} = $camposPorIndice[$currentIndex][$campo] ?? 0;
                    }
                }
                $detalleCounter++;
            }

            // Asignar los proveedores generales
            $detalle->IDproveedor1 = $request->IDproveedor1 ?? null;
            $detalle->IDproveedor2 = $request->IDproveedor2 ?? null;
            $detalle->IDproveedor3 = $request->IDproveedor3 ?? null;
            
            $detalle->save();
        }

        // Actualizar observaciones si CO
            $requisicion = Requisiciones::where('IDRequisicion', $request->IDRequisicion)
                                         ->where('Ejercicio', $request->Ejercicio)
                                         ->first();
            if ($requisicion) {
                $requisicion->ObservacionesCot = $request->ObservacionesCot;
                $requisicion->save();
            }
      

        return ApiResponse::success(null,
             'Se han insertado la cotización correctamente'
          );

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

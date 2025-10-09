<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\CatDetailsTipos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatDetailsTiposController extends Controller
{
 public function createorUpdate(Request $request)
{
    try {
        // Si el IDDetalleTipo existe (>0), buscamos el registro para actualizar
        $detailTipo = $request->IDDetalleTipo > 0
            ? CatDetailsTipos::find($request->IDDetalleTipo)
            : new CatDetailsTipos();

        if (!$detailTipo) {
            return ApiResponse::error('Detalle del tipo no encontrado', 404);
        }

        // Rellenamos solo los campos permitidos
        $detailTipo->fill($request->only([
            'IDTipo',
            'Nombre',
        ]));

        $detailTipo->save();

        $message = $request->IDDetalleTipo > 0
            ? 'Detalle del tipo actualizado'
            : 'Detalle del tipo creado';

        return ApiResponse::success($detailTipo, $message);
    } catch (Exception $e) {
        return ApiResponse::error($e->getMessage(), 400);
    }
}

    public function index()
    {
        try {
            $detailTipo = DB::table('cat_detailstipos')
                ->join('cat_tipos', 'cat_detailstipos.IDTipo', '=', 'cat_tipos.IDTipo')
                ->select('cat_detailstipos.*', 'cat_tipos.Descripcion as Tipo') // Puedes agregar más campos
                ->orderBy('cat_detailstipos.Nombre', 'asc')
                ->get();
            return ApiResponse::success($detailTipo, 'detalles de tipos recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los detalles de tipos', 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $detalle = CatDetailsTipos::find($request->id);

            if (!$detalle) {
                return ApiResponse::error("Detalle no encontrado", 404);
            }

            $detalle->delete();

            return ApiResponse::success(null, 'Detalle eliminado correctamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el detalle: ' . $e->getMessage(), 500);
        }
    }
}

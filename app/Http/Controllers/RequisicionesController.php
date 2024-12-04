<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\Requisiciones;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class   RequisicionesController extends Controller
{
    public function create(Request $request)
    {
        DB::beginTransaction(); // Inicia la transacción

        try {
            $requisicion = Requisiciones::find($request->Usuario);

            if (!$requisicion) {
                // Actualizar usuario   
                $requisicion = new Requisiciones();
                $folio = Requisiciones::where('Ejercicio', date('Y'))->max('IDRequisicion') ?? 0;
                $requisicion->IDRequisicion = $folio + 1;
            }
            $centro_costo = Departamento::where('IDDepartamento', $request->IDDepartamento)->first();

            $requisicion->Ejercicio = date('Y');
            //centro de costo
            $requisicion->FechaCaptura = date('Y-m-d H:i:s');
            $requisicion->FUM = date('Y-m-d H:i:s');

            $requisicion->UsuarioCaptura = Auth::user()->Usuario;
            $requisicion->UsuarioCa = Auth::user()->Usuario;
            $requisicion->AutEspecial =  $request->IDTipo ==5 || $request->IDTipo ==6 || $request->IDTipo ==7 ? 1:0;

            $requisicion->IDDepartamento = $request->IDDepartamento;
            $requisicion->Solicitante = $request->Solicitante;
            $requisicion->Observaciones = $request->Observaciones;
            $requisicion->IDTipo = $request->IDTipo;
            $requisicion->Status = "CA";
            $requisicion->centro_costo = $centro_costo->Centro_Costo;

            
            
            $requisicion->save();
            $datos = $request->all();

            foreach ($datos as $key => $valor) {
                if (strpos($key, 'Descripcion') === 0) {
                    Log::info("Clave de descripción encontrada: $key");
            
                    $index = substr($key, 11); // Obtener índice numérico
                    $cantidadKey = 'Cantidad' . $index;
            
                    if ($request->has($cantidadKey)) {
                        $cantidad = $request->input($cantidadKey);
                        Log::info("Requisicion aqui $requisicion");
            
                        $detailsRequisitionController = new DetailsRequisitionController();
                        $detailsRequisitionController->create($requisicion->IDRequisicion, $cantidad, $valor);
                    } else {
                        Log::warning("Clave de cantidad no encontrada: $cantidadKey");
                    }
                }
            }
            
    

            DB::commit(); // Confirma la transacción

            return ApiResponse::success($requisicion, 'Requisición creada con exito');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            // Log::error($e->getMessage()); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error("La requisición no se pudo crear", 500);
        }
    }
    public function index()
    {
        try {
            ini_set('memory_limit', '2048M');  // O cualquier valor mayor

    $requisiciones = DB::table('Requisiciones_View')->paginate(200); // 50 registros por página
            // return "ddd";
            return ApiResponse::success($requisiciones, 'Requisición creada con exito');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error("La requisición no se pudo crear", 500);
        }
    }
    
}

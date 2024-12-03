<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\Requisiciones;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequisicionesController extends Controller
{
    public function create(Request $request)
    {
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
            return ApiResponse::success($requisicion, 'Requisición creada con exito');
        } catch (Exception $e) {
            Log::error($e->getMessage()); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Requisitor;
use Exception;
use Illuminate\Support\Facades\Log;

class RequisitorController extends Controller
{
    public function create(Request $request)
    {
        try {
            $requisitor = Requisitor::find($request->Usuario);
            if ($requisitor) {
                // Actualizar usuario
                $requisitor->delete();
            } else {
                $requisitor = new Requisitor();
            }
            $requisitor->Usuario = $request->Usuario;
            $requisitor->Activo = 1;

            $requisitor->save();
            return ApiResponse::success($requisitor, 'Autorizador creado con exito');
        } catch (Exception $e) {
            Log::error($e); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error('El autorizador no se pudo crear', 500);
        }
    }
}

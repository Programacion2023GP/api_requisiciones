<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Director;
use App\Models\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;
class DirectorController extends Controller
{
    public function create(Request $request)
    {
        try {
            $director = Director::where($request->IDDepartamento)->where("Nombre_Director", $request->Nombre . ' ' . $request->Paterno . ' '  . $request->Materno)->first();
            if ($director) {
                // Actualizar usuario
                $director->delete();
            } else {
                $director = new Director();
            }
            $director->Nombre_Director = $request->Nombre . ' ' . $request->Paterno . ' '  . $request->Materno ;
            // $director->Activo = 1;

            $director->save();
            return ApiResponse::success($director, 'Autorizador creado con exito');
        } catch (Exception $e) {
            return ApiResponse::error('El autorizador no se pudo crear', 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Tipos;
use Exception;
use Illuminate\Http\Request;

class TiposController extends Controller
{
    public function index()
    {
        try {
            $users = Tipos::where('Activo', 1)
              
                ->orderBy('Descripcion', 'asc')
                ->get();

            return ApiResponse::success($users, 'Usuarios recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
}

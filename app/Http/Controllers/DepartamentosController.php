<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use Exception;
use Illuminate\Http\Request;

class DepartamentosController extends Controller
{
    public function index(){
        try {
            $users = Departamento::all();
            return ApiResponse::success($users, 'Usuarios recuperados con éxito');
        }
        catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Provedor;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProvedoresController extends Controller
{
    public function index()
    {
        try {
            $proveedores = Provedor::orderBy('IDProveedor', 'desc')->get();

            return ApiResponse::success($proveedores, 'provedores recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los provedores', 500);
        }
    }

  public function create(Request $request)
{
    try {
        $proveedor = $request->IDProveedor > 0
            ? Provedor::where('IDProveedor', $request->IDProveedor)->first()
            : new Provedor();

        if (!$proveedor && $request->IDProveedor > 0) {
            return ApiResponse::error('Proveedor no encontrado', 404); 
        }

        if ($request->IDProveedor == 0 && Provedor::where('EMail', $request->EMail)->exists()) {
            throw new Exception('Correo duplicado');
        }

        $proveedor->fill($request->only([
            'Nombre_RazonSocial',
            'ApPaterno',
            'ApMaterno',
            'RFC',
            'Telefono1',
            'Telefono2',
            'EMail'
        ]));

        $proveedor->FechaAlta = now();
        $proveedor->Usuario = Auth::user()->Usuario;
        $proveedor->FUM = now();
        $proveedor->UsuarioFUM = Auth::user()->Usuario;

        $proveedor->save();

        return ApiResponse::success($proveedor, 'Proveedor creado/actualizado con éxito');
    } catch (Exception $e) {
        if ($e->getMessage() == 'Correo duplicado') {
            return ApiResponse::error('Correo duplicado.', 400);
        }
        
        return ApiResponse::error('El provedor no se pudo crear. Intenta nuevamente.', 400);
    }
}

    
}

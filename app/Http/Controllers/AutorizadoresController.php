<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiResponse;
use App\Models\Autorizadores;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class AutorizadoresController extends Controller
{
    public function create(Request $request, int $id = null)
    {
        try {
            $autorizador = Autorizadores::find($request->Usuario);
            // return $autorizador;
            Log::error('Este es un mensaje de error', ['contexto' => $autorizador]);

            if ($autorizador) {
                // Actualizar usuario
                $autorizador->delete();
            } else {
                $autorizador = new Autorizadores();
            }
            $autorizador->Autorizador = $request->Usuario;
            $autorizador->Permiso_Autorizar = $request->Permiso_Autorizar;
            $autorizador->Permiso_Asignar = $request->Permiso_Asignar;
            $autorizador->Permiso_Cotizar = $request->Permiso_Cotizar;
            $autorizador->Permiso_Orden_Compra = $request->Permiso_Orden_Compra;
            $autorizador->Permiso_Surtir = $request->Permiso_Surtir;


            $autorizador->save();
            Log::info('Este es un mensaje de error', ['contexto' => $autorizador]);

            return ApiResponse::success($autorizador, 'Autorizador creado con exito');
        } catch (Exception $e) {
            Log::error($e); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error('El autorizador no se pudo crear', 500);
        }
    }
    public function indexAutorizadores(Request $request)
    {
        try {
            $users = User::where('Activo', 1)
                ->select(
                    'cat_usuarios.IDUsuario',
                    'cat_usuarios.Usuario',

                    'cat_usuarios.NombreCompleto',




                )
                ->leftJoin('autorizadores', 'autorizadores.Autorizador', '=', 'cat_usuarios.Usuario')
                ->where('autorizadores.Permiso_Cotizar', 1)
                ->orderBy('IDUsuario', 'desc')
                ->get();



            return ApiResponse::success($users, 'Usuarios recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
}

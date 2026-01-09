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
        

            // VALIDAR que tenemos el dato correcto
            $nombreAutorizador = $request->Autorizador ?? $request->Usuario ?? null;

            if (empty($nombreAutorizador)) {
                throw new \Exception('El nombre del autorizador es requerido');
            }

            // CORREGIDO: Buscar por el campo Autorizador, no por ID
            $autorizador = Autorizadores::where('Autorizador', $nombreAutorizador)->first();

            if ($autorizador) {
                // Actualizar el existente en lugar de eliminarlo
                $autorizador->update([
                    'Permiso_Autorizar' => $request->Permiso_Autorizar ?? $autorizador->Permiso_Autorizar,
                    'Permiso_Asignar' => $request->Permiso_Asignar ?? $autorizador->Permiso_Asignar,
                    'Permiso_Cotizar' => $request->Permiso_Cotizar ?? $autorizador->Permiso_Cotizar,
                    'Permiso_Orden_Compra' => $request->Permiso_Orden_Compra ?? $autorizador->Permiso_Orden_Compra,
                    'Permiso_Surtir' => $request->Permiso_Surtir ?? $autorizador->Permiso_Surtir,
                ]);
            } else {
                $autorizador = new Autorizadores();
                $autorizador->Autorizador = $nombreAutorizador;
                $autorizador->Permiso_Autorizar = $request->Permiso_Autorizar ?? 0;
                $autorizador->Permiso_Asignar = $request->Permiso_Asignar ?? 0;
                $autorizador->Permiso_Cotizar = $request->Permiso_Cotizar ?? 0;
                $autorizador->Permiso_Orden_Compra = $request->Permiso_Orden_Compra ?? 0;
                $autorizador->Permiso_Surtir = $request->Permiso_Surtir ?? 0;
            }


            $autorizador->save();

           

            return ApiResponse::success($autorizador, 'Autorizador procesado exitosamente');
        } catch (\Exception $e) {

            return ApiResponse::error('Error al procesar autorizador: ' . $e->getMessage(), 500);
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

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AutorizadoresController;
use App\Models\Autorizadores;
use App\Models\Director;
use ErrorException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->sql) {
                $users = User::where('Activo', 1)
                    ->select(
                        'cat_usuarios.*', // Selecciona todas las columnas de la tabla cat_usuarios
                        'autorizadores.Permiso_Autorizar',
                        'autorizadores.Permiso_Asignar',
                        'autorizadores.Permiso_Cotizar',
                        'autorizadores.Permiso_Orden_Compra',
                        'autorizadores.Permiso_Surtir'



                    )
                    ->leftJoin('autorizadores', 'autorizadores.Autorizador', '=', 'cat_usuarios.Usuario')
                    ->whereRaw($request->sql)
                    ->orderBy('IDUsuario', 'desc')
                    ->get();
            } else {
                $users = User::where('Activo', 1)
                    ->select(
                        'cat_usuarios.*', // Selecciona todas las columnas de la tabla cat_usuarios
                        'autorizadores.Permiso_Autorizar',
                        'autorizadores.Permiso_Asignar',
                        'autorizadores.Permiso_Cotizar',
                        'autorizadores.Permiso_Orden_Compra',
                        'autorizadores.Permiso_Surtir'



                    )
                    ->leftJoin('autorizadores', 'autorizadores.Autorizador', '=', 'cat_usuarios.Usuario')
                    ->orderBy('IDUsuario', 'desc')
                    ->get();
            }


            return ApiResponse::success($users, 'Usuarios recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
    public function ChangeStatus(int $id = null)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                throw new Exception('El usuario no existe');
            }

            $user->Activo = !$user->Activo;
            $user->save();

            return ApiResponse::success($user, 'El usuario ha sido eliminado correctamente');
        } catch (Exception $e) {
            // DB::rollBack(); // Deshace la transacción
            return ApiResponse::error('Error al eliminar el usuario', 500);
        }
    }
    public function createOrUpdate(Request $request, int $id = null)
    {
        DB::beginTransaction(); // Inicia la transacción

        try {

            $user = User::find($request->IDUsuario);
            if ($user) {
                // Actualizar usuario

                $user->update($request->all());
                $message = 'Usuario actualizado con éxito';
            } else {
                $exists = User::where('Usuario', $request->Usuario)->exists();

                if ($exists) {
                    throw new Exception('El usuario ya existe');
                }
                // Crear usuario
                $user = User::create($request->all());
                $message = 'Usuario creado con éxito';
            }

            // Lógica de roles
            if ($request->Rol === 'AUTORIZADOR') {
                (new AutorizadoresController())->create($request);
            } elseif ($request->Rol === 'REQUISITOR') {
                (new RequisitorController())->create($request);
            } elseif ($request->Rol === 'DIRECTOR') {
                $exists = Director::where('IdDepartamento', $request->IDDepartamento)->exists();

                if ($exists) {
                    throw new Exception('Ya existe el director');
                }
                (new DirectorController())->create($request);
            }

            DB::commit(); // Confirma la transacción
            return ApiResponse::success($user, $message);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            // Si el error es debido a la existencia de un director, muestra un mensaje más amigable
            if ($e->getMessage() == 'El usuario ya existe') {
                return ApiResponse::error('El usuario ya existe.', 500);
            }
            if ($e->getMessage() == 'Ya existe el director') {
                return ApiResponse::error('No se puede crear el director, ya existe uno registrado.', 500);
            }

            // Para otros errores, mostramos un mensaje genérico
            // Log::error($e->getMessage());
            return ApiResponse::error('El usuario no se pudo crear. Intenta nuevamente.', 500);
        }
    }
    public function login(Request $request)
    {
        try {
            // Obtener las credenciales del request
            $credentials = $request->only('Usuario', 'Password');

            if (!isset($credentials['Usuario']) || !isset($credentials['Password'])) {
                return ApiResponse::error('Credenciales incompletas', 400);
            }

            $user = User::where('Usuario', $credentials['Usuario'])->first();
            $permisos = Autorizadores::where('Autorizador', $credentials['Usuario'])->first();

            if ($user && $user->Password === $credentials['Password']) {
                $token = $user->createToken('YourAppName')->plainTextToken;
                $menuPermisos = DB::select("
                SELECT 
                cm.Id,
                cm.IdMenu,
                    cm.Menu,
                    cm.MenuPadre,
                    cm.Icon,
            
                CASE 
                    WHEN rmu.Permiso='S' THEN 1
                    ELSE 0
                END AS EstadoPermiso
            FROM 
                cat_menus cm
            LEFT JOIN 
                relmenuusuario rmu ON rmu.IdMenu = cm.IdMenu AND rmu.Usuario = ?
            WHERE cm.active =1
            ORDER BY 
                cm.IdMenu;
                    ", [$credentials['Usuario']]);
                return ApiResponse::success([
                    "permisos" => $permisos,
                    "menuPermiso" => $menuPermisos,
                    "token" => $token
                ], 'Bienvenido al sistema');
            }

            return ApiResponse::error('Credenciales incorrectas', 401);
        } catch (Exception $e) {
            return ApiResponse::error('El usuario no se pudo autenticar. Intenta nuevamente.', 401);
        }
    }







    public function logout(Request $request)
    {
        // Revocar el token del usuario autenticado
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'cerrando sesión']);
    }
}

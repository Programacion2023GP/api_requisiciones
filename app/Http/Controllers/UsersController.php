<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AutorizadoresController;
use App\Models\Autorizadores;
use App\Models\Departamento;
use App\Models\Director;
use App\Models\RelUsuarioDepartamento;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;

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
                        'autorizadores.Permiso_Surtir',
                        'cat_departamentos.Nombre_Departamento'



                    )
                    ->leftJoin('autorizadores', 'autorizadores.Autorizador', '=', 'cat_usuarios.Usuario')
                    ->leftJoin('cat_departamentos', 'cat_departamentos.IDDepartamento', '=', 'cat_usuarios.IDDepartamento')

                    ->whereRaw($request->sql)
                    ->orderBy('IDUsuario', 'desc')
                    ->get();
            } else {
                $users = User::where('Activo', 1)
                    ->select(
                        'cat_usuarios.*',
                        'autorizadores.Permiso_Autorizar',
                        'autorizadores.Permiso_Asignar',
                        'autorizadores.Permiso_Cotizar',
                        'autorizadores.Permiso_Orden_Compra',
                        'autorizadores.Permiso_Surtir',
                        'cat_departamentos.Nombre_Departamento',
                        DB::raw('(SELECT GROUP_CONCAT(IDDepartamento SEPARATOR ",")
                  FROM relusuariodepartamento
                  WHERE relusuariodepartamento.IDUsuario = cat_usuarios.IDUsuario) as IDDepartamentos')
                    )
                    ->leftJoin('autorizadores', 'autorizadores.Autorizador', '=', 'cat_usuarios.Usuario')
                    ->leftJoin('cat_departamentos', 'cat_departamentos.IDDepartamento', '=', 'cat_usuarios.IDDepartamento')
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
        config(['logging.channels.stack.channels' => ['single']]);

        // AUMENTAR TIME LIMIT
        set_time_limit(180);
        DB::beginTransaction(); // Inicia la transacción

        try {
            // 1. Validar que IDDepartamentos existe y es array
            if (!$request->has('IDDepartamentos') || !is_array($request->IDDepartamentos)) {
                throw new Exception('IDDepartamentos es requerido y debe ser un array');
            }

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

            // 2. Crear relaciones usuario-departamento
            RelUsuarioDepartamento::where('IDUsuario', $user->IDUsuario)
                ->whereNotIn('IDDepartamento', $request->IDDepartamentos)
                ->delete();

            $existentes = RelUsuarioDepartamento::where('IDUsuario', $user->IDUsuario)
                ->pluck('IDDepartamento')
                ->toArray();

            $nuevos = collect($request->IDDepartamentos)
                ->diff($existentes)
                ->map(fn($idDep) => [
                    'IDUsuario' => $user->IDUsuario,
                    'IDDepartamento' => $idDep,
                ])
                ->toArray();

            if (count($nuevos)) {
                RelUsuarioDepartamento::insert($nuevos);
            }

            // 3. Manejo de firma del director (corregido)
            if ($request->hasFile('firma_Director')) {
                \Log::info('=== INICIO PROCESAMIENTO FIRMA DIRECTOR ===');

                $firmaFile = $request->file('firma_Director');

                // DEBUG del archivo recibido
          
                if (!$firmaFile->isValid()) {
                    \Log::error('Archivo no válido. Error: ' . $firmaFile->getErrorMessage());
                    throw new Exception('El archivo de firma no es válido: ' . $firmaFile->getErrorMessage());
                }
                $dataArray = $request->all();

                $firmaUrl = $this->handleImageUpload($request, $dataArray, "firma_Director","firmas");
                foreach ($request->IDDepartamentos as $index => $idDep) {
                    // Log::info("--- Procesando departamento #" . ($index + 1) . " (ID: {$idDep}) ---" . " (Director: {$user->Nombre} {$user->Paterno} {$user->Materno}) ---");

                    try {
                        // Crear archivo temporal para CADA departamento
                       
                        $newRequest = new Request([
                            'IDDepartamento' => $idDep,
                            'Nombre_Director' =>         $user->Nombre . ' ' . $user->Paterno . ' ' . $user->Materno,
                        ]);

               


                       

                        
                            $result = (new DepartamentsController())->create($newRequest,$firmaUrl);
                         

                        // Limpiar archivo temporal
                       

                    } catch (\Exception $e) {
                        \Log::error("❌ ERROR en departamento ");
                        throw $e;
                    }
                }
            }
            if (in_array($request->Rol, ['AUTORIZADOR', 'DIRECTORCOMPRAS', 'CAPTURA', 'REQUISITOR', 'DIRECTOR'])) {
                // Convertir valores booleanos antes de enviar al controller
                $this->procesarAutorizador($request, $user);
            }

            // 5. Otros roles específicos
            switch ($request->Rol) {
                case 'AUTORIZADOR':
                    (new MenuUserController())->create(new Request(["Listado" => 1]), $user->Usuario);
                    break;

                case 'DIRECTORCOMPRAS':
                    (new MenuUserController())->create(new Request([
                        "Listado" => 1,
                        "ReporteConsumibles" => 1,
                        "RequisicionesAdd" => 1,
                        "SeguimientoRequis" => 1,
                        "Soporte" => 1,
                    ]), $user->Usuario);
                    break;

                case 'CAPTURA':
                    (new MenuUserController())->create(new Request(["Listado" => 1, "RequisicionesAdd" => 1]), $user->Usuario);
                    break;

                case 'REQUISITOR':
                    (new RequisitorController())->create($request);
                    (new MenuUserController())->create(new Request(["Listado" => 1]), $user->Usuario);
                    break;

                case 'DIRECTOR':
                    (new MenuUserController())->create(new Request([
                        "Listado" => 1,
                        "RequisicionesAdd" => 1,
                        "SeguimientoRequis" => 1,
                        "Soporte" => 1,
                    ]), $user->Usuario);
                    if (!$request->hasFile('firma_Director')) {
                        $newRequest = new Request([
                            'IDDepartamento' => $request->IDDepartamento,
                            'Nombre_Director' => $request->Nombre . ' ' . $request->Paterno . ' ' . $request->Materno,
                        ]);
                        $result = (new DepartamentsController())->createDirectorWithoutSignature($newRequest);
                    }
                    break;
            }

            DB::commit(); // Confirma la transacción
            return ApiResponse::success($user, $message);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            // Log detallado del error
            \Log::error('ERROR en UserController::createOrUpdate: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ', $request->all());

            // Mensajes específicos
            $errorMessages = [
                'El usuario ya existe' => 'El usuario ya existe.',
                'Ya existe el director' => 'No se puede crear el director, ya existe uno registrado.',
                'El archivo de firma no es válido' => 'El archivo de firma no es válido o está corrupto.',
            ];

            $message = $errorMessages[$e->getMessage()] ??
                'Error al procesar la solicitud: ' . $e->getMessage();

            return ApiResponse::error($message, 500);
        }
    }

    /**
     * Método auxiliar para procesar autorizador con conversión de booleanos
     */
    private function procesarAutorizador(Request $request, User $user)
    {
        // DEBUG: Ver qué viene en el request
       

        // Crear array de datos para autorizador
        $data = [
            'Autorizador' => $request->Usuario,
        ];


        // Campos booleanos que necesitan conversión
        $booleanFields = [
            'Permiso_Autorizar',
            'Permiso_Asignar',
            'Permiso_Cotizar',
            'Permiso_Orden_Compra',
            'Permiso_Surtir'
        ];

        foreach ($booleanFields as $field) {
            if ($request->has($field)) {
                $value = $request->get($field);

                // Convertir string 'true'/'false' a booleano
                if (is_string($value)) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }

                // Convertir a 1/0 para MySQL
                $data[$field] = $value ? 1 : 0;
            } else {
                // Valor por defecto
                $data[$field] = 0;
            }
        }


        // VALIDACIÓN CRÍTICA: Asegurar que Autorizador no sea null
        if (empty($data['Autorizador'])) {
           

            // Intentar obtener el valor de otra forma
            $data['Autorizador'] = $user->Usuario ?? $user->email ?? 'user_' . $user->id . '_' . time();
        }

        // Crear nuevo Request con datos convertidos
        $autorizadorRequest = new Request($data);


        (new AutorizadoresController())->create($autorizadorRequest);
    }
    public function login(Request $request)
    {
        try {
            // Validar entrada
            $credentials = $request->only('Usuario', 'Password');

            if (empty($credentials['Usuario']) || empty($credentials['Password'])) {
                return ApiResponse::error('Usuario y contraseña son requeridos', 400);
            }

            // Buscar usuario - SIN filtrar por 'active' porque NO existe
            $user = User::where('Usuario', $credentials['Usuario'])->first();

            if (!$user) {
                return ApiResponse::error('Usuario no encontrado', 401);
            }

            // Validar contraseña
            if ($user->Password !== $credentials['Password']) {
                return ApiResponse::error('Contraseña incorrecta', 401);
            }

            // Validar departamento del usuario
            if (empty($user->IDDepartamento)) {
                return ApiResponse::error('Usuario no tiene departamento asignado', 403);
            }

            $departamento = Departamento::where("IDDepartamento", $user->IDDepartamento)->first();

            if (!$departamento) {
                return ApiResponse::error('Departamento del usuario no encontrado', 403);
            }

            $continue = (bool) ($departamento->access ?? false);

            // Obtener departamentos adicionales del usuario
            $departamentosUser = RelUsuarioDepartamento::where('IDUsuario', $user->IDUsuario)
                ->pluck('IDDepartamento')
                ->toArray();

            // Obtener permisos del menú
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
            FROM cat_menus cm
            LEFT JOIN relmenuusuario rmu 
                ON rmu.IdMenu = cm.IdMenu AND rmu.Usuario = ?
            WHERE cm.active = 1
            ORDER BY cm.IdMenu
        ", [$credentials['Usuario']]);

            // Determinar ruta de redirección
            $route = $this->determineRedirectRoute($menuPermisos);

            $canAccess = ($user->Rol === "SISTEMAS") || $continue;

            // Obtener permisos del autorizador
            $permisos = Autorizadores::where('Autorizador', $credentials['Usuario'])->first();

            // Generar token
            $token = $user->createToken('YourAppName')->plainTextToken;

            return ApiResponse::success([
                "permisos" => $permisos,
                "menuPermiso" => $menuPermisos,
                "token" => $token,
                "group" => $departamentosUser,
                "role" => $user->Rol,
                "redirect" => $canAccess ? "/#/" . ($route ?: "dashboard") : "/#/access-denied",
                "centro_costo" => $departamento->Centro_Costo ?? null,
                "name" => $user->NombreCompleto ?? $user->Usuario,
                "continue" => $canAccess,
            ], 'Bienvenido al sistema');
        } catch (\Exception $e) {
            // NO hacer log complejo que pueda causar timeout
            return ApiResponse::error('Error al procesar la solicitud', 500);
        }
    }

    private function determineRedirectRoute($menuPermisos)
    {
        if (empty($menuPermisos)) {
            return "dashboard";
        }

        // Prioridad 1: Listado
        foreach ($menuPermisos as $mP) {
            if (isset($mP->IdMenu) && trim($mP->IdMenu) === "Listado" && ($mP->EstadoPermiso ?? 0) == 1) {
                return "MnuRequisiciones";
            }
        }

        // Prioridad 2: CatProveedores
        foreach ($menuPermisos as $mP) {
            if (isset($mP->IdMenu) && trim($mP->IdMenu) === "CatProveedores" && ($mP->EstadoPermiso ?? 0) == 1) {
                return "CatProveedores";
            }
        }

        // Prioridad 3: Seguridad
        foreach ($menuPermisos as $mP) {
            if (isset($mP->IdMenu) && (trim($mP->IdMenu) === "Usuarios" || trim($mP->IdMenu) === "RequisicionesAdd") && ($mP->EstadoPermiso ?? 0) == 1) {
                return "MnuSeguridad";
            }
        }

        // Prioridad 4: Departamentos
        foreach ($menuPermisos as $mP) {
            if (isset($mP->IdMenu) && trim($mP->IdMenu) === "CatDepartamentos" && ($mP->EstadoPermiso ?? 0) == 1) {
                return "CatDepartamentos";
            }
        }

        return "dashboard";
    }





    public function logout(Request $request)
    {
        // Revocar el token del usuario autenticado
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'cerrando sesión']);
    }
    public function changePassword(Request $request)
    {
        $user = User::where('IDUsuario', Auth::user()->IDUsuario)->first();
        if ($user) {
            $user->Password =  $request->Password;
            $user->save();
            return ApiResponse::success($user, 'Contraseña actualizada con éxito');
        } else {
            return ApiResponse::error('El usuario no existe', 404);
        }
    }
}

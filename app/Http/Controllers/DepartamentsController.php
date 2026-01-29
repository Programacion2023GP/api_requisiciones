<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\Director;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepartamentsController extends Controller
{
    public function index()
    {
        try {
            $departaments = DB::table('directores')->get();
            return ApiResponse::success($departaments, 'Usuarios recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
    public function director(int $id)
    {
        try {
            $departaments = DB::table('det_directores')->where('IDDepartamento', $id)->orderBy("IdDetDirectores", "desc")->get();

            return ApiResponse::success($departaments, 'Usuarios recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los usuarios', 500);
        }
    }
    // public function directores
    public function update(Request $request)
    {
        try {
            $departaments = Departamento::where('IDDepartamento', $request->IDDepartamento)
                ->update(['Centro_Costo' => $request->Centro_Costo]);

            return ApiResponse::success($departaments, 'Centro de costo actualizado');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function createDirectorWithoutSignature(Request $request)
    {
        try {
            if (!$request->has('IDDepartamento')) {
                throw new \Exception('Datos incompletos');
            }

            $now = now();
            $usuario = Auth::user()->Usuario ?? 'system';

            DB::table('det_directores')->updateOrInsert(
                [
                    'IdDepartamento' => $request->IDDepartamento,
                    'Nombre_Director' => $request->Nombre_Director,
                ],
                [
                    'Nombre_Director' => $request->Nombre_Director,
                    'Firma_Director' => null, // Firma nula
                    'FechaInicio' => $now->format('Y-m-d'),
                    'FechaAlta' => $now,
                    'Usuario' => $usuario,
                    'Fum' => $now->format('Y-m-d'),
                    'UsuarioFum' => $usuario,
                ]
            );

            return ApiResponse::success([], 'Director creado sin firma');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function create(Request $request, $firmaUrl)
    {
        DB::beginTransaction();
        try {
          

            // 1. VALIDACIONES BÁSICAS
            if (!$request->has('IDDepartamento') || empty($request->IDDepartamento)) {
                throw new \Exception('IDDepartamento es requerido');
            }

            if (!$request->has('Nombre_Director') || empty($request->Nombre_Director)) {
                throw new \Exception('Nombre_Director es requerido');
            }

            // 2. VERIFICAR SI YA EXISTE ANTES DE INSERTAR
          

            // 3. INSERTAR/ACTUALIZAR DIRECTOR
            $now = now();
            $usuario = Auth::user()->Usuario ?? 'system';

           
            // Usar una variable para capturar el resultado
            $resultado = DB::table('det_directores')->updateOrInsert(
                [
                    'IdDepartamento' => $request->IDDepartamento,
                    'Nombre_Director' => $request->Nombre_Director,
                ],
                [
                    'Nombre_Director' => $request->Nombre_Director,
                    'Firma_Director' => $firmaUrl, // Puede ser null
                    'FechaInicio' => $now->format('Y-m-d'),
                    'FechaAlta' => $now,
                    'Usuario' => $usuario,
                    'Fum' => $now->format('Y-m-d'),
                    'UsuarioFum' => $usuario,
                ]
            );


            // 4. VERIFICAR DESPUÉS DE LA OPERACIÓN
           

            // 5. VERIFICAR ERRORES DE BASE DE DATOS
          

            DB::commit();

            return $resultado;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Error en create():', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    /**
     * Función para guardar una imagen en el microservicio, elimina y guarda la nueva al editar la imagen
     * para no guardar muchas imágenes y genera el path que se guardará en la BD
     * 
     * @param $image File es el archivo de la imagen
     * @param $destination String ruta donde se guardará en el microservicio
     * @param $dir String ruta que mandará a la BD
     * @param $imgName String Nombre de como se guardará el archivo
     * @return String URL completa de la imagen en el microservicio
     */

    public function updateNameDepartament(Request $request)
    {
        try {
            // Actualizar el nombre del departamento
            $departament = DB::table('cat_departamentos')
                ->where('IDDepartamento', $request->IDDepartamento)
                ->update([
                    'Nombre_Departamento' => $request->Nombre_Departamento,
                ]);

            // Si no se actualizó ninguna fila
            if ($departament === 0) {
                return ApiResponse::error('No se encontró el departamento o no hubo cambios.', 404);
            }

            // Responder con éxito
            return ApiResponse::success($departament, 'Departamento actualizado exitosamente');
        } catch (\Exception $e) {
            // Manejo de errores
            return ApiResponse::error($e->getMessage(), 500);
        }
    }



    public function relUserDepartment(Request $request)
    {
        try {
            // Crear una nueva instancia de Director
            $director = new Director();

            // Asignar los valores del request al director
            $director->IDDepartamento = $request->IDDepartamento;
            $directoresId = DB::table('relusuariodepartamento')->insertGetId([
                "IDUsuario" => $request->IDUsuario,
                "IDDepartamento" => $request->IDDepartamento,
            ]);
            $nameDirector = DB::table('cat_usuarios')
                ->where("IDDepartamento", $request->IDDepartamento)
                ->first();
            $director->Nombre_Director = $nameDirector->NombreCompleto;
            // Procesar la imagen (firma del director)
            if ($request->hasFile('Firma_Director') && $request->file('Firma_Director')->isValid()) {
                // Obtener el archivo
                $firma = $request->file('Firma_Director');

                // Crear un nombre único para el archivo
                $filename = uniqid('firma_') . '.' . $firma->getClientOriginalExtension();

                // Guardar la imagen en la carpeta correspondiente al ID del departamento
                $path = $firma->storeAs('public/firma_directores/' . $request->IDDepartamento, $filename);

                // Asignar el path de la firma al modelo
                $director->Firma_Director = 'storage/firma_directores/' . $request->IDDepartamento . "/" . $filename;
            } else {
                throw new \Exception('La firma no es válida o no fue cargada correctamente.');
            }

            // Asignar los demás valores
            $director->FechaInicio = now()->format('Y-m-d'); // Cambiar el formato de la fecha
            $director->FechaAlta = now();
            $director->Usuario = Auth::user()->Usuario;
            $director->Fum = now()->format('Y-m-d');
            $director->UsuarioFum = Auth::user()->Usuario;

            // Guardar el director en la base de datos
            $director->save();

            // Responder con éxito
            return ApiResponse::success($director, 'Director registrado exitosamente');
        } catch (\Exception $e) {
            // Manejo de errores
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

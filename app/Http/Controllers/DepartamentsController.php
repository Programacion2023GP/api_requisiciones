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
    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            \Log::info('=== DirectorController::create() - USANDO det_directores ===');
            \Log::info('Request:', $request->all());

            // 1. VALIDACIONES BÁSICAS
            if (!$request->has('IDDepartamento') || empty($request->IDDepartamento)) {
                throw new \Exception('IDDepartamento es requerido');
            }

            if (!$request->has('Nombre_Director') || empty($request->Nombre_Director)) {
                throw new \Exception('Nombre_Director es requerido');
            }

            if (!$request->hasFile('firma_Director') || !$request->file('firma_Director')->isValid()) {
                throw new \Exception('La firma del director es requerida y válida');
            }

            // 2. PROCESAR ARCHIVO
            $firma = $request->file('firma_Director');
            $dirPath = "presidencia/firmas_directores";

            \Log::info('Procesando archivo: ' . $firma->getClientOriginalName());

            // Validar tamaño y tipo
            if ($firma->getSize() > 5 * 1024 * 1024) {
                throw new \Exception('El archivo es demasiado grande. Máximo 5MB');
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'application/pdf'];
            if (!in_array($firma->getMimeType(), $allowedMimes)) {
                throw new \Exception('Tipo de archivo no permitido');
            }

            // Subir imagen
            $imagePath = $this->ImgUpload(
                $firma,
                $request->IDDepartamento,
                $dirPath,
                'firma_director_' . $request->IDDepartamento . '_' . time()
            );

            if (!$imagePath) {
                throw new \Exception('Error al subir la imagen');
            }

            $firmaUrl = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->IDDepartamento . "/" . $imagePath;
            \Log::info('URL de firma: ' . $firmaUrl);

            // 3. ELIMINAR DIRECTOR EXISTENTE (si hay)
            \Log::info('Buscando director existente para departamento: ' . $request->IDDepartamento);

            $existing = DB::table('det_directores')
                ->where('IdDepartamento', $request->IDDepartamento)
                ->first();

            if ($existing) {
                \Log::info('Director existente encontrado, ID: ' . $existing->IdDetDirectores);
                \Log::info('Eliminando...');

                DB::table('det_directores')
                    ->where('IdDetDirectores', $existing->IdDetDirectores)
                    ->delete();

                \Log::info('Director existente eliminado');
            } else {
                \Log::info('No hay director existente para este departamento');
            }

            // 4. INSERTAR NUEVO DIRECTOR EN det_directores
            \Log::info('Insertando nuevo director en det_directores...');

            $now = now();
            $usuario = Auth::user()->Usuario ?? 'system';

            $id = DB::table('det_directores')->insertGetId([
                'IdDepartamento' => $request->IDDepartamento,
                'Nombre_Director' => $request->Nombre_Director,
                'Firma_Director' => $firmaUrl,
                'FechaInicio' => $now->format('Y-m-d'),
                'FechaAlta' => $now,
                'Usuario' => $usuario,
                'Fum' => $now->format('Y-m-d'),
                'UsuarioFum' => $usuario,
            ]);

            \Log::info('✅ Director insertado en det_directores con ID: ' . $id);

            // 5. OBTENER DATOS COMPLETOS DE LA VISTA
            \Log::info('Obteniendo datos de la vista directores...');

            $directorCompleto = DB::table('directores')
                ->where('IDDepartamento', $request->IDDepartamento)
                ->first();

            if (!$directorCompleto) {
                \Log::warning('No se encontró en la vista directores, obteniendo directamente...');

                // Si la vista no se actualizó inmediatamente, obtener de det_directores
                $directorCompleto = DB::table('det_directores')
                    ->where('IdDetDirectores', $id)
                    ->first();

                // Agregar datos del departamento
                $departamento = DB::table('cat_departamentos')
                    ->where('IDDepartamento', $request->IDDepartamento)
                    ->first();

                if ($departamento) {
                    $directorCompleto->Nombre_Departamento = $departamento->Nombre_Departamento;
                    $directorCompleto->Centro_Costo = $departamento->Centro_Costo;
                }
            }

            \Log::info('Datos del director:', (array)$directorCompleto);

            DB::commit();

            \Log::info('=== DirectorController::create() - COMPLETADO ===');

            return ApiResponse::success($directorCompleto, 'Director registrado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('❌ ERROR en DirectorController::create: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

            return ApiResponse::error('Error: ' . $e->getMessage(), 500);
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
    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        // Verificar que la imagen sea válida
        if (!$image || !$image->isValid()) {
            throw new \Exception('La imagen no es válida');
        }

        // Generar nombre único para el archivo
        $extension = $image->getClientOriginalExtension();
        $filename = $imgName . '_' . time() . '.' . $extension;

        // Subir al microservicio con los parámetros específicos
        $imageUrl = $this->uploadToMicroservice($image, $destination, $dir, $filename);

        // Devolver la URL completa para la BD
        return $filename;
    }

    /**
     * Función auxiliar para subir al microservicio con los parámetros específicos
     */
    private function uploadToMicroservice($file, $destination, $dir, $filename)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false, // ⚠️ SOLO PARA DESARROLLO
            ]);

            $response = $client->request('POST', 'https://api.gpcenter.gomezpalacio.gob.mx/api/smImgUpload', [
                'multipart' => [
                    [
                        'name'     => 'Firma_Director',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $filename,
                    ],
                    [
                        'name' => 'dirDestination',
                        'contents' => $destination,
                    ],
                    [
                        'name' => 'dirPath',
                        'contents' => $dir,
                    ],
                    [
                        'name' => 'imgName',
                        'contents' => $filename,
                    ],
                    [
                        'name' => 'requestFileName',
                        'contents' => 'Firma_Director',
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
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

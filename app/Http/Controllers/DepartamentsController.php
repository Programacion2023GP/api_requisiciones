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
            $departaments = DB::table('det_directores')->where('IDDepartamento', $id)->get();

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
            ->where("IDDepartamento", $request->IDDepartamento)->where("Rol","DIRECTOR")
            ->first();
        $director->Nombre_Director = $nameDirector->NombreCompleto;
        $dirPath = "presidencia/firmas_directores";
        // Procesar la imagen usando la función ImgUpload adaptada
        if ($request->hasFile('Firma_Director') && $request->file('Firma_Director')->isValid()) {
            $firma = $request->file('Firma_Director');
            
            // Usar la función ImgUpload con los parámetros que espera el microservicio
            $imagePath = $this->ImgUpload(
                $firma, 
               $request->IDDepartamento, // destination
                $dirPath, // dir
                'firma_director_' . $request->IDDepartamento    // imgName
            );
            
            $director->Firma_Director = "https://api.gpcenter.gomezpalacio.gob.mx/". $dirPath ."/". $request->IDDepartamento."/". $imagePath;
        } else {
            throw new \Exception('La firma no es válida o no fue cargada correctamente.');
        }

        // Asignar los demás valores
        $director->FechaInicio = now()->format('Y-m-d');
        $director->FechaAlta = now();
        $director->Usuario = Auth::user()->Usuario;
        $director->Fum = now()->format('Y-m-d');
        $director->UsuarioFum = Auth::user()->Usuario;

        // Guardar el director en la base de datos
        $director->save();

        return ApiResponse::success($director, 'Director registrado exitosamente');
    } catch (\Exception $e) {
        return ApiResponse::error($e->getMessage(), 500);
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
        $client = new \GuzzleHttp\Client();

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
        throw new \Exception( $e->getMessage());
    }
}

}

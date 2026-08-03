<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Guarda una imagen en el disco local 'public' (storage/app/public/...)
     * y devuelve solo el nombre de archivo generado.
     *
     * @param  \Illuminate\Http\UploadedFile $image
     * @param  string $destination  Subcarpeta específica (ej. CURP)
     * @param  string $dir          Ruta base dentro de storage/app/public (ej. presidencia/Firmas/algo)
     * @param  string $imgName      Nombre base sin extensión
     * @return string               Nombre de archivo final (ej. curp_1690000000.jpg)
     */
    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        \Log::info('=== IMGUPLOAD INICIO (local) ===');
        \Log::info('- Destination: ' . $destination);
        \Log::info('- Dir: ' . $dir);
        \Log::info('- ImgName: ' . $imgName);

        if (!$image || !$image->isValid()) {
            \Log::error('❌ La imagen no es válida');
            throw new \Exception('La imagen no es válida');
        }

        $extension = $image->getClientOriginalExtension();
        $filename  = $imgName . '_' . time() . '.' . $extension;

        \Log::info('Archivo a guardar localmente:');
        \Log::info('- Nombre original: ' . $image->getClientOriginalName());
        \Log::info('- Tamaño: ' . $image->getSize() . ' bytes');
        \Log::info('- MIME type: ' . $image->getMimeType());
        \Log::info('- Nombre generado: ' . $filename);

        // Ruta relativa dentro de storage/app/public
        $rutaRelativa = rtrim($dir, '/') . '/' . rtrim($destination, '/');

        try {
            $guardado = Storage::disk('public')->putFileAs($rutaRelativa, $image, $filename);

            if (!$guardado) {
                \Log::error('❌ Storage::putFileAs devolvió false');
                throw new \Exception('No se pudo guardar la imagen localmente');
            }

            \Log::info('✅ Imagen guardada en: storage/app/public/' . $rutaRelativa . '/' . $filename);
            \Log::info('=== IMGUPLOAD FIN ===');

            return $filename;
        } catch (\Exception $e) {
            \Log::error('❌ Error guardando imagen local: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function handleImageUpload(Request $request, array &$data, string $fieldName, string $subdirectory, ?string $customFilename = null)
    {
        \Log::info('=== HANDLEIMAGEUPLOAD INICIO ===');
        \Log::info('Field: ' . $fieldName);
        \Log::info('Subdirectory: ' . $subdirectory);
        \Log::info('Custom filename: ' . ($customFilename ?? 'Ninguno'));

        $url = null;

        $curp = $data['curp'] ?? $request->input('curp') ?? $request->curp ?? 'SIN_CURP';
        \Log::info('CURP identificada: ' . $curp);

        $filename = $customFilename ?? $curp;
        $basePath = "presidencia/Firmas/"; // relativo a storage/app/public
        $appUrl   = rtrim(env('APP_URL', 'http://localhost'), '/');

        \Log::info('Configuración base:');
        \Log::info('- APP_URL: ' . $appUrl);
        \Log::info('- Base Path: ' . $basePath);
        \Log::info('- Filename final: ' . $filename);

        $fileInfo = [
            'hasFile'      => $request->hasFile($fieldName) ? 'SÍ' : 'NO',
            'existsInData' => isset($data[$fieldName]) ? 'SÍ' : 'NO',
        ];
        if ($request->hasFile($fieldName)) {
            $fileInfo['isValid'] = $request->file($fieldName)->isValid() ? 'SÍ' : 'NO';
            $fileInfo['name']    = $request->file($fieldName)->getClientOriginalName();
            $fileInfo['size']    = $request->file($fieldName)->getSize();
        }
        \Log::info('Estado del archivo:', $fileInfo);

        // **CASO 1: Archivo normal (multipart/form-data)**
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {
            \Log::info('📁 CASO 1: Archivo normal detectado');

            try {
                $file    = $request->file($fieldName);
                $dirPath = rtrim($basePath . $subdirectory, '/');

                $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                if ($imagePath) {
                    // Ej: http://localhost/storage/presidencia/Firmas/algo/CURP123/CURP123_169...jpg
                    $url = $appUrl . '/storage/' . $dirPath . '/' . $curp . '/' . $imagePath;
                    $data[$fieldName] = $url;

                    \Log::info('✅ Archivo guardado localmente');
                    \Log::info('- URL final: ' . $url);
                } else {
                    \Log::error('❌ ImagePath retornó null/false');
                    unset($data[$fieldName]);
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error en CASO 1: ' . $e->getMessage());
                \Log::error('Trace: ' . $e->getTraceAsString());
                unset($data[$fieldName]);
            }
        }
        // **CASO 2: Archivo serializado en JSON (OBJETO)**
        elseif (isset($data[$fieldName]) && (is_array($data[$fieldName]) || is_object($data[$fieldName]))) {
            \Log::info('📄 CASO 2: Archivo serializado detectado');

            try {
                $fileData = (array) $data[$fieldName];
                \Log::info('FileData estructura:', $fileData);

                $tempFilePath = null;
                foreach ($fileData as $key => $value) {
                    if (is_string($value) && (str_contains($key, 'UploadedFile') || str_contains($value, 'Temp') || str_contains($value, 'tmp'))) {
                        $tempFilePath = $value;
                        \Log::info('✅ Ruta temporal encontrada: ' . $tempFilePath);
                        break;
                    }
                }

                if ($tempFilePath && file_exists($tempFilePath)) {
                    \Log::info('Procesando archivo temporal...');
                    \Log::info('- Ruta: ' . $tempFilePath);
                    \Log::info('- Tamaño: ' . filesize($tempFilePath) . ' bytes');

                    $mimeType = @mime_content_type($tempFilePath);

                    $file = new \Illuminate\Http\UploadedFile(
                        $tempFilePath,
                        basename($tempFilePath),
                        $mimeType ?: null,
                        filesize($tempFilePath),
                        0,
                        true
                    );

                    $dirPath = rtrim($basePath . $subdirectory, '/');
                    $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                    if ($imagePath) {
                        $url = $appUrl . '/storage/' . $dirPath . '/' . $curp . '/' . $imagePath;
                        $data[$fieldName] = $url;
                        \Log::info('✅ Archivo temporal guardado localmente: ' . $url);
                    } else {
                        \Log::error('❌ ImagePath retornó null/false para archivo temporal');
                        unset($data[$fieldName]);
                    }
                } else {
                    \Log::warning('⚠️ No se encontró ruta temporal válida');
                    unset($data[$fieldName]);
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error en CASO 2: ' . $e->getMessage());
                \Log::error('Trace: ' . $e->getTraceAsString());
                unset($data[$fieldName]);
            }
        }
        // **CASO 3: Limpieza de datos inválidos**
        else {
            \Log::info('🧹 CASO 3: Limpieza de datos');

            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];

                if (is_string($value) && (str_contains($value, 'Temp\\php') || str_contains($value, 'Temp/php') || str_contains($value, 'tmp/'))) {
                    \Log::info('🗑️  Eliminando ruta temporal: ' . $value);
                    unset($data[$fieldName]);
                } elseif (is_array($value) && empty($value)) {
                    \Log::info('🗑️  Eliminando array vacío');
                    unset($data[$fieldName]);
                } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    \Log::info('🔗 URL válida detectada, manteniendo: ' . $value);
                } else {
                    \Log::info('🗑️  Eliminando dato inválido de tipo: ' . gettype($value));
                    unset($data[$fieldName]);
                }
            } else {
                \Log::info('ℹ️  Campo ' . $fieldName . ' no existe en data');
            }
        }

        \Log::info('URL final retornada: ' . ($url ?? 'NULL'));
        \Log::info('=== HANDLEIMAGEUPLOAD FIN ===');

        return $url;
    }
}

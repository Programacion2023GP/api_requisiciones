<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        \Log::info('=== IMGUPLOAD INICIO ===');
        \Log::info('Parámetros recibidos:');
        \Log::info('- Destination: ' . $destination);
        \Log::info('- Dir: ' . $dir);
        \Log::info('- ImgName: ' . $imgName);
        \Log::info('- Image type: ' . get_class($image));

        // Verificar que la imagen sea válida
        if (!$image || !$image->isValid()) {
            \Log::error('❌ La imagen no es válida');
            \Log::error('¿Image existe?: ' . ($image ? 'SÍ' : 'NO'));
            \Log::error('¿Es válida?: ' . ($image && $image->isValid() ? 'SÍ' : 'NO'));
            throw new \Exception('La imagen no es válida');
        }

        // Generar nombre único para el archivo
        $extension = $image->getClientOriginalExtension();
        $filename = $imgName . '_' . time() . '.' . $extension;

        \Log::info('Archivo a subir:');
        \Log::info('- Nombre original: ' . $image->getClientOriginalName());
        \Log::info('- Extensión: ' . $extension);
        \Log::info('- Tamaño: ' . $image->getSize() . ' bytes');
        \Log::info('- MIME type: ' . $image->getMimeType());
        \Log::info('- Nombre generado: ' . $filename);
        \Log::info('- Ruta temporal: ' . $image->getPathname());
        \Log::info('- ¿Existe temporal?: ' . (file_exists($image->getPathname()) ? 'SÍ' : 'NO'));

        // Subir al microservicio con los parámetros específicos
        \Log::info('Llamando a uploadToMicroservice...');
        try {
            $imageUrl = $this->uploadToMicroservice($image, $destination, $dir, $filename);
            \Log::info('✅ ImgUpload completado exitosamente');
            \Log::info('=== IMGUPLOAD FIN ===');
            return $filename;
        } catch (\Exception $e) {
            \Log::error('❌ Error en ImgUpload: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Función auxiliar para subir al microservicio con los parámetros específicos
     */
    private function uploadToMicroservice($file, $destination, $dir, $filename)
    {
        \Log::info('=== UPLOADTOMICROSERVICE INICIO ===');
        \Log::info('Configurando cliente Guzzle...');

        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false, // Disable SSL verification
                'timeout' => 30,
                'connect_timeout' => 10,
                'debug' => fopen(storage_path('logs/guzzle_debug.log'), 'a') // Log detallado
            ]);

            \Log::info('Preparando multipart data...');
            \Log::info('- Destination: ' . $destination);
            \Log::info('- Dir: ' . $dir);
            \Log::info('- Filename: ' . $filename);
            \Log::info('- Ruta temporal del archivo: ' . $file->getPathname());
            \Log::info('- ¿Archivo existe?: ' . (file_exists($file->getPathname()) ? 'SÍ' : 'NO'));

            if (!file_exists($file->getPathname())) {
                \Log::error('❌ Archivo temporal no existe en: ' . $file->getPathname());
                throw new \Exception('Archivo temporal no encontrado');
            }

            // Verificar si podemos abrir el archivo
            $handle = @fopen($file->getPathname(), 'r');
            if (!$handle) {
                \Log::error('❌ No se pudo abrir el archivo: ' . $file->getPathname());
                \Log::error('Último error PHP: ' . error_get_last()['message'] ?? 'Desconocido');
                throw new \Exception('No se puede leer el archivo temporal');
            }
            fclose($handle);

            $multipart = [
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
            ];

            \Log::info('Enviando request a microservicio...');
            \Log::info('URL: https://api.gpcenter.gomezpalacio.gob.mx/api/smImgUpload');

            $startTime = microtime(true);

            $response = $client->request('POST', 'https://api.gpcenter.gomezpalacio.gob.mx/api/smImgUpload', [
                'multipart' => $multipart,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);

            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            \Log::info('✅ Respuesta recibida del microservicio');
            \Log::info('- Tiempo de respuesta: ' . round($duration, 2) . ' segundos');
            \Log::info('- Status Code: ' . $response->getStatusCode());
            \Log::info('- Headers: ' . json_encode($response->getHeaders()));

            // Check response status
            if ($response->getStatusCode() !== 200) {
                \Log::error('❌ Código de respuesta incorrecto: ' . $response->getStatusCode());
                \Log::error('Body: ' . $response->getBody()->getContents());
                throw new \Exception('Error al subir la imagen. Código: ' . $response->getStatusCode());
            }

            $body = $response->getBody()->getContents();
            \Log::info('Body de respuesta: ' . $body);

            \Log::info('=== UPLOADTOMICROSERVICE FIN ===');

            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::error('❌ Error de conexión en uploadToMicroservice: ' . $e->getMessage());
            \Log::error('¿Microservicio disponible?');
            throw new \Exception('Error de conexión con el microservicio: ' . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Log::error('❌ Error en request a microservicio: ' . $e->getMessage());
            if ($e->hasResponse()) {
                \Log::error('Response: ' . $e->getResponse()->getBody()->getContents());
            }
            throw new \Exception('Error en la solicitud al microservicio: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('❌ Error general en uploadToMicroservice: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw new \Exception('Error en uploadToMicroservice: ' . $e->getMessage());
        }
    }

    public function handleImageUpload(Request $request, array &$data, string $fieldName, string $subdirectory, ?string $customFilename = null)
    {
        \Log::info('=== HANDLEIMAGEUPLOAD INICIO ===');
        \Log::info('Field: ' . $fieldName);
        \Log::info('Subdirectory: ' . $subdirectory);
        \Log::info('Custom filename: ' . ($customFilename ?? 'Ninguno'));

        $url = null;

        // VERIFICAR SI EL CAMPO CURP EXISTE
        $curp = $data['curp'] ?? $request->input('curp') ?? $request->curp ?? 'SIN_CURP';
        \Log::info('CURP identificada: ' . $curp);

        $filename = $customFilename ?? $curp;
        $baseUrl = "https://api.gpcenter.gomezpalacio.gob.mx/";
        $basePath = "presidencia/Firmas/";

        \Log::info('Configuración base:');
        \Log::info('- Base URL: ' . $baseUrl);
        \Log::info('- Base Path: ' . $basePath);
        \Log::info('- Filename final: ' . $filename);

        // Debug mejorado
        $fileInfo = [
            'hasFile' => $request->hasFile($fieldName) ? 'SÍ' : 'NO',
            'existsInData' => isset($data[$fieldName]) ? 'SÍ' : 'NO'
        ];

        if ($request->hasFile($fieldName)) {
            $fileInfo['isValid'] = $request->file($fieldName)->isValid() ? 'SÍ' : 'NO';
            $fileInfo['type'] = get_class($request->file($fieldName));
            $fileInfo['name'] = $request->file($fieldName)->getClientOriginalName();
            $fileInfo['size'] = $request->file($fieldName)->getSize();
        }

        \Log::info('Estado del archivo:', $fileInfo);

        // **CASO 1: Archivo normal (multipart/form-data)**
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {
            \Log::info('📁 CASO 1: Archivo normal detectado');

            try {
                $file = $request->file($fieldName);
                $dirPath = rtrim($basePath . $subdirectory, '/');

                \Log::info('Preparando para subir archivo...');
                \Log::info('- Ruta completa directorio: ' . $dirPath);
                \Log::info('- CURP para directorio: ' . $curp);

                $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                if ($imagePath) {
                    // Construir URL sin doble barra
                    $url = $baseUrl . $dirPath . "/" . $curp . "/" . $imagePath;
                    $data[$fieldName] = $url;

                    \Log::info('✅ Archivo subido exitosamente');
                    \Log::info('- ImagePath: ' . $imagePath);
                    \Log::info('- URL final: ' . $url);

                    // IMPORTANTE: Eliminar el archivo temporal del array $data
                    if (isset($data[$fieldName]) && is_array($data[$fieldName])) {
                        unset($data[$fieldName]);
                    }
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
                // Convertir a array si es objeto
                $fileData = (array) $data[$fieldName];
                \Log::info('FileData estructura:', $fileData);

                // Buscar la clave con la ruta temporal (puede tener backslashes)
                $tempFilePath = null;
                foreach ($fileData as $key => $value) {
                    \Log::info('Revisando key ' . $key . ' => ' . (is_string($value) ? $value : gettype($value)));
                    if (is_string($value) && (strpos($key, 'UploadedFile') !== false || strpos($value, 'Temp') !== false || strpos($value, 'tmp') !== false)) {
                        $tempFilePath = $value;
                        \Log::info('✅ Ruta temporal encontrada: ' . $tempFilePath);
                        break;
                    }
                }

                if ($tempFilePath && file_exists($tempFilePath)) {
                    \Log::info('Procesando archivo temporal...');
                    \Log::info('- Ruta: ' . $tempFilePath);
                    \Log::info('- Tamaño: ' . filesize($tempFilePath) . ' bytes');
                    \Log::info('- ¿Legible?: ' . (is_readable($tempFilePath) ? 'SÍ' : 'NO'));

                    $mimeType = @mime_content_type($tempFilePath);
                    \Log::info('- MIME type detectado: ' . ($mimeType ?: 'No detectado'));

                    $file = new \Illuminate\Http\UploadedFile(
                        $tempFilePath,
                        basename($tempFilePath),
                        $mimeType ?: null,
                        filesize($tempFilePath),
                        0,
                        true
                    );

                    $dirPath = rtrim($basePath . $subdirectory, '/');
                    \Log::info('Llamando a ImgUpload con archivo temporal...');

                    $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                    if ($imagePath) {
                        $url = $baseUrl . $dirPath . "/" . $curp . "/" . $imagePath;
                        $data[$fieldName] = $url;
                        \Log::info('✅ Archivo temporal subido: ' . $url);
                    } else {
                        \Log::error('❌ ImagePath retornó null/false para archivo temporal');
                        unset($data[$fieldName]);
                    }
                } else {
                    \Log::warning('⚠️ No se encontró ruta temporal válida');
                    \Log::info('TempFilePath: ' . ($tempFilePath ?? 'NULL'));
                    \Log::info('¿Existe?: ' . ($tempFilePath && file_exists($tempFilePath) ? 'SÍ' : 'NO'));
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
                \Log::info('Valor actual: ' . (is_string($value) ? $value : gettype($value)));

                // Si es string con ruta temporal
                if (is_string($value) && (str_contains($value, 'Temp\\php') || str_contains($value, 'Temp/php') || str_contains($value, 'tmp/'))) {
                    \Log::info('🗑️  Eliminando ruta temporal: ' . $value);
                    unset($data[$fieldName]);
                }
                // Si es array/objeto vacío
                elseif (is_array($value) && empty($value)) {
                    \Log::info('🗑️  Eliminando array vacío');
                    unset($data[$fieldName]);
                }
                // Si ya es una URL válida, mantenerla
                elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    \Log::info('🔗 URL válida detectada, manteniendo: ' . $value);
                }
                // Otros casos
                else {
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

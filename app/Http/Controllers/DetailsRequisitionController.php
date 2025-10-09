<?php

namespace App\Http\Controllers;

use App\Models\DetailRequisition;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DetailsRequisitionController extends Controller
{
    // Crear producto
public function create(int $idRequisicion, string $cantidad, string $descripcion, $imagen = null)
{
    $departmentsController = new DepartamentsController();
    $dirPath = "presidencia/producto";

    try {
        // 1️⃣ Crear el producto sin imagen
        $details = new DetailRequisition();
        $details->IDRequisicion = $idRequisicion;
        $details->Cantidad = $cantidad;
        $details->Descripcion = $descripcion;
        $details->Ejercicio = date('Y');
        $details->save();

        // 2️⃣ Subir imagen si existe
        if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
            $imagePath = $departmentsController->ImgUpload(
                $imagen,
                $details->IDDetalle,
                $dirPath,
                'producto_' . $details->IDDetalle
            );

            $details->image ="https://api.gpcenter.gomezpalacio.gob.mx/" . "$dirPath/"  . $details->IDDetalle . "/" . $imagePath;
            $details->save();
        }

       
    } catch (Exception $e) {
        Log::error("Error en create: " . $e->getMessage());
        throw $e;
    }
}
    public function update(int $idDetalle, string $cantidad, string $descripcion, $imagen = null)
    {
        $departmentsController = new DepartamentsController();
        $dirPath = "presidencia/producto";

        try {
            $details = DetailRequisition::find($idDetalle);
            if (!$details) {
                throw new Exception("Detalle no encontrado");
            }

            $details->Cantidad = $cantidad;
            $details->Descripcion = $descripcion;

            // Si hay una nueva imagen, procesarla
            if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                // Eliminar imagen anterior si existe
                if ($details->image) {
                    $this->deleteImage($details->image);
                }

                $imagePath = $departmentsController->ImgUpload(
                    $imagen,
                    $details->IDDetalle,
                    $dirPath,
                    'producto_' . $details->IDDetalle
                );
            $details->image ="https://api.gpcenter.gomezpalacio.gob.mx/" . "$dirPath/"  . $details->IDDetalle . "/" . $imagePath;
            }

            $details->save();
        } catch (Exception $e) {
            Log::error("Error en update: " . $e->getMessage());
            throw $e;
        }
    }

    // Eliminar producto
    public function delete(int $idDetalle)
    {
        try {
            $details = DetailRequisition::find($idDetalle);
            if ($details) {
                // Eliminar imagen asociada si existe
                if ($details->image) {
                    $this->deleteImage($details->image);
                }
                $details->delete();
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error("Error en delete: " . $e->getMessage());
            throw $e;
        }
    }

    // Método para eliminar imagen física del servidor
    private function deleteImage($imageUrl)
    {
        try {
            // Extraer la ruta del archivo de la URL
            $path = parse_url($imageUrl, PHP_URL_PATH);
            // Remover el primer slash si existe
            $path = ltrim($path, '/');
            
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        } catch (Exception $e) {
            Log::error("Error eliminando imagen: " . $e->getMessage());
        }
    }

    // Método para obtener detalles por requisición
    public function getByRequisition($idRequisicion)
    {
        try {
            return DetailRequisition::where('IDRequisicion', $idRequisicion)->get();
        } catch (Exception $e) {
            Log::error("Error obteniendo detalles: " . $e->getMessage());
            throw $e;
        }
    }

    // Método para actualizar solo la imagen de un producto
    public function updateImage(int $idDetalle, $imagen)
    {
        $departmentsController = new DepartamentsController();
        $dirPath = "presidencia/producto";

        try {
            $details = DetailRequisition::find($idDetalle);
            if (!$details) {
                throw new Exception("Detalle no encontrado");
            }

            if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                // Eliminar imagen anterior si existe
                if ($details->image) {
                    $this->deleteImage($details->image);
                }

                $imagePath = $departmentsController->ImgUpload(
                    $imagen,
                    $details->IDDetalle,
                    $dirPath,
                    'producto_' . $details->IDDetalle
                );
                $details->image = url("$dirPath/" . $imagePath);
                $details->save();
            }

            return $details;
        } catch (Exception $e) {
            Log::error("Error actualizando imagen: " . $e->getMessage());
            throw $e;
        }
    }
}
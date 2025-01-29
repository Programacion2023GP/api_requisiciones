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
            $director->IdDepartamento = $request->IDDepartamento;
            $director->Nombre_Director = $request->Nombre_Director;

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

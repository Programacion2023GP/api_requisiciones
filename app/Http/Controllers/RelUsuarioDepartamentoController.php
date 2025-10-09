<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\RelUsuarioDepartamento;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RelUsuarioDepartamentoController extends Controller
{
    public function storeDepartamentos(Int $IDUsuario, $IdsDepartamentos)
    {
        try {
            // $userId = $request->IDUsuario ?? auth()->id();
            // // 1️⃣ Obtener los departamentos seleccionados desde el request
            // $departamentos = $request->departamentos ?? [];

            // 2️⃣ Eliminar relaciones que ya no estén seleccionadas
            RelUsuarioDepartamento::where('IDUsuario', $IDUsuario)
                ->whereNotIn('IDDepartamento', $IdsDepartamentos)
                ->delete();

            // 3️⃣ Insertar los nuevos (solo los que no existan)
            $existentes = RelUsuarioDepartamento::where('IDUsuario', $IDUsuario)
                ->pluck('IDDepartamento')
                ->toArray();

            $nuevos = collect($IdsDepartamentos)
                ->diff($existentes)
                ->map(fn($idDep) => [
                    'IDUsuario' => $IDUsuario,
                    'IDDepartamento' => $idDep,
                ])
                ->toArray();

            if (count($nuevos)) {
                RelUsuarioDepartamento::insert($nuevos);
            }

            return ApiResponse::success($requisicion, $message);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            Log::error("ERROR ~ RelUsuarioDepartamentoController ~ storeDepartamentos: $e->getMessage()");
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
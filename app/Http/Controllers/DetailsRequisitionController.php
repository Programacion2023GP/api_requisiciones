<?php

namespace App\Http\Controllers;

use App\Models\DetailRequisition;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetailsRequisitionController extends Controller
{
    public function create(int $id,String $cantidad, String $descripcion){
     try {
        $details = new DetailRequisition();
        $details->IDRequisicion = $id;
        $details->Cantidad = $cantidad;
        $details->Descripcion = $descripcion;
        $details->Ejercicio = date('Y');
        $details->save();
        Log::info("ADD: $details");
    } catch (Exception $e) {
        Log::error("Error $e: " . $e->getMessage());

     }
    }
}

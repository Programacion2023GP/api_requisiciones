<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Provedor;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProvedoresController extends Controller
{
    public function index()
    {
        // URL de la API Node.js
        // $url = 'http://predial.gomezpalacio.gob.mx:3000/empresas'; // Cambia esto por la URL correcta de tu API Node.js

        try {
            //     // Hacer la solicitud GET a la API Node.js
            //     $response = Http::get($url);

            //     // Comprobar si la respuesta fue exitosa
            //     if (!$response->successful()) {
            //         return response()->json([
            //             'error' => 'No se pudo obtener la información de las empresas.',
            //             'message' => $response->body()
            //         ], $response->status());
            //     }

            //     // Obtener las empresas de la respuesta de la API Node.js
            //     $empresas = $response->json();

            $proveedores = Provedor::orderBy('IDProveedor', 'desc')->get();

            // $empresasConRfcCoincidente = [];

            // foreach ($proveedores as $proveedor) {
            //     $paso = 0;
            //     foreach ($empresas as $empresa) {

            //         if (isset($empresa['rfcPM']) && isset($empresa['fhVencimiento'])  && $empresa['rfcPM'] == $proveedor->RFC) {
            //             $proveedor->vigencia = ($empresa['fhVencimiento'] > date('Y-m-d')) ? "Certificado" : "Vencido";
            //             $paso = 1;
            //             array_push($empresasConRfcCoincidente, $proveedor);
            //             break; // Salir del bucle interno si hay coincidencia
            //         } elseif (isset($empresa['rfc']) && isset($empresa['fhVencimiento']) && $empresa['rfc'] == $proveedor->RFC) {
            //             $proveedor->vigencia = ($empresa['fhVencimiento'] > date('Y-m-d')) ? "Certificado" : "Vencido";
            //             $paso = 1;

            //             array_push($empresasConRfcCoincidente, $proveedor);
            //             break; // Salir del bucle interno si hay coincidencia
            //         }
            //     }
            //     // Si no se encontró coincidencia con ninguna empresa
            //     if ($paso == 0) {
            //         $proveedor->vigencia = $proveedor->Comprado == 0 ? "Nuevo" : "Vencido";
            //         array_push($empresasConRfcCoincidente, $proveedor);
            //     }
            // }


            // Verificar si se encontraron coincidencias
            // if (count($empresasConRfcCoincidente) > 0) {
            //     $empresasConRfcCoincidente = collect($empresasConRfcCoincidente)
            //         ->unique(function ($item) {
            //             return $item->RFC;
            //         })
            //         ->values();
            return response()->json([
                'data' => $proveedores
            ]);
            // } else {
            //     return response()->json([
            //         'message' => 'No se encontraron empresas con RFC coincidente'
            //     ]);
            // }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al recuperar las empresas o proveedores',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // public function index()
    // {
    //     try {
    //         $proveedores = Provedor::orderBy('IDProveedor', 'desc')->get();

    //         return ApiResponse::success($proveedores, 'provedores recuperados con éxito');
    //     } catch (Exception $e) {
    //         return ApiResponse::error('Error al recuperar los provedores', 500);
    //     }
    // }

    public function create(Request $request)
    {
        try {
            $proveedor = $request->IDProveedor > 0
                ? Provedor::where('IDProveedor', $request->IDProveedor)->first()
                : new Provedor();

            if (!$proveedor && $request->IDProveedor > 0) {
                return ApiResponse::error('Proveedor no encontrado', 404);
            }

            if ($request->IDProveedor == 0 && Provedor::where('EMail', $request->EMail)->exists()) {
                throw new Exception('Correo duplicado');
            }

            $proveedor->fill($request->only([
                'Nombre_RazonSocial',
                'ApPaterno',
                'ApMaterno',
                'RFC',
                'Telefono1',
                'Telefono2',
                'EMail'
            ]));

            $proveedor->FechaAlta = now();
            $proveedor->Usuario = Auth::user()->Usuario;
            $proveedor->FUM = now();
            $proveedor->UsuarioFUM = Auth::user()->Usuario;

            $proveedor->save();

            return ApiResponse::success($proveedor, 'Proveedor creado/actualizado con éxito');
        } catch (Exception $e) {
            if ($e->getMessage() == 'Correo duplicado') {
                return ApiResponse::error('Correo duplicado.', 400);
            }

            return ApiResponse::error('El provedor no se pudo crear. Intenta nuevamente.', 400);
        }
    }
}

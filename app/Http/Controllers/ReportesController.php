<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    /**
     * Relación de gastos: requisiciones en ORDEN DE COMPRA (OC) y SURTIDAS (SU)
     * Filtros: IDDepartamento (opcional), Ejercicio (opcional)
     * Importe = Cantidad x PrecioUnitarioConIva del proveedor seleccionado
     */
    public function relacionGastos(Request $request)
    {
        try {
            // 1) Requisiciones en ORDEN DE COMPRA y SURTIDAS (misma vista que usa el PDF)
            $query = DB::table('requisiciones_view')
                ->whereIn('Status', ['OC', 'SU'])
                ->select('Id', 'IDRequisicion', 'Ejercicio', 'Status', 'Nombre_Departamento', 'Observaciones')
                ->orderBy('Ejercicio')
                ->orderBy('IDRequisicion');

            if ($request->filled('IDDepartamento')) {
                $query->where('IDDepartamento', $request->IDDepartamento);
            }
            if ($request->filled('Ejercicio')) {
                $query->where('Ejercicio', $request->Ejercicio);
            }
            $requisiciones = $query->get();

            if ($requisiciones->isEmpty()) {
                return ApiResponse::success([], 'Relación de gastos obtenida con éxito');
            }

            // 2) Productos (misma vista que usa el PDF), agrupados por requisición
            $productos = DB::table('products_details')
                ->whereIn('id', $requisiciones->pluck('Id')->all())
                ->get()
                ->groupBy(function ($p) {
                    return $p->id . '-' . $p->Ejercicio;
                });

            // 3) Total Neto del proveedor seleccionado, igual que el PDF:
            //    (PrecioUnitarioSinIva x Cantidad) + IVA - Retenciones
            $resultado = $requisiciones->map(function ($r) use ($productos) {
                $items = $productos->get($r->Id . '-' . $r->Ejercicio, collect());
                $total = 0;
                $descripciones = [];

                foreach ($items as $p) {
                    $descripciones[] = trim(($p->Cantidad ?? '') . ' - ' . ($p->Descripcion ?? ''));

                    if ($p->Proveedor === null || $p->Proveedor === '') {
                        continue;
                    }
                    for ($i = 1; $i <= 3; $i++) {
                        if ((string) $p->Proveedor === (string) ($p->{"Proveedor$i"} ?? '')) {
                            $cantidad = (float) ($p->Cantidad ?? 0);
                            $subtotal = (float) ($p->{"PrecioUnitarioSinIva$i"} ?? 0) * $cantidad;
                            $iva = $subtotal * ((float) ($p->{"PorcentajeIVA$i"} ?? 0) / 100);
                            $total += $subtotal + $iva - (float) ($p->{"Retenciones$i"} ?? 0);
                            break;
                        }
                    }
                }

                return [
                    'Id' => $r->Id,
                    'IDRequisicion' => $r->IDRequisicion,
                    'Ejercicio' => $r->Ejercicio,
                    'Status' => $r->Status,
                    'Nombre_Departamento' => $r->Nombre_Departamento,
                    'Concepto' => $r->Observaciones,
                    'Descripcion' => implode(' | ', $descripciones),
                    'Importe' => round($total, 2),
                ];
            })->values();

            return ApiResponse::success($resultado, 'Relación de gastos obtenida con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Datos para el PDF de una requisición (mismo formato que usa el módulo de requisiciones)
     */
    public function requisicionPdf(Request $request)
    {
        try {
            $pdfData = DB::table('requisiciones_view')
                ->where('Ejercicio', $request->Ejercicio)
                ->where('IDRequisicion', $request->IDRequisicion)
                ->first();
            $products = DB::table('products_details')
                ->where('Ejercicio', $request->Ejercicio)
                ->where('id', $request->Id)
                ->get();
            return ApiResponse::success(['pdfData' => $pdfData, 'products' => $products], 'PDF obtenido con éxito');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\RelUsuarioDepartamento;
use App\Models\Requisiciones;
use App\Models\Tipos;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class   RequisicionesController extends Controller
{
    public function create(Request $request)
    {
        DB::beginTransaction(); // Inicia la transacción
        $message = "Requisicion creada con exito";
        $update = false;
        try {
            $requisicion = Requisiciones::find($request->Id);
            $detailsRequisitionController = new DetailsRequisitionController();

            if (!$requisicion) {
                // Actualizar usuario   
                $requisicion = new Requisiciones();
                $folio = Requisiciones::where('Ejercicio', date('Y'))->max('IDRequisicion') ?? 0;
                $requisicion->IDRequisicion = $folio + 1;
                $requisicion->Status = "CP";
                $requisicion->UsuarioCaptura = Auth::user()->Usuario;
            } else {
                $message = "Requisicion actualizada con exito";
                $update = true;
                // $detailsRequisitionController->delete($requisicion->IDRequisicion, $requisicion->Ejercicio);
            }
            $centro_costo = Departamento::where('IDDepartamento', $request->IDDepartamento)->first();

            $requisicion->Ejercicio = date('Y');
            //centro de costo
            $requisicion->FechaCaptura = $request->FechaCaptura;
            $requisicion->FechaAutorizacion = $request->FechaAutorizacion;
            $requisicion->FechaAsignacion = $request->FechaAsignacion;
            $requisicion->FechaCotizacion = $request->FechaCotizacion;
            $requisicion->FechaOrdenCompra = $request->FechaOrdenCompra;

            // $requisicion->FechaCaptura = date('Y-m-d H:i:s');
            $requisicion->FUM = date('Y-m-d H:i:s');

            $requisicion->UsuarioCa = Auth::user()->Usuario;
            $requisicion->IDDepartamento = Auth::user()->Rol == "CAPTURA" ? Auth::user()->IDDepartamento :  $request->IDDepartamento;
            $requisicion->Solicitante = $request->Solicitante;
            $requisicion->Observaciones = $request->Observaciones;
            $requisicion->IDTipo = $request->IDTipo;

            $requisicion->centro_costo = Auth::user()->Rol == "CAPTURA"
                ? Departamento::select('Centro_Costo')->where('IDDepartamento', Auth::user()->IDDepartamento)->first()->Centro_Costo
                : $centro_costo->Centro_Costo;

            $tipo = Tipos::where('IDTipo', $request->IDTipo)->first();
            // 
            $requisicion->AutEspecial = $tipo->RequiereAut;



            $requisicion->save();
            $datos = $request->all();

            foreach ($datos as $key => $valor) {
                if (strpos($key, 'Descripcion') === 0) {
                    // Log::info("Clave de descripción encontrada: $key");

                    $index = substr($key, 11); // Obtener índice numérico
                    $cantidadKey = 'Cantidad' . $index;
                    $iDDetalleKey = 'IDDetalle' . $index;
                    // $iDDetalleKey = 'IDDetalle' . $index;

                    if ($request->has($cantidadKey)) {
                        $cantidad = $request->input($cantidadKey);
                        if ($update) {
                            // if ($update && $request->filled($iDDetalleKey) && $cantidad) {
                            //     $idDetalle = $request->input($iDDetalleKey);
                            //     $detailsRequisitionController->update($idDetalle, $cantidad, $valor);
                            // } else {
                            //     $detailsRequisitionController->create($requisicion->IDRequisicion, $cantidad, $valor);
                            // }
                        } else {
                            $cant = $request->input($cantidadKey);

                            $detailsRequisitionController->create($requisicion->IDRequisicion, $cant, $valor);
                        }
                    } else {
                        Log::warning("Clave de cantidad no encontrada: $cantidadKey");
                    }
                }
            }



            DB::commit(); // Confirma la transacción

            return ApiResponse::success($requisicion, $message);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            // Log::error($e->getMessage()); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function asignedAutorized(Request $request)
    {
        try {
            //code...
            $requisicion = Requisiciones::where("IDRequisicion", $request->id)->first(); // Cambiar get() por first()
            if (!$requisicion) {
                return ApiResponse::error("Requisición no encontrada", 404);
            } else {
                // Asignar valores
                $requisicion->UsuarioAS = $request->Usuario;
                $requisicion->FechaAsignacion = now();
                $requisicion->Status = "AS";

                // Actualizar el registro
                $requisicion->save(); // Usar save() en lugar de update()
                return $requisicion;
                return ApiResponse::success($requisicion, 'Requisición asignada con éxito');
            }
        } catch (Exception $e) {
            // Log::error($e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        try {
            ini_set('memory_limit', '2048M'); // O cualquier valor mayor

            // Verificar si se ha pasado una consulta SQL
            $consulta  = $request->sql;
            if ($request->filled('sql')) {
                if (Auth::user()->Rol == 'CAPTURA' || Auth::user()->Rol == 'DIRECTOR') {
                    $departamentoID = Auth::user()->IDDepartamento;

                    // Obtener departamentos relacionados
                    $departamentosDire = RelUsuarioDepartamento::where("IDUsuario", Auth::user()->IDUsuario)
                        ->pluck("IDDepartamento")
                        ->toArray();

                    // Asegurar que haya al menos un departamento
                    if (!empty($departamentosDire)) {
                        $ids = implode(',', $departamentosDire);
                        $consulta .= ' AND IDDepartamento IN (' . $ids . ')';
                    }

                    // Guardar estado previo de la consulta
                    $consultaPrev = $consulta;

                    // Consultar permiso VoBo una sola vez
                    $usuarioVobo = DB::table('relmenuusuario')
                        ->where('Usuario', Auth::user()->Usuario)
                        ->where('IdMenu', "VoBo")
                        ->first();

                    // Solo si tiene permiso 'S' se consideran los casos especiales
                    if (!is_null($usuarioVobo) && $usuarioVobo->Permiso == "S") {
                        switch ($departamentoID) {
                            case 84: // Taller Municipal
                                $consulta .= ' OR IDTipo = 5';
                                break;

                            case 83: // Servicios Generales
                                $consulta .= ' OR IDTipo = 7';
                                break;

                            case 27: // Informática
                                $consulta .= ' OR IDTipo = 6';
                                break;

                            default:
                                // No agregar nada
                                break;
                        }
                    }
                }

                if (Auth::user()->Rol == 'REQUISITOR') {
                    $consulta .= " AND UsuarioAS = '" . Auth::user()->Usuario . "'";
                }
                // Si el rol es 'DIRECTOR', agregamos condiciones adicionales
                // if (Auth::user()->Rol == 'DIRECTOR') {
                //     $consultaPrev = $consulta;

                //     // Definir condiciones basadas en el departamento
                //     $departamentoID = Auth::user()->IDDepartamento;
                //     $departamentosDire = RelUsuarioDepartamento::where("IDUsuario", Auth::user()->IDUsuario)
                //         ->pluck("IDDepartamento")
                //         ->toArray(); // convertir a array simple

                //     $ids = implode(',', $departamentosDire); // "1,2,3"

                //     $consulta = $consulta . ' AND IDDepartamento IN (' . $ids . ')';


                //     // Añadir condiciones específicas según el departamento
                //     switch ($departamentoID) {
                //         case 84: // Taller Municipal
                //             $usuarioVobo = DB::table('relmenuusuario')->where('Usuario', Auth::user()->Usuario)->where('IdMenu', "VoBo")->first();
                //             if (!isEmpty($usuarioVobo)) {
                //                 # code...
                //                 if ($usuarioVobo->Permiso == "S") {
                //                     $consulta = $consultaPrev . ' OR IDTipo = 5 ';
                //                 }
                //             }

                //             break;
                //         case 83: // Servicios Generales
                //             $usuarioVobo = DB::table('relmenuusuario')->where('Usuario', Auth::user()->Usuario)->where('IdMenu', "VoBo")->first();
                //             if (!isEmpty($usuarioVobo)) {

                //                 if ($usuarioVobo->Permiso == "S") {
                //                     $consulta = $consultaPrev . ' OR IDTipo = 7 ';
                //                 }
                //             }
                //             break;
                //         case 27: // Informática
                //             $usuarioVobo = DB::table('relmenuusuario')->where('Usuario', Auth::user()->Usuario)->where('IdMenu', "VoBo")->first();
                //             $consultaPrev = $consulta;
                //             if (!isEmpty($usuarioVobo)) {

                //                 if ($usuarioVobo->Permiso == "S") {
                //                     $consulta = $consultaPrev . ' OR IDTipo = 6 ';
                //                 }
                //             }
                //             break;
                //         default:

                //             break;
                //     }
                // }

                // Ejecutar la consulta SQL
                $sql = DB::raw($consulta);


                $query = DB::table('requisiciones_view')->distinct()->whereRaw($sql)->orderBy('Id', 'desc');
            } else {
                // Si no se pasa una consulta SQL personalizada, se obtienen todas las requisiciones
                $query = DB::table('requisiciones_view')->distinct();
            }
            $requisiciones = $query
                ->get()
                ->unique(function ($item) {
                    return $item->IDRequisicion . '-' . $item->Ejercicio;
                })
                ->values(); // Reindexa los datos
            // Devuelve la respuesta en formato JSON
            return ApiResponse::success($requisiciones, 'Lista de requisiciones obtenida con éxito');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request)
    {
        try {
            // Buscar la requisición por su ID
            $requisicion = Requisiciones::find($request->id);

            // Si no se encuentra la requisición, retornar un error
            if (!$requisicion) {
                return ApiResponse::error("Requisición no encontrada", 404);
            }

            // Actualizar la requisición según el estado
            switch ($request->Status) {
                case "AU":
                    $requisicion->UsuarioAU = Auth::user()->Usuario;
                    $requisicion->FechaAutorizacion = now(); // Usar función nativa para la fecha actual
                    $requisicion->Director = Auth::user()->NombreCompleto;

                    $requisicion->FechaVoBo = now();
                    break;
                case "AS":
                    $requisicion->UsuarioAS = Auth::user()->Usuario;
                    $requisicion->FechaAsignacion = now();
                    break;
                case "RE":
                    $requisicion->UsuarioRE = Auth::user()->Usuario;
                    $requisicion->FechaRealizacion = now();
                    break;
                case "OC":
                    $requisicion->UsuarioOC = Auth::user()->Usuario;
                    $requisicion->FechaOrdenCompra = now();
                    // $condition = DetailRequisition::where('Ejercicio', $requisicion->Ejercicio)
                    //     ->where('IDRequisicion', $requisicion->IDRequisicion)
                    //     ->whereNull('Proveedor')

                    //     ->first();
                    // if ($condition) {
                    //     throw new Exception('No se puede avanzar porque no se han asignado provedor a todos los productos');
                    // }

                    break;
                case "CO":
                    $requisicion->UsuarioCO = Auth::user()->Usuario;
                    $requisicion->FechaCotizacion = now();
                    // $condition = DetailRequisition::where('Ejercicio', $requisicion->Ejercicio)
                    //     ->where('IDRequisicion', $requisicion->IDRequisicion)
                    //     ->whereNull('IDproveedor1')
                    //     ->whereNull('IDproveedor2')
                    //     ->whereNull('IDproveedor3')
                    //     ->first();
                    // if ($condition) {
                    //     throw new Exception('No se puede avanzar porque no se han cotizado todos los productos');
                    // }
                    break;
                case "SU":
                    $requisicion->Orden_Compra =  $request->ClavePresupuestal;



                    break;
                case "CA":
                    $requisicion->Motivo_Cancelacion =  $request->Motivo_Cancelacion;

                    break;
                default:
                    return ApiResponse::error("Estado inválido", 400);
            }

            // Guardar los cambios en la base de datos
            $requisicion->Status =  $request->Status;
            $requisicion->update();

            return ApiResponse::success($requisicion, 'Requisición actualizada con éxito');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error
            if ($e->getMessage() == 'No se puede avanzar porque no se han cotizado todos los productos') {
                return ApiResponse::error($e->getMessage(), 500);
            }
            if ($e->getMessage() == 'No se puede avanzar porque no se han asignado provedor a todos los productos') {
                return ApiResponse::error($e->getMessage(), 500);
            }

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function changestatus(Request $request)
    {
        try {
            // Buscar la requisición por su ID
            $requisicion = Requisiciones::find($request->id);
            // Si no se encuentra la requisición, retornar un error
            if (!$requisicion) {
                return ApiResponse::error("Requisición no encontrada", 404);
            }

            // Actualizar la requisición según el estado
            switch ($request->status) {
                case "CP":
                    $requisicion->FechaCaptura = null;
                    $requisicion->FechaAutorizacion = null;
                    $requisicion->FechaAsignacion = null;
                    $requisicion->FechaCotizacion = null;
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->FechaVoBo = null;

                    $requisicion->UsuarioAU = null;
                    $requisicion->FechaVoBo = null;
                    $requisicion->UsuarioAS = null;
                    $requisicion->UsuarioCO = null;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioRE = null;

                    $requisicion->UsuarioVoBo = null;

                    $requisicion->Status = $request->status;

                case "AU":
                    $requisicion->FechaAutorizacion = null;

                    $requisicion->FechaAsignacion = null;
                    $requisicion->FechaCotizacion = null;
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->FechaVoBo = null;

                    $requisicion->UsuarioAU = null;
                    $requisicion->UsuarioAS = null;
                    $requisicion->UsuarioCO = null;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioRE = null;
                    $requisicion->UsuarioVoBo = null;
                    $requisicion->Status = $request->status;
                    break;
                case "VoBo":
                    $requisicion->FechaVoBo = null;
                    $requisicion->FechaCotizacion = null;
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;

                    $requisicion->FechaVoBo = null;
                    $requisicion->UsuarioAS = null;
                    $requisicion->UsuarioCO = null;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioCA = null;
                    $requisicion->UsuarioVoBo = null;
                    break;
                case "AS":
                    $requisicion->UsuarioAS = null;
                    $requisicion->FechaVoBo = null;
                    $requisicion->FechaCotizacion = null;
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->Status = $request->status;
                    $requisicion->FechaVoBo = null;
                    $requisicion->UsuarioAS = null;
                    $requisicion->UsuarioCO = null;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioCA = null;
                    $requisicion->UsuarioVoBo = null;
                    break;
                case "CO":
                    $requisicion->FechaCotizacion = null;
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->Status = $request->status;
                    $requisicion->UsuarioCO = null;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioCA = null;
                    break;
                case "OC":
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->Status = $request->status;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioCA = null;
                    break;
                case "SU":
                    $requisicion->FechaOrdenCompra = null;
                    $requisicion->FechaCancelacion = null;
                    $requisicion->Status = $request->status;
                    $requisicion->UsuarioOC = null;
                    $requisicion->UsuarioCA = null;



                    break;
                case "CA":
                    $requisicion->Status = $request->status;
                    break;
                default:
            }


            // Guardar los cambios en la base de datos
            $requisicion->update();

            return ApiResponse::success($requisicion, 'Requisición actualizada con éxito');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error
            if ($e->getMessage() == 'No se puede avanzar porque no se han cotizado todos los productos') {
                return ApiResponse::error($e->getMessage(), 500);
            }
            if ($e->getMessage() == 'No se puede avanzar porque no se han asignado provedor a todos los productos') {
                return ApiResponse::error($e->getMessage(), 500);
            }

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function vobo(Request $request)
    {
        try {
            $requisicion = Requisiciones::find($request->id);

            if (!$requisicion) {
                return ApiResponse::error("Requisición no encontrada", 404);
            }

            $requisicion->UsuarioVoBo = Auth::user()->Usuario;
            $requisicion->FechaVoBo = now();

            $requisicion->update();

            return ApiResponse::success($requisicion, 'Requisición VoBo actualizada con éxito');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return ApiResponse::error("La requisición no se pudo actualizar", 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $requisicion = DB::table('requisiciones_view')->where('Ejercicio', $request->Ejercicio)->where('IDRequisicion', $request->IDRequisicion)->first();
            $details = DB::table('det_requisicion')->where('Ejercicio', $request->Ejercicio)->where('IDRequisicion', $request->IDRequisicion)->get();
            return ApiResponse::success(["requisicion" => $requisicion, "details" => $details], 'Productos obtenidos con éxito');
        } catch (Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }

    public function showRequisicion(Request $request)
    {
        try {
            $requisicion = Requisiciones::join('det_requisicion', function ($join) {
                $join->on('det_requisicion.Ejercicio', '=', 'requisiciones.Ejercicio')
                    ->on('det_requisicion.IDRequisicion', '=', 'requisiciones.IDRequisicion');
            })->where('requisiciones.Id', $request->Id)
                ->get();
            return ApiResponse::success($requisicion, 'Requisición obtenida con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudo obtener la requisición", 500);
        }
    }
    public function detailsRequisicion(Request $request)
    {
        try {
            $products = DB::table('products_details')->where('Ejercicio', $request->Ejercicio)->where('IDRequisicion', $request->IDRequisicion)->get();

            return ApiResponse::success($products, 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }


    public function products(Request $request)
    {
        try {
            $products = DB::table('det_requisicion as d')
                ->join('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.IDRequisicion', '=', 'd.IDRequisicion');
                })
                ->select(
                    'd.*',
                    'r.ObservacionesCot'
                )
                ->where([
                    ['d.Ejercicio', '=', $request->Ejercicio],
                    ['d.IDRequisicion', '=', $request->IDRequisicion]
                ])
                ->orderByDesc('d.IDDetalle') // Más claro y expresivo
                ->get();


            return ApiResponse::success($products, 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }

    public function productsWithIndex(Request $request)
    {
        try {
            // Parámetros obligatorios
            $ejercicio = $request->Ejercicio ?? null;
            $idRequisicion = $request->IDRequisicion ?? null;
            if (!$ejercicio || !$idRequisicion) {
                return ApiResponse::validationError('Parámetros obligatorios: Ejercicio y IDRequisicion', [], 422);
            }

            // Construir query base (manteniendo joins y selects)
            $baseQuery = DB::table('det_requisicion as d')
                ->leftJoin('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.IDRequisicion', '=', 'd.IDRequisicion');
                })
                ->select('d.*', 'r.ObservacionesCot')
                ->where('d.Ejercicio', $ejercicio)
                ->where('d.IDRequisicion', $idRequisicion);

            // Modo streaming opcional (NDJSON) para que el front procese incrementalmente
            if ($request->boolean('stream')) {
                $streamQuery = DB::table('det_requisicion as d')
                    ->leftJoin('requisiciones as r', function ($join) {
                        $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                            ->on('r.IDRequisicion', '=', 'd.IDRequisicion');
                    })
                    ->select('d.*', 'r.ObservacionesCot')
                    ->where('d.Ejercicio', $ejercicio)
                    ->where('d.IDRequisicion', $idRequisicion)
                    ->orderBy('d.IDDetalle', 'desc');

                $callback = function () use ($streamQuery) {
                    foreach ($streamQuery->cursor() as $row) {
                        echo json_encode($row) . "\n"; // NDJSON
                        flush();
                    }
                };

                return response()->stream($callback, 200, ['Content-Type' => 'application/x-ndjson']);
            }

            // Paginación por defecto: evita grandes cargas en memoria y en la red
            $perPage = intval($request->per_page ?? 100);
            $page = max(1, intval($request->page ?? 1));
            if ($perPage <= 0) {
                $perPage = 100;
            }

            $total = $baseQuery->count();

            $items = $baseQuery->orderBy('d.IDDetalle', 'desc')
                ->forPage($page, $perPage)
                ->get();

            $meta = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ];

            return ApiResponse::success(['items' => $items, 'meta' => $meta], 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }

    public function productsPlainText(Request $request)
    {
        try {
            $products = DB::table('det_requisicion as d')
                ->leftJoin('requisiciones as r', function ($join) {
                    $join->on('r.Ejercicio', '=', 'd.Ejercicio')
                        ->on('r.IDRequisicion', '=', 'd.IDRequisicion');
                })
                ->select(
                    'd.*',
                    'r.ObservacionesCot'
                )
                ->where('d.Ejercicio', $request->Ejercicio)
                ->where('d.IDRequisicion', $request->IDRequisicion)
                ->orderBy('d.IDDetalle', 'desc') // Orden descendente por IDDetalle
                ->get();

            // Convierte el resultado a texto plano (ejemplo: concatenando campos principales)
            // Convierte cada producto en "clave=valor" separado por |
            $textoPlano = $products->map(function ($p) {
                return collect((array) $p)
                    ->map(fn($v, $k) => "$k:$v")
                    ->implode(" | ");
            })->implode("\n"); // separa cada producto por salto de línea

            return ApiResponse::success($textoPlano, 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }
}
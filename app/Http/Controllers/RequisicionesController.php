<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\Requisiciones;
use App\Models\Tipos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class   RequisicionesController extends Controller
{
    public function create(Request $request)
    {
        DB::beginTransaction(); // Inicia la transacción

        try {
            $requisicion = Requisiciones::find($request->Usuario);

            if (!$requisicion) {
                // Actualizar usuario   
                $requisicion = new Requisiciones();
                $folio = Requisiciones::where('Ejercicio', date('Y'))->max('IDRequisicion') ?? 0;
                $requisicion->IDRequisicion = $folio + 1;
            }
            $centro_costo = Departamento::where('IDDepartamento', $request->IDDepartamento)->first();

            $requisicion->Ejercicio = date('Y');
            //centro de costo
            $requisicion->FechaCaptura = date('Y-m-d H:i:s');
            $requisicion->FUM = date('Y-m-d H:i:s');

            $requisicion->UsuarioCaptura = Auth::user()->Usuario;
            $requisicion->UsuarioCa = Auth::user()->Usuario;
            $requisicion->IDDepartamento = $request->IDDepartamento;
            $requisicion->Solicitante = $request->Solicitante;
            $requisicion->Observaciones = $request->Observaciones;
            $requisicion->IDTipo = $request->IDTipo;
            $requisicion->Status = "CP";
            $requisicion->centro_costo = $centro_costo->Centro_Costo;
            $tipo = Tipos::where('IDTipo', $request->IDTipo)->first();
            // 
            $requisicion->AutEspecial = $tipo->RequiereAut;



            $requisicion->save();
            $datos = $request->all();

            foreach ($datos as $key => $valor) {
                if (strpos($key, 'Descripcion') === 0) {
                    Log::info("Clave de descripción encontrada: $key");

                    $index = substr($key, 11); // Obtener índice numérico
                    $cantidadKey = 'Cantidad' . $index;

                    if ($request->has($cantidadKey)) {
                        $cantidad = $request->input($cantidadKey);
                        Log::info("Requisicion aqui $requisicion");

                        $detailsRequisitionController = new DetailsRequisitionController();
                        $detailsRequisitionController->create($requisicion->IDRequisicion, $cantidad, $valor);
                    } else {
                        Log::warning("Clave de cantidad no encontrada: $cantidadKey");
                    }
                }
            }



            DB::commit(); // Confirma la transacción

            return ApiResponse::success($requisicion, 'Requisición creada con exito');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios si hay un error

            // Log::error($e->getMessage()); // Mejor usar Log::error() para registrar errores
            return ApiResponse::error("La requisición no se pudo crear", 500);
        }
    }
    public function asignedAutorized(Request $request)
    {
        try {
            //code...
            $requisicion = Requisiciones::where("Id", $request->id)->first(); // Cambiar get() por first()
            if (!$requisicion) {
                return ApiResponse::error("Requisición no encontrada", 404);
            } else {
                // Asignar valores
                $requisicion->UsuarioAS = $request->Usuario;
                $requisicion->FechaAsignacion = now();
                $requisicion->Status = "AS";

                // Actualizar el registro
                $requisicion->save(); // Usar save() en lugar de update()

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
            if ($request->filled('sql')) {
                if (Auth::user()->Rol == 'CAPTURA') {
                    return;
                }
                $consulta  = $request->sql;
                if (Auth::user()->Rol == 'REQUISITOR') {
                    $consulta .= " AND UsuarioAS = '" . Auth::user()->Usuario . "'";
                }
                // Si el rol es 'DIRECTOR', agregamos condiciones adicionales
                if (Auth::user()->Rol == 'DIRECTOR') {
                    $consultaPrev = $consulta;

                    // Definir condiciones basadas en el departamento
                    $departamentoID = Auth::user()->IDDepartamento;
                    $consulta = $consulta . ' AND IDDepartamento = ' . $departamentoID;

                    // Añadir condiciones específicas según el departamento
                    switch ($departamentoID) {
                        case 84: // Taller Municipal
                            $consulta = $consultaPrev . ' AND IDTipo = 5 OR ' . $consulta;
                            break;
                        case 83: // Servicios Generales
                            $consulta = $consultaPrev . ' AND IDTipo = 7 OR ' . $consulta;
                            break;
                        case 27: // Informática
                            $consulta = $consultaPrev . ' AND IDTipo = 6 OR ' . $consulta;
                            break;
                        default:
                            break;
                    }
                }

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
                    break;
                case "CO":
                    $requisicion->UsuarioCO = Auth::user()->Usuario;
                    $requisicion->FechaCotizacion = now();
                    break;
                case "SU":

                    break;
                    case "CA":

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
            Log::error($e->getMessage()); // Registrar el mensaje de error
            return ApiResponse::error("La requisición no se pudo actualizar", 500);
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
             return ApiResponse::success(["requisicion"=>$requisicion,"details"=>$details], 'Productos obtenidos con éxito');

        } catch (Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);

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
            $products = DB::table('det_requisicion')->where('Ejercicio', $request->Ejercicio)->where('IDRequisicion', $request->IDRequisicion)->get();
            return ApiResponse::success($products, 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }
}

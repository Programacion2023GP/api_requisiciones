<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Departamento;
use App\Models\Requisiciones;
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
            $requisicion->AutEspecial =  $request->IDTipo == 5 || $request->IDTipo == 6 || $request->IDTipo == 7 ? 1 : 0;

            $requisicion->IDDepartamento = $request->IDDepartamento;
            $requisicion->Solicitante = $request->Solicitante;
            $requisicion->Observaciones = $request->Observaciones;
            $requisicion->IDTipo = $request->IDTipo;
            $requisicion->Status = "CA";
            $requisicion->centro_costo = $centro_costo->Centro_Costo;



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
    public function asignedAutorized(Request $request){
        try {
            //code...
            $requisicion = Requisiciones::where("Id", $request->id)->first(); // Cambiar get() por first()
            if(!$requisicion){
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
            if ($request->filled('sql')) { // Usar filled() para asegurar que el parámetro no esté vacío
                // Escapar la consulta SQL para evitar inyecciones SQL
                $sql = DB::raw($request->sql); // Escapa los caracteres especiales de la consulta

                // Intentar ejecutar la consulta
                $query = DB::table('requisiciones_view')->distinct()->whereRaw($sql);

                // Imprimir la consulta SQL para ver qué hace
                Log::info('Consulta SQL: ' . $query->toSql());

                $requisiciones = $query->get();

                if (!$requisiciones) {
                    throw new \Exception('No se pudieron obtener las requisiciones');
                }
            } else {
                $requisiciones = DB::table('requisiciones_view')->distinct()->get();
            }

            // Realiza la consulta con paginación
            // ...

            // Devuelve la respuesta en formato JSON
            return ApiResponse::success($requisiciones, 'Lista de requisiciones obtenida con éxito');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error("No se pudieron obtener las requisiciones", 500);
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

    public function show(Request $request)
    {
        try {
        } catch (Exception $e) {
        }
    }
    public function products (Request $request){
        try {
            $products = DB::table('det_requisicion')->where('Ejercicio',$request->Ejercicio)->where('IDRequisicion',$request->IDRequisicion)->get();
            return ApiResponse::success($products, 'Productos obtenidos con éxito');
        } catch (\Exception $e) {
            return ApiResponse::error("No se pudieron obtener los productos", 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Menu;
use App\Models\MenuUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuUserController extends Controller
{
    public function index(string $id = null)
    {
        try {
            $permisos = DB::select("
          

	SELECT 
    cm.Id,
    cm.IdMenu,
        cm.Menu,
        cm.MenuPadre,
        cm.Icon,

    CASE 
        WHEN rmu.Permiso='S' THEN 1
        ELSE 0
    END AS EstadoPermiso
FROM 
    cat_menus cm
LEFT JOIN 
    relmenuusuario rmu ON rmu.IdMenu = cm.IdMenu AND rmu.Usuario = ?
WHERE cm.active =1
ORDER BY 
    cm.IdMenu;

 

        ", [$id ? $id : Auth::user()->Usuario]);

            return ApiResponse::success($permisos, 'Menu recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar el Menu', 500);
        }
    }
    public function create(Request $request, string $id)
    {
        try {
            // Suponiendo que los datos del JSON están en el cuerpo de la solicitud
            MenuUser::where('Usuario', $id)->delete();

            $jsonData = $request->all();

            foreach ($jsonData as $key => $value) {
                $usuario = new MenuUser();
                $usuario->Usuario = $id;
                $usuario->IdMenu = $key;
                $usuario->Permiso = $value == 1 ? 'S' : 'N';
                $usuario->save();
            }

            return ApiResponse::success("", 'Se han asignado los permisos del menu al usuario');
        } catch (Exception $e) {
            return ApiResponse::error('Error al registrar los permisos', 500);
        }
    }
}

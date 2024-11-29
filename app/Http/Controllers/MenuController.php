<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Menu;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(string $id)
    {
        try {
            $permisos = DB::select("
            SELECT 
                cm.Id,
                cm.IdMenu,
                cm.Menu,
                cm.MenuPadre,
                CASE 
                    WHEN rmu.Usuario IS NOT NULL THEN 1
                    ELSE 0
                END AS State
            FROM 
                cat_menus cm
            LEFT JOIN 
                relmenuusuario rmu ON rmu.IdMenu = cm.IdMenu AND rmu.Usuario = ?
            ORDER BY 
                cm.IdMenu
        ", [$id]);

            return ApiResponse::success($permisos, 'Menu recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar el Menu', 500);
        }
    }
}

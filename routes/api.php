<?php

use App\Http\Controllers\AutorizadoresController;
use App\Http\Controllers\DepartamentosController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuUserController;
use App\Http\Controllers\RequisicionesController;
use App\Http\Controllers\TiposController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('/auth')->group(function () {
    Route::post('/login', [UsersController::class, 'login']);
    Route::post('/logout', [UsersController::class, 'logout'])->middleware('auth:sanctum');
});
Route::prefix('/menu')->group(function () {});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('/menuuser')->group(function () {
        Route::get('/index/{id?}', [MenuUserController::class, 'index']);
        Route::post('/create/{id}', [MenuUserController::class, 'create']);
    });
    Route::prefix('/departamentos')->group(function () {
        Route::get('/index', [DepartamentosController::class, 'index']);
    });
    Route::prefix('/tipos')->group(function () {
        Route::get('/index', [TiposController::class, 'index']);
    });
    Route::prefix('/users')->group(function () {
        Route::get('/index', [UsersController::class, 'index']);
        Route::post('/createOrUpdate/{id?}', [UsersController::class, 'createOrUpdate']);
        Route::delete('/delete/{id?}', [UsersController::class, 'ChangeStatus']);
    });
    Route::prefix('/requisiciones')->group(function () {
        Route::post('/create', [RequisicionesController::class, 'create']);
        Route::post('/index', [RequisicionesController::class, 'index']);
        Route::put('/update', [RequisicionesController::class, 'update']);
        Route::put('/vobo', [RequisicionesController::class, 'vobo']);
        Route::post('/show', [RequisicionesController::class, 'show']);
        Route::post('/products ', [RequisicionesController::class, 'products']);
        Route::post('/asignedAutorized ', [RequisicionesController::class, 'asignedAutorized']);

        
    });
    Route::prefix('/autorizadores')->group(function () {
        Route::get('/cotizadores', [AutorizadoresController::class, 'indexAutorizadores']);
     
    });
    

});
// http://127.0.0.1:8000/api/requisiciones/products

<?php

use App\Http\Controllers\DepartamentosController;
use App\Http\Controllers\MenuController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('/users')->group(function (){
    Route::get('/index', [UsersController::class, 'index']);
    Route::post('/createOrUpdate/{id?}', [UsersController::class, 'createOrUpdate']);
    Route::delete('/delete/{id?}', [UsersController::class, 'ChangeStatus']);

    
});
Route::prefix('/auth')->group(function (){
    Route::post('/login', [UsersController::class, 'login']);
    Route::post('/logout', [UsersController::class, 'logout'])->middleware('auth:sanctum');


});
Route::prefix('/departamentos')->group(function (){
    Route::get('/index', [DepartamentosController::class, 'index']);

});
Route::prefix('/menu')->group(function (){
    Route::get('/index/{id}', [MenuController::class, 'index']);

});
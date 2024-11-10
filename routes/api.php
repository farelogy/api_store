<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\MainController;

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

Route::post('/register',[MainController::class,'register']);
Route::post('/login',[MainController::class,'login']);
Route::get('/check-token',[MainController::class, 'check_token']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum'])->group(function () {
    //route untuk pengaturan cabang
    Route::post('/tambah_cabang', [MainController::class,'tambah_cabang']);
    Route::get('/data_cabang', [MainController::class,'data_cabang']);
    Route::post('/edit_cabang', [MainController::class,'edit_cabang']);
    Route::post('/delete_cabang', [MainController::class,'delete_cabang']);

    //route untuk pengaturan user
    Route::get('/data_user', [MainController::class,'data_user']);
    Route::get('/edit_user', [MainController::class,'edit_user']);
    Route::get('/delete_user', [MainController::class,'delete_user']);


    // Add more routes here
});



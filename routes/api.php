<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\MainController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\StokController;


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
    Route::post('/edit_user', [MainController::class,'edit_user']);
    Route::post('/delete_user', [MainController::class,'delete_user']);
    Route::post('/reset_password', [MainController::class,'reset_password']);
    Route::post('/reset_password_profile', [MainController::class,'reset_password']);


    //route untuk management barang
    Route::get('/data_barang', [BarangController::class,'data_barang']);
    Route::post('/add_barang',[BarangController::class, 'add_barang']);
    Route::post('/edit_barang',[BarangController::class, 'edit_barang']);
    Route::post('/delete_barang',[BarangController::class, 'delete_barang']);

    //route untuk stok barang
    Route::get('/data_stok_barang',[StokController::class,'data_stok_barang']);
    Route::post('/data_stok_barang_detail',[StokController::class,'data_stok_barang_detail']);
    Route::post('/add_stok_barang',[StokController::class,'add_stok_barang']);
    Route::post('/edit_stok_barang',[StokController::class,'edit_stok_barang']);
    Route::post('/delete_stok_barang',[StokController::class,'delete_stok_barang']);


    // Add more routes here
});



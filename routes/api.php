<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\MainController;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum', 'check.token.expiration'])->get('/check-token', function (Request $request) {
    $token = $request->access_token;
        
    if ($token) {
        $tokenData = PersonalAccessToken::findToken($token);

        if (!$tokenData || $tokenData->expires_at->lt(Carbon::now())) {
            return response()->json(['message' => 'Token is expired'], 401);
        }
    } else {
        return response()->json(['message' => 'Token not provided'], 401);
    }
    return response()->json(['message' => 'Token is valid'], 200);
});



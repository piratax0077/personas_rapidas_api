<?php

use App\Http\Controllers\PersonaRapidaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('personas-rapidas')->group(function () {
    Route::get('/buscar', [PersonaRapidaController::class, 'buscar'])->middleware('api.client:personas:buscar')
        ->name('personas-rapidas.buscar');

    Route::post('/guardar', [PersonaRapidaController::class, 'guardar'])->middleware('api.client:personas:guardar')
        ->name('personas-rapidas.guardar');
});

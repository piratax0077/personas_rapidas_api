<?php

use App\Http\Controllers\PersonaRapidaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('personas-rapidas.prueba');
});

Route::middleware(['auth.basic', 'throttle:60,1'])->group(function () {
    Route::get('/personas-rapidas/prueba', [PersonaRapidaController::class, 'index'])
        ->name('personas-rapidas.prueba');
});

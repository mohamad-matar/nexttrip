<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(AuthController::class)->group(function () {
    Route::post('register',   'register');
    Route::post('login',   'login');
    Route::post('logout',   'logout')->middleware('auth:sanctum');
});



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/trip/plan', [AIController::class, 'planTrip']);
});

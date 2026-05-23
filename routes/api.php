<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Tourist\GuideBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\GuideController ;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(AuthController::class)->group(function () {
    Route::post('register',   'register');
    Route::post('login',   'login');
    Route::post('logout',   'logout')->middleware('auth:sanctum');
});

Route::prefix('public')->group(function () {
    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/languages', [LanguageController::class, 'index']);
    Route::get('/guides', [GuideController::class, 'index']);
    Route::get('/guides/{guide}', [GuideController::class, 'show']);
});


Route::prefix('tourist')->middleware('auth:sanctum')->group(function () {  
        Route::get('/guide-bookings', [GuideBookingController::class, 'index']);
        Route::get('/guide-bookings/{booking}', [GuideBookingController::class, 'show']);
        Route::post('/guide-bookings/{guide}/book', [GuideBookingController::class, 'book']);
        Route::post('/guide-bookings/{booking}/cancel', [GuideBookingController::class, 'cancel']);
        Route::post('/guide-bookings/{booking}/review', [GuideBookingController::class, 'review']);
});

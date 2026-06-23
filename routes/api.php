<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\Guide\BookingController;
use App\Http\Controllers\Guide\ProfileController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Tourist\GuideBookingController as TouristGuideBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\GuideController;



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

Route::middleware(['auth:sanctum', 'role:tourist' ])
    ->prefix('tourist')
    ->group(function () {
        Route::get('/guide-bookings', [TouristGuideBookingController::class, 'index']);
        Route::get('/guide-bookings/{booking}', [TouristGuideBookingController::class, 'show']);
        Route::post('/guide-bookings/{guide}/book', [TouristGuideBookingController::class, 'book']);
        Route::post('/guide-bookings/{booking}/cancel', [TouristGuideBookingController::class, 'cancel']);
        Route::post('/guide-bookings/{booking}/review', [TouristGuideBookingController::class, 'review']);
    });

Route::middleware(['auth:sanctum', 'role:guide' ])
    ->prefix('guide')
    ->group(function () {
        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
            Route::get('', 'show');
            Route::post('',  'update');
        });

        Route::prefix('booking')->controller(BookingController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/{booking}/accept', 'accept');
            Route::post('/{booking}/reject',  'reject');
            Route::post('/{booking}/cancel',  'cancel');
        });
    });

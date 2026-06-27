<?php

use App\Http\Controllers\AuthController;

use App\Http\Controllers\Public\CityController;
use App\Http\Controllers\Public\GuideController;
use App\Http\Controllers\Public\LanguageController;

use App\Http\Controllers\Guide\GuideDashboardController;
use App\Http\Controllers\Guide\BookingController;
use App\Http\Controllers\Guide\ProfileController;
use App\Http\Controllers\Guide\ReviewController;

use App\Http\Controllers\Tourist\GuideBookingController as TouristGuideBookingController;
use App\Http\Controllers\Tourist\ReviewController as TouristReviewController;


use App\Http\Controllers\NotificationController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum', 'role:guide'])
    ->prefix('guide')
    ->group(function () {
        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
            Route::get('', 'show');
            Route::post('',  'update');
        });

        Route::prefix('bookings')->controller(BookingController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/{booking}/accept', 'accept');
            Route::post('/{booking}/reject',  'reject');
            Route::post('/{booking}/cancel',  'cancel');
        });

        Route::get('/reviews', [ReviewController::class, 'index']);

        Route::get('/dashboard', [GuideDashboardController::class, 'index']);
    });

Route::middleware(['auth:sanctum', 'role:tourist'])
    ->prefix('tourist')
    ->group(function () {
        Route::get('/guide-bookings', [TouristGuideBookingController::class, 'index']);
        Route::get('/guide-bookings/{booking}', [TouristGuideBookingController::class, 'show']);
        Route::post('/guide-bookings/{guide}/book', [TouristGuideBookingController::class, 'book']);
        Route::post('/guide-bookings/{booking}/cancel', [TouristGuideBookingController::class, 'cancel']);
        Route::post('/guide-bookings/{booking}/review', [TouristGuideBookingController::class, 'review']);

        Route::get('/reviews', [TouristReviewController::class, 'index']);
    });

//الاشعارات
Route::middleware('auth:sanctum')->group(function () {

    // كل الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index']);

    // الإشعارات الجديدة فقط
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);

    // عدد الإشعارات الجديدة
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // وضع علامة مقروء للجميع
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
});

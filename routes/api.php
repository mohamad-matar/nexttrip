<?php

use App\Http\Controllers\AuthController;

use App\Http\Controllers\Public\GuideController;
use App\Http\Controllers\Public\AiRecommendationController;
use App\Http\Controllers\Public\LookupController;
use App\Http\Controllers\Public\MapPlaceController;

use App\Http\Controllers\Guide\GuideDashboardController;
use App\Http\Controllers\Guide\BookingController;
use App\Http\Controllers\Guide\ProfileController;
use App\Http\Controllers\Guide\ReviewController;

use App\Http\Controllers\Tourist\GuideBookingController as TouristGuideBookingController;
use App\Http\Controllers\Tourist\ReviewController as TouristReviewController;
use App\Http\Controllers\Tourist\TripPlaceController;


use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\InterestController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AnalyticsController;

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SuggestedPlaceController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(AuthController::class)->group(function () {
    Route::post('register',   'register');
    Route::post('login',   'login');
    Route::post('logout',   'logout')->middleware('auth:sanctum');
    Route::get('me',   'me')->middleware('auth:sanctum');
});

Route::prefix('public')->group(function () {
    Route::get('/cities', [LookupController::class, 'cities']);
    Route::get('/languages', [LookupController::class, 'languages']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/interests', [InterestController::class, 'index']);
    
    Route::get('/guides', [GuideController::class, 'index']);
    Route::get('/guides/{guide}', [GuideController::class, 'show']);

    Route::get('/places', [MapPlaceController::class, 'index']);
    Route::get('/places/{place}', [MapPlaceController::class, 'show']);

    Route::get('/top-places', [LookupController::class, 'topPlaces']);

    Route::prefix('ai')->group(function () {
        Route::post('/nearby-recommendations', [AiRecommendationController::class, 'nearbyRecommendations']);
        Route::post('/smart-trip-planner', [AiRecommendationController::class, 'smartTripPlanner']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('cities', CityController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('places', PlaceController::class);
    Route::apiResource('languages', LanguageController::class);
    Route::apiResource('interests', InterestController::class);

    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/status', [UserController::class, 'changeStatus']);
    Route::patch('users/{user}/role', [UserController::class, 'makeAdmin']);

    Route::patch('suggested-places/{suggestedPlace}/review', [SuggestedPlaceController::class, 'review']);

    Route::get('analytics', [AnalyticsController::class, 'index']);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
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

        Route::get('/trips', [TripPlaceController::class, 'trips']);
        Route::post('/trips/{trip}/places', [TripPlaceController::class, 'store']);
    });

Route::middleware('auth:sanctum')->group(function () {
    //الاشعارات
    // كل الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index']);

    // الإشعارات الجديدة فقط
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);

    // عدد الإشعارات الجديدة
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // وضع علامة مقروء للجميع
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // suggested places
    Route::get('suggested-places', [SuggestedPlaceController::class, 'index']);
    Route::get('suggested-places/{suggestedPlace}', [SuggestedPlaceController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:guide,tourist'])->group(function () {
    Route::post('suggested-places', [SuggestedPlaceController::class, 'store']);
    Route::delete('suggested-places/{suggestedPlace}', [SuggestedPlaceController::class, 'destroy']);
});

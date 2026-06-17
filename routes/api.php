<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Auth\AuthController;

// Public Controllers
use App\Http\Controllers\Public\FieldController as PublicFieldController;
use App\Http\Controllers\Public\PromotionController as PublicPromotionController;

// User Controllers
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\BookingController as UserBookingController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FieldTypeController;
use App\Http\Controllers\Admin\FieldController as AdminFieldController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReportController;

// ============================================================
// PUBLIC ROUTES — Tidak perlu login
// ============================================================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

Route::prefix('fields')->group(function () {
    Route::get('/',                        [PublicFieldController::class, 'index']);
    Route::get('/{field}',                 [PublicFieldController::class, 'show']);
    Route::get('/{field}/availability',    [PublicFieldController::class, 'availability']);
});

Route::get('field-types',                  [FieldTypeController::class, 'index']);

Route::post('promotions/validate',         [PublicPromotionController::class, 'validate']);

// ============================================================
// AUTHENTICATED ROUTES — Perlu JWT token
// ============================================================
Route::middleware(['jwt.auth'])->group(function () {

    // Auth
    Route::post('auth/logout',  [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::get('auth/me',       [AuthController::class, 'me']);

    // ── USER ROUTES ─────────────────────────────────────────
    Route::middleware(['role.user'])->group(function () {

        // Profil
        Route::get('profile',          [ProfileController::class, 'show']);
        Route::put('profile',          [ProfileController::class, 'update']);
        Route::post('profile/avatar',  [ProfileController::class, 'uploadAvatar']);

        // Booking
        Route::get('bookings',                      [UserBookingController::class, 'index']);
        Route::post('bookings',                     [UserBookingController::class, 'store']);
        Route::get('bookings/{booking}',            [UserBookingController::class, 'show']);
        Route::put('bookings/{booking}/cancel',     [UserBookingController::class, 'cancel']);
        Route::post('bookings/{booking}/payment',   [UserBookingController::class, 'uploadPayment']);
    });

    // ── ADMIN ROUTES ─────────────────────────────────────────
    Route::prefix('admin')->middleware(['role.admin'])->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Laporan & Export
        Route::get('reports/bookings',  [ReportController::class, 'bookings']);
        Route::get('reports/revenue',   [ReportController::class, 'revenue']);
        Route::get('export/pdf',        [ReportController::class, 'exportPdf']);
        Route::get('export/excel',      [ReportController::class, 'exportExcel']);

        // Users
        Route::get('users',                         [UserController::class, 'index']);
        Route::get('users/{user}',                  [UserController::class, 'show']);
        Route::put('users/{user}/toggle-status',    [UserController::class, 'toggleStatus']);

        // Field Types
        Route::apiResource('field-types', FieldTypeController::class)
             ->except(['index']); // index sudah di public

        // Fields
        Route::apiResource('fields', AdminFieldController::class);
        Route::post('fields/{field}/images',                [AdminFieldController::class, 'uploadImage']);
        Route::delete('fields/{field}/images/{image}',      [AdminFieldController::class, 'deleteImage']);

        // Time Slots
        Route::get('fields/{field}/time-slots',     [TimeSlotController::class, 'index']);
        Route::post('fields/{field}/time-slots',    [TimeSlotController::class, 'store']);
        Route::put('time-slots/{timeSlot}',         [TimeSlotController::class, 'update']);
        Route::delete('time-slots/{timeSlot}',      [TimeSlotController::class, 'destroy']);

        // Bookings
        Route::get('bookings',                      [AdminBookingController::class, 'index']);
        Route::post('bookings',                     [AdminBookingController::class, 'store']);
        Route::get('bookings/{booking}',            [AdminBookingController::class, 'show']);
        Route::put('bookings/{booking}/confirm',    [AdminBookingController::class, 'confirm']);
        Route::put('bookings/{booking}/reject',     [AdminBookingController::class, 'reject']);

        // Promotions
        Route::apiResource('promotions', AdminPromotionController::class);
    });
});

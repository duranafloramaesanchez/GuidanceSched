<?php

use App\Http\Controllers\Api\GuidanceApiController;
use Illuminate\Support\Facades\Route;

// These routes send JSON data to another program instead of returning Blade web pages.
// HTTP Basic authentication keeps this small prototype API stateless.
Route::middleware('auth.basic')->group(function () {
    // Appointment API: list, create, view, update, and cancel requests.
    Route::get('/appointments', [GuidanceApiController::class, 'appointments']);
    Route::post('/appointments', [GuidanceApiController::class, 'createAppointment']);
    Route::get('/appointments/{appointment}', [GuidanceApiController::class, 'showAppointment']);
    Route::patch('/appointments/{appointment}', [GuidanceApiController::class, 'updateAppointment']);
    Route::patch('/appointments/{appointment}/cancel', [GuidanceApiController::class, 'cancelAppointment']);

    // Case API: administrators can view cases and record follow-up work.
    Route::get('/cases', [GuidanceApiController::class, 'cases']);
    Route::get('/cases/{case}', [GuidanceApiController::class, 'showCase']);
    Route::patch('/cases/{case}/status', [GuidanceApiController::class, 'updateCaseStatus']);
    Route::get('/cases/{case}/follow-ups', [GuidanceApiController::class, 'followUps']);
    Route::post('/cases/{case}/follow-ups', [GuidanceApiController::class, 'createFollowUp']);
});

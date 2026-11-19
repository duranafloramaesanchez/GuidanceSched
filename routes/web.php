<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CaseRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::get('/appointments/available-times', [AppointmentController::class, 'availableTimes'])
        ->name('appointments.available-times');

    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->name('appointments.store');
    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    Route::get('/students', [UserController::class, 'students'])->name('students.index');
    Route::get('/students/{user}/details', [UserController::class, 'editStudentDetails'])->name('students.details.edit');
    Route::put('/students/{user}/details', [UserController::class, 'updateStudentDetails'])->name('students.details.update');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/', [CaseRecordController::class, 'index'])
            ->name('index');

        Route::get('/create', [CaseRecordController::class, 'create'])
            ->name('create');

        Route::post('/', [CaseRecordController::class, 'store'])
            ->name('store');

        Route::get('/{case}/edit', [CaseRecordController::class, 'edit'])->name('edit');
        Route::put('/{case}', [CaseRecordController::class, 'update'])->name('update');
        Route::post('/{case}/follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');

        Route::get('/{case}', [CaseRecordController::class, 'show'])
            ->name('show');
    });
});

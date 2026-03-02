<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\Teacher\TimetableController as TimetableController;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;



Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'getLogin'])->name('teacher.login');
});


// Route::get('/', function () {
//     dd(extension_loaded('curl'));
// });

Route::middleware(['web', 'teacher.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('teacher.dashboard');

    Route::get('/select-class-for-homework', [HomeworkController::class, 'selectClassForHomework'])
        ->name('teacher.select-class-for-homework');

    Route::get('/student-list-under-class/{class_id}', [HomeworkController::class, 'studentListUnderClass'])
        ->name('teacher.student-list-under-class');

    Route::get('/timetable', [TimetableController::class, 'index'])
        ->name('teacher.timetable');

    Route::get('/add-homework/{period_id}', [HomeworkController::class, 'addHomework'])
        ->name('teacher.add-homework');
});




// Route::get('/login', [OtpAuthController::class, 'showLogin'])
//     ->name('otp.login');

// Route::post('/verify-otp', [OtpAuthController::class, 'verifyOtp'])
//     ->name('otp.verify');

// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', [OtpAuthController::class, 'dashboard'])
//         ->name('dashboard');

//     Route::post('/logout', [OtpAuthController::class, 'logout'])
//         ->name('logout');
// });


Route::get('/firebase-test', function () {
    $auth = (new Factory)
        ->withServiceAccount(config('firebase.credentials'))
        ->createAuth();

    return 'Firebase Admin SDK working';
});

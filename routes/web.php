<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Public home page
Route::get('/', function () {
    return view('welcome');
});

// Guest authentication routes
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

});

// Logout route
Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Protected student-management routes
Route::middleware('auth')->group(function () {

    // Student listing
    Route::get('/students', [StudentController::class, 'index'])
        ->name('students.index');

    // Student registration
    Route::get('/students/create', [StudentController::class, 'create'])
        ->name('students.create');

    Route::post('/students', [StudentController::class, 'store'])
        ->name('students.store');

    // Archived students
    Route::get('/students/archived', [StudentController::class, 'archived'])
        ->name('students.archived');

    // Student editing
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])
        ->name('students.edit');

    Route::put('/students/{student}', [StudentController::class, 'update'])
        ->name('students.update');

    // Student archiving
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])
        ->name('students.destroy');

    // Student restoration
    Route::post('/students/{student}/restore', [StudentController::class, 'restore'])
        ->withTrashed()
        ->name('students.restore');

    // Student profile
    Route::get('/students/{student}', [StudentController::class, 'show'])
        ->name('students.show');

});
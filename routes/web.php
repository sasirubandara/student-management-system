<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
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
    ->middleware(['auth', 'active'])
    ->name('logout');

// Protected application routes
Route::middleware(['auth', 'active'])->group(function () {

    // User management
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');

    // Update user role
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
        ->name('users.role.update');

    // Update user account status
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
        ->name('users.status.update');

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
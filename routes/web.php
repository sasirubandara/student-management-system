<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Home route
Route::get('/', function () {
    return view('welcome');
});

// Student listing
Route::get('/students', [StudentController::class, 'index'])
    ->name('students.index');

// Student registration
Route::get('/students/create', [StudentController::class, 'create'])
    ->name('students.create');

Route::post('/students', [StudentController::class, 'store'])
    ->name('students.store');

// Student editing
Route::get('/students/{student}/edit', [StudentController::class, 'edit'])
    ->name('students.edit');

Route::put('/students/{student}', [StudentController::class, 'update'])
    ->name('students.update');

// Student profile
Route::get('/students/{student}', [StudentController::class, 'show'])
    ->name('students.show');
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

// Student registration form
Route::get('/students/create', [StudentController::class, 'create'])
    ->name('students.create');

// Individual student profile
Route::get('/students/{student}', [StudentController::class, 'show'])
    ->name('students.show');

// Store a newly registered student
Route::post('/students', [StudentController::class, 'store'])
    ->name('students.store');
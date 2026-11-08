<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AcademicClassController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\StudentEnrollmentController;
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

    // Academic class management
    Route::get('/academic-classes', [AcademicClassController::class, 'index'])
        ->name('academic-classes.index');

    // Create academic classes
    Route::get('/academic-classes/create', [AcademicClassController::class, 'create'])
        ->name('academic-classes.create');

    Route::post('/academic-classes', [AcademicClassController::class, 'store'])
        ->name('academic-classes.store');

    // Edit academic classes
    Route::get('/academic-classes/{academicClass}/edit', [AcademicClassController::class, 'edit'])
        ->name('academic-classes.edit');

    // Update academic classes
    Route::put('/academic-classes/{academicClass}', [AcademicClassController::class, 'update'])
        ->name('academic-classes.update');

    // Delete academic classes
    Route::delete('/academic-classes/{academicClass}', [AcademicClassController::class, 'destroy'])
        ->name('academic-classes.destroy');

    // Academic class profile
    Route::get('/academic-classes/{academicClass}', [AcademicClassController::class, 'show'])
        ->name('academic-classes.show');

    // Subject management
    Route::get('/subjects', [SubjectController::class, 'index'])
        ->name('subjects.index');

    Route::get('/subjects/create', [SubjectController::class, 'create'])
        ->name('subjects.create');

    Route::post('/subjects', [SubjectController::class, 'store'])
        ->name('subjects.store');

    Route::get('/subjects/{subject}/edit', [SubjectController::class, 'edit'])
        ->name('subjects.edit');

    Route::put('/subjects/{subject}', [SubjectController::class, 'update'])
        ->name('subjects.update');

    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])
        ->name('subjects.destroy');

    Route::get('/subjects/{subject}', [SubjectController::class, 'show'])
        ->name('subjects.show');

    // Student enrollment management
    Route::get('/student-enrollments', [StudentEnrollmentController::class, 'index'])
        ->name('student-enrollments.index');

    Route::get('/student-enrollments/create', [StudentEnrollmentController::class, 'create'])
        ->name('student-enrollments.create');

    Route::post('/student-enrollments', [StudentEnrollmentController::class, 'store'])
        ->name('student-enrollments.store');

    Route::get('/student-enrollments/{studentEnrollment}/edit', [StudentEnrollmentController::class, 'edit'])
        ->name('student-enrollments.edit');

    Route::put('/student-enrollments/{studentEnrollment}', [StudentEnrollmentController::class, 'update'])
        ->name('student-enrollments.update');

    Route::get('/student-enrollments/{studentEnrollment}', [StudentEnrollmentController::class, 'show'])
        ->name('student-enrollments.show');

});
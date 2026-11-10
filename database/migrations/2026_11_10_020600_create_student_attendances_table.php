<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the student attendances table.
     */
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->restrictOnDelete();

            $table->foreignId('student_enrollment_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->string('status', 20)->index();

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Prevent duplicate attendance records for one enrollment in a session.
            $table->unique(
                ['attendance_session_id', 'student_enrollment_id'],
                'student_attendances_session_enrollment_unique'
            );
        });
    }

    /**
     * Drop the student attendances table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
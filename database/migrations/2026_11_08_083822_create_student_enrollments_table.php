<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the student enrollments table.
     */
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('academic_class_id')
                ->constrained('academic_classes')
                ->restrictOnDelete();

            $table->date('enrollment_date');

            $table->string('status', 20)
                ->default('active')
                ->index();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate student-to-class enrollment records.
            $table->unique(
                ['student_id', 'academic_class_id'],
                'student_enrollments_student_class_unique'
            );
        });
    }

    /**
     * Drop the student enrollments table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
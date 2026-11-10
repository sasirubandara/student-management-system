<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('examination_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('examination_id')
                ->constrained('examinations')
                ->restrictOnDelete();

            $table->foreignId('student_enrollment_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->decimal('marks_obtained', 8, 2);

            $table->string('grade', 5);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(
                ['examination_id', 'student_enrollment_id'],
                'examination_results_examination_enrollment_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_results');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the student promotions table.
     */
    public function up(): void
    {
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('source_enrollment_id')
                ->unique()
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->foreignId('destination_enrollment_id')
                ->unique()
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->string('promotion_type', 30);

            $table->date('promotion_date');

            $table->text('notes')->nullable();

            $table->foreignId('promoted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['student_id', 'promotion_date']);
        });
    }

    /**
     * Drop the student promotions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
    }
};

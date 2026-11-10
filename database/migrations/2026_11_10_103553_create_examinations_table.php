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
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();

            $table->string('title', 150);

            $table->string('exam_type', 50);

            $table->date('exam_date');

            $table->decimal('maximum_marks', 8, 2);

            $table->decimal('pass_marks', 8, 2)->nullable();

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('exam_date');
            $table->index('exam_type');

            $table->unique(
                ['teaching_assignment_id', 'title', 'exam_date'],
                'examinations_assignment_title_date_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};
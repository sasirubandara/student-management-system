<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the academic class subject assignment table.
     */
    public function up(): void
    {
        Schema::create('academic_class_subject', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_class_id')
                ->constrained('academic_classes')
                ->restrictOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['academic_class_id', 'subject_id'],
                'academic_class_subject_unique'
            );
        });
    }

    /**
     * Drop the academic class subject assignment table.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_class_subject');
    }
};
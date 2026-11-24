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
        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->unsignedTinyInteger('term_number');

            $table->date('start_date');
            $table->date('end_date');

            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'term_number'],
                'academic_terms_year_number_unique'
            );

            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};

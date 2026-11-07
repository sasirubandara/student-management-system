<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the academic classes table.
     */
    public function up(): void
    {
        Schema::create('academic_classes', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('code', 20)->unique();

            $table->unsignedTinyInteger('grade_level');

            $table->unsignedSmallInteger('academic_year');

            $table->text('description')->nullable();

            $table->string('status', 20)
                ->default('active')
                ->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['academic_year', 'grade_level']);
        });
    }

    /**
     * Drop the academic classes table.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_classes');
    }
};
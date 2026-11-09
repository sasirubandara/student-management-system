<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the teachers table.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('teacher_number', 20)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->date('date_of_birth')->nullable();
            $table->date('employment_date');

            $table->string('status', 20)
                ->default('active')
                ->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Drop the teachers table.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
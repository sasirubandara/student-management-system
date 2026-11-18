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
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();

            $table->string('institution_name')
                ->default('Student Management System');

            $table->string('institution_email')
                ->nullable();

            $table->string('institution_phone', 50)
                ->nullable();

            $table->text('institution_address')
                ->nullable();

            $table->unsignedSmallInteger('current_academic_year');

            $table->string('timezone', 100)
                ->default('UTC');

            $table->string('date_format', 30)
                ->default('Y-m-d');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};

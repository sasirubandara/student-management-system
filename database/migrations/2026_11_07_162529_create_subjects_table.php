<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the subjects table.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('code', 20)->unique();

            $table->text('description')->nullable();

            $table->string('status', 20)
                ->default('active')
                ->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Drop the subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
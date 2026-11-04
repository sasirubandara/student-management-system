
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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('student_number', 20)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->date('date_of_birth')->nullable();
            $table->date('enrollment_date');

            $table->string('status', 20)->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
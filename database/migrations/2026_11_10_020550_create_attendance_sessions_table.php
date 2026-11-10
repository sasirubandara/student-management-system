<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the attendance sessions table.
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();

            $table->date('attendance_date');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Allow only one attendance session per teaching assignment per date.
            $table->unique(
                ['teaching_assignment_id', 'attendance_date'],
                'attendance_sessions_assignment_date_unique'
            );
        });
    }

    /**
     * Drop the attendance sessions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
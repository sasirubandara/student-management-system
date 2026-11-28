<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_locks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
        });

        DB::table('timetable_locks')->insert([
            'id' => 1,
        ]);

        Schema::create('timetable_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('academic_term_id')
                ->constrained('academic_terms')
                ->restrictOnDelete();

            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'academic_term_id',
                    'teaching_assignment_id',
                    'day_of_week',
                    'start_time',
                ],
                'timetable_entries_exact_slot_unique'
            );

            $table->index(
                [
                    'academic_term_id',
                    'day_of_week',
                    'start_time',
                    'end_time',
                ],
                'timetable_entries_term_day_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_locks');
    }
};

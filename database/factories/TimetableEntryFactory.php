<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetableEntry>
 */
class TimetableEntryFactory extends Factory
{
    protected $model = TimetableEntry::class;

    public function definition(): array
    {
        return [
            'academic_term_id' => AcademicTerm::factory(),
            'teaching_assignment_id' => TeachingAssignment::factory(),
            'day_of_week' => fake()->numberBetween(1, 5),
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'created_by' => null,
        ];
    }
}

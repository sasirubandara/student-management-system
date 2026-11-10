<?php

namespace Database\Factories;

use App\Models\Examination;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExaminationFactory extends Factory
{
    protected $model = Examination::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'teaching_assignment_id' => TeachingAssignment::factory(),

            'title' => fake()->unique()->words(3, true),

            'exam_type' => fake()->randomElement(
                Examination::EXAM_TYPES
            ),

            'exam_date' => fake()
                ->dateTimeBetween('-1 year', '+1 year')
                ->format('Y-m-d'),

            'maximum_marks' => '100.00',

            'pass_marks' => '40.00',

            'description' => fake()->optional()->paragraph(),

            'created_by' => User::factory(),
        ];
    }

    /**
     * Associate the examination with an existing teaching assignment.
     */
    public function forTeachingAssignment(
        TeachingAssignment $teachingAssignment
    ): static {
        return $this->state([
            'teaching_assignment_id' => $teachingAssignment->id,
        ]);
    }
}
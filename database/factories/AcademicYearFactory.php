<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2100);

        return [
            'year' => $year,
            'name' => 'Academic Year '.$year,
            'start_date' => $year.'-01-01',
            'end_date' => $year.'-12-31',
        ];
    }
}

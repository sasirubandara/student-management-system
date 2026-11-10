<?php

namespace App\Services;

use InvalidArgumentException;

class GradeCalculator
{
    /**
     * Calculate a grade using the configured grading scale.
     */
    public function calculate(
        int|float|string $marksObtained,
        int|float|string $maximumMarks
    ): string {
        if (! is_numeric($marksObtained) || ! is_numeric($maximumMarks)) {
            throw new InvalidArgumentException(
                'Marks must be numeric values.'
            );
        }

        $marks = (float) $marksObtained;
        $maximum = (float) $maximumMarks;

        if ($maximum <= 0) {
            throw new InvalidArgumentException(
                'Maximum marks must be greater than zero.'
            );
        }

        if ($marks < 0 || $marks > $maximum) {
            throw new InvalidArgumentException(
                'Marks obtained must be between zero and maximum marks.'
            );
        }

        $percentage = ($marks / $maximum) * 100;

        $gradingScale = config('examinations.grading_scale', []);

        foreach ($gradingScale as $gradeRule) {
            if ($percentage >= $gradeRule['min']) {
                return $gradeRule['grade'];
            }
        }

        throw new InvalidArgumentException(
            'The grading scale does not cover the calculated percentage.'
        );
    }
}
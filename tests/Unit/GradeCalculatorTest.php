<?php

use App\Services\GradeCalculator;
use InvalidArgumentException;

uses(Tests\TestCase::class);

it('calculates grades using the configured grading scale', function (
    float $marks,
    string $expectedGrade
) {
    $calculator = app(GradeCalculator::class);

    expect($calculator->calculate($marks, 100))
        ->toBe($expectedGrade);
})->with([
    'grade A at 85 percent' => [85, 'A'],
    'grade A at 100 percent' => [100, 'A'],
    'grade B at 84.99 percent' => [84.99, 'B'],
    'grade B at 70 percent' => [70, 'B'],
    'grade C at 69.99 percent' => [69.99, 'C'],
    'grade C at 55 percent' => [55, 'C'],
    'grade D at 54.99 percent' => [54.99, 'D'],
    'grade D at 40 percent' => [40, 'D'],
    'grade F below 40 percent' => [39.99, 'F'],
    'grade F at zero marks' => [0, 'F'],
]);

it('calculates percentages when maximum marks are not 100', function () {
    $calculator = app(GradeCalculator::class);

    expect($calculator->calculate(42.50, 50))
        ->toBe('A');
});

it('rejects negative marks', function () {
    $calculator = app(GradeCalculator::class);

    $calculator->calculate(-5, 100);
})->throws(InvalidArgumentException::class);

it('rejects marks exceeding the maximum', function () {
    $calculator = app(GradeCalculator::class);

    $calculator->calculate(105, 100);
})->throws(InvalidArgumentException::class);

it('rejects a zero maximum mark value', function () {
    $calculator = app(GradeCalculator::class);

    $calculator->calculate(50, 0);
})->throws(InvalidArgumentException::class);

it('rejects non-numeric marks', function () {
    $calculator = app(GradeCalculator::class);

    $calculator->calculate('invalid', 100);
})->throws(InvalidArgumentException::class);
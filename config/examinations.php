<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Examination Grading Scale
    |--------------------------------------------------------------------------
    |
    | Each grade has a minimum percentage requirement.
    | Keep the grades ordered from highest to lowest.
    |
    */

    'grading_scale' => [

        [
            'grade' => 'A',
            'min' => 85,
        ],

        [
            'grade' => 'B',
            'min' => 70,
        ],

        [
            'grade' => 'C',
            'min' => 55,
        ],

        [
            'grade' => 'D',
            'min' => 40,
        ],

        [
            'grade' => 'F',
            'min' => 0,
        ],

    ],

];
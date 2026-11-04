<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'enrollment_date',
        'status',
    ];
}
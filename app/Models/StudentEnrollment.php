<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'academic_class_id',
        'enrollment_date',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
        ];
    }

    /**
     * Get the student associated with this enrollment,
     * including archived students.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    /**
     * Get the academic class associated with this enrollment,
     * including archived classes.
     */
    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class)->withTrashed();
    }

    /**
     * Get the attendance records for this enrollment.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Get the examination results for this enrollment.
     */
    public function examinationResults(): HasMany
    {
        return $this->hasMany(ExaminationResult::class);
    }
}
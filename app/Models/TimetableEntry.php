<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_term_id',
        'teaching_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'academic_term_id' => 'integer',
            'teaching_assignment_id' => 'integer',
            'day_of_week' => 'integer',
        ];
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

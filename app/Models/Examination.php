<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examination extends Model
{
    use HasFactory;

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_MIDTERM = 'midterm';

    public const TYPE_TERM_EXAMINATION = 'term_examination';

    public const TYPE_FINAL_EXAMINATION = 'final_examination';

    public const TYPE_PRACTICAL = 'practical';

    public const TYPE_OTHER = 'other';

    /**
     * Available examination types.
     */
    public const EXAM_TYPES = [
        self::TYPE_QUIZ,
        self::TYPE_ASSIGNMENT,
        self::TYPE_MIDTERM,
        self::TYPE_TERM_EXAMINATION,
        self::TYPE_FINAL_EXAMINATION,
        self::TYPE_PRACTICAL,
        self::TYPE_OTHER,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'teaching_assignment_id',
        'title',
        'exam_type',
        'exam_date',
        'maximum_marks',
        'pass_marks',
        'description',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'maximum_marks' => 'decimal:2',
            'pass_marks' => 'decimal:2',
        ];
    }

    /**
     * Get the teaching assignment associated with the examination.
     */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /**
     * Get the user who created the examination.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the results belonging to the examination.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExaminationResult::class);
    }
}
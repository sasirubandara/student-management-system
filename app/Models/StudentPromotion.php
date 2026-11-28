<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    use HasFactory;

    public const TYPE_ADVANCED = 'advanced';

    public const TYPE_REPEATED = 'repeated';

    protected $fillable = [
        'student_id',
        'source_enrollment_id',
        'destination_enrollment_id',
        'promotion_type',
        'promotion_date',
        'notes',
        'promoted_by',
    ];

    protected function casts(): array
    {
        return [
            'promotion_date' => 'date',
        ];
    }

    /**
     * Student associated with this promotion.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)
            ->withTrashed();
    }

    /**
     * Enrollment completed by the promotion.
     */
    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentEnrollment::class,
            'source_enrollment_id'
        );
    }

    /**
     * Enrollment created by the promotion.
     */
    public function destinationEnrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentEnrollment::class,
            'destination_enrollment_id'
        );
    }

    /**
     * Administrator responsible for this promotion.
     */
    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'promoted_by'
        );
    }
}

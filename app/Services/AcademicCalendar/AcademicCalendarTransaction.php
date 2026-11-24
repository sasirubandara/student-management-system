<?php

namespace App\Services\AcademicCalendar;

use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

class AcademicCalendarTransaction
{
    /**
     * Execute an academic-calendar write under the shared lock.
     */
    public function run(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback): mixed {
            $lock = DB::table('academic_calendar_locks')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            if ($lock === null) {
                throw new LogicException(
                    'The academic calendar lock record is missing.'
                );
            }

            return $callback();
        }, 3);
    }
}

<?php

namespace App\Services\Timetables;

use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

class TimetableTransaction
{
    /**
     * Execute a timetable write under the shared database lock.
     */
    public function run(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback): mixed {
            $lock = DB::table('timetable_locks')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            if ($lock === null) {
                throw new LogicException(
                    'The timetable lock record is missing.'
                );
            }

            return $callback();
        }, 3);
    }
}

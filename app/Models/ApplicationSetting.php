<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    /**
     * The identifier of the application's settings record.
     */
    public const SETTINGS_ID = 1;

    /**
     * Supported date display formats.
     */
    public const DATE_FORMATS = [
        'Y-m-d',
        'd/m/Y',
        'm/d/Y',
        'd M Y',
        'M d, Y',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'institution_name',
        'institution_email',
        'institution_phone',
        'institution_address',
        'current_academic_year',
        'timezone',
        'date_format',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'current_academic_year' => 'integer',
        ];
    }

    /**
     * Get the default application settings.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'institution_name' => 'Student Management System',
            'institution_email' => null,
            'institution_phone' => null,
            'institution_address' => null,
            'current_academic_year' => now()->year,
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'Y-m-d',
        ];
    }
}

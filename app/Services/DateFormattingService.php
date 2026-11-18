<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DateFormattingService
{
    public function __construct(
        private readonly ApplicationSettingsService $settings
    ) {}

    /**
     * Format a calendar date using the configured date format.
     */
    public function date(
        ?DateTimeInterface $value
    ): string {
        if ($value === null) {
            return '—';
        }

        $format = $this->settings->get(
            'date_format',
            'Y-m-d'
        );

        return CarbonImmutable::instance($value)
            ->format($format);
    }

    /**
     * Format a timestamp using the configured timezone.
     */
    public function dateTime(
        ?DateTimeInterface $value
    ): string {
        if ($value === null) {
            return '—';
        }

        $timezone = $this->settings->get(
            'timezone',
            'UTC'
        );

        $format = $this->settings->get(
            'date_format',
            'Y-m-d'
        );

        return CarbonImmutable::instance($value)
            ->setTimezone($timezone)
            ->format($format.' H:i');
    }
}

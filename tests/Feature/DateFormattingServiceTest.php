<?php

use App\Services\ApplicationSettingsService;
use App\Services\DateFormattingService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    app(ApplicationSettingsService::class)->clearCache();
});

afterEach(function (): void {
    app(ApplicationSettingsService::class)->clearCache();
});

it('formats dates using the configured date format', function (): void {
    app(ApplicationSettingsService::class)->update([
        'date_format' => 'd/m/Y',
    ]);

    $formatter = app(DateFormattingService::class);

    $date = CarbonImmutable::parse(
        '2027-01-15',
        'UTC'
    );

    expect($formatter->date($date))
        ->toBe('15/01/2027');
});

it('formats timestamps using the configured timezone', function (): void {
    app(ApplicationSettingsService::class)->update([
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ]);

    $formatter = app(DateFormattingService::class);

    $timestamp = CarbonImmutable::parse(
        '2027-01-15 10:00:00',
        'UTC'
    );

    expect($formatter->dateTime($timestamp))
        ->toBe('15/01/2027 15:30');
});

it('does not shift calendar dates when timezone preferences change', function (): void {
    app(ApplicationSettingsService::class)->update([
        'timezone' => 'America/New_York',
        'date_format' => 'Y-m-d',
    ]);

    $formatter = app(DateFormattingService::class);

    $date = CarbonImmutable::parse(
        '2027-01-15 00:00:00',
        'UTC'
    );

    expect($formatter->date($date))
        ->toBe('2027-01-15');
});

it('does not modify the original timestamp', function (): void {
    app(ApplicationSettingsService::class)->update([
        'timezone' => 'Asia/Colombo',
        'date_format' => 'Y-m-d',
    ]);

    $formatter = app(DateFormattingService::class);

    $timestamp = Carbon::parse(
        '2027-01-15 10:00:00',
        'UTC'
    );

    $formatter->dateTime($timestamp);

    expect($timestamp->format('Y-m-d H:i'))
        ->toBe('2027-01-15 10:00');

    expect($timestamp->getTimezone()->getName())
        ->toBe('UTC');
});

it('handles missing date values safely', function (): void {
    $formatter = app(DateFormattingService::class);

    expect($formatter->date(null))->toBe('—');

    expect($formatter->dateTime(null))->toBe('—');
});

it('uses updated preferences when formatting timestamps', function (): void {
    $settings = app(ApplicationSettingsService::class);

    $formatter = app(DateFormattingService::class);

    $timestamp = CarbonImmutable::parse(
        '2027-01-15 10:00:00',
        'UTC'
    );

    $settings->update([
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
    ]);

    expect($formatter->dateTime($timestamp))
        ->toBe('2027-01-15 10:00');

    $settings->update([
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ]);

    expect($formatter->dateTime($timestamp))
        ->toBe('15/01/2027 15:30');
});

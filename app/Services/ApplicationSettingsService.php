<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplicationSettingsService
{
    public const CACHE_KEY = 'application_settings';

    public const CACHE_TTL = 3600;

    /**
     * Retrieve or initialize the application settings record.
     */
    public function getRecord(): ApplicationSetting
    {
        return ApplicationSetting::query()->firstOrCreate(
            ['id' => ApplicationSetting::SETTINGS_ID],
            ApplicationSetting::defaults()
        );
    }

    /**
     * Retrieve all supported application settings.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function (): array {
                return $this->getRecord()->only(
                    array_keys(ApplicationSetting::defaults())
                );
            }
        );
    }

    /**
     * Retrieve an individual application setting.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Update the supported application settings.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        array $attributes,
        ?User $actor = null
    ): ApplicationSetting {
        $allowedKeys = array_keys(
            ApplicationSetting::defaults()
        );

        $this->getRecord();

        $settings = DB::transaction(function () use (
            $attributes,
            $allowedKeys,
            $actor
        ): ApplicationSetting {
            $settings = ApplicationSetting::query()
                ->whereKey(ApplicationSetting::SETTINGS_ID)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $settings->only($allowedKeys);

            $settings->fill(
                Arr::only($attributes, $allowedKeys)
            );

            $settings->save();

            $after = $settings->only($allowedKeys);

            $changes = [];

            foreach ($allowedKeys as $key) {
                if ($before[$key] !== $after[$key]) {
                    $changes[$key] = [
                        'old' => $before[$key],
                        'new' => $after[$key],
                    ];
                }
            }

            if ($actor !== null && $changes !== []) {
                app(AuditLogger::class)->record(
                    event: 'application_settings.updated',
                    auditable: $settings,
                    properties: [
                        'changes' => $changes,
                    ],
                    actor: $actor
                );
            }

            return $settings;
        });

        $this->clearCache();

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function (): void {
                $this->clearCache();
            });
        }

        return $settings->refresh();
    }

    /**
     * Invalidate the cached application settings.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

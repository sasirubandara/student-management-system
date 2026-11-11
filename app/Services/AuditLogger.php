<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an application activity.
     */
    public function record(
        string $event,
        ?Model $auditable = null,
        array $properties = [],
        ?User $actor = null
    ): AuditLog {
        $request = request();

        $actor ??= Auth::user();

        $userAgent = $request->userAgent();

        return AuditLog::create([
            'user_id' => $actor?->getKey(),

            'event' => $event,

            'auditable_type' => $auditable?->getMorphClass(),

            'auditable_id' => $auditable?->getKey(),

            'properties' => $this->sanitizeProperties($properties),

            'ip_address' => $request->ip(),

            'user_agent' => $userAgent !== null
                ? mb_substr($userAgent, 0, 1000)
                : null,

            'created_at' => now(),
        ]);
    }

    /**
     * Remove sensitive information from audit properties.
     */
    private function sanitizeProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $keyName = (string) $key;

            // Never record passwords, tokens, secrets, or credentials.
            if (preg_match(
                '/password|token|secret|authorization|cookie|api[_-]?key/i',
                $keyName
            )) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeProperties($value);

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = mb_substr($value, 0, 500);

                continue;
            }

            if (
                is_int($value)
                || is_float($value)
                || is_bool($value)
                || $value === null
            ) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
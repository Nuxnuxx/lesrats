<?php

namespace App\Services;

use PostHog\PostHog;

class PostHogService
{
    public static function capture(string|int $distinctId, string $event, array $properties = []): void
    {
        if (config('posthog.disabled')) {
            return;
        }

        PostHog::capture([
            'distinctId' => (string) $distinctId,
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    public static function identify(string|int $distinctId, array $properties = []): void
    {
        if (config('posthog.disabled')) {
            return;
        }

        PostHog::identify([
            'distinctId' => (string) $distinctId,
            'properties' => $properties,
        ]);
    }
}

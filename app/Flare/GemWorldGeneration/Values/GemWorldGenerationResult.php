<?php

namespace App\Flare\GemWorldGeneration\Values;

use Carbon\CarbonInterface;

class GemWorldGenerationResult
{
    public function __construct(
        public readonly string $profile_name,
        public readonly string $profile_label,
        public readonly string $map_type,
        public readonly string $status,
        public readonly ?int $map_id,
        public readonly ?string $path,
        public readonly int $locations_created,
        public readonly CarbonInterface $started_at,
        public readonly CarbonInterface $finished_at,
        public readonly int $elapsed_seconds,
        public readonly string $message,
    ) {}

    public function generated(): bool
    {
        return $this->status === 'generated';
    }

    public function skipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}

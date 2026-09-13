<?php

namespace App\Flare\GemWorldGeneration\Values;

use Carbon\CarbonInterface;

class GemWorldGenerationResult
{
    /**
     * @param string $profile_name
     * @param string $profile_label
     * @param string $map_type
     * @param string $status
     * @param int|null $map_id
     * @param string|null $path
     * @param int $locations_created
     * @param CarbonInterface $started_at
     * @param CarbonInterface $finished_at
     * @param int $elapsed_seconds
     * @param string $message
     */
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

    /**
     * Determine whether the Gem World was newly generated or its Locations were recovered.
     *
     * @return bool
     */
    public function generated(): bool
    {
        return $this->status === 'generated';
    }

    /**
     * Determine whether the Gem World already existed with Locations and was left untouched.
     *
     * @return bool
     */
    public function skipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Determine whether the Gem World could not be produced.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Determine whether the Gem World could not be synchronized because its committed backup
     * assets are missing.
     *
     * @return bool
     */
    public function missingBackup(): bool
    {
        return $this->status === 'missing_backup';
    }
}

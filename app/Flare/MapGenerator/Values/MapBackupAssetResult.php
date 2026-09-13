<?php

namespace App\Flare\MapGenerator\Values;

/**
 * Immutable outcome of restoring/validating a Game Map's committed image and tile pieces.
 */
class MapBackupAssetResult
{
    /**
     * @param MapBackupAssetStatus $status
     * @param string $message
     */
    public function __construct(
        public readonly MapBackupAssetStatus $status,
        public readonly string $message,
    ) {}

    /**
     * Determine whether the Game Map's assets are valid and usable, whether they were already
     * valid, restored from committed backup, or repaired from existing live pieces.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [
            MapBackupAssetStatus::ALREADY_VALID,
            MapBackupAssetStatus::RESTORED,
            MapBackupAssetStatus::REPAIRED,
        ], true);
    }

    /**
     * Determine whether the required committed backup was missing.
     *
     * @return bool
     */
    public function isMissingBackup(): bool
    {
        return $this->status === MapBackupAssetStatus::MISSING_BACKUP;
    }

    /**
     * Determine whether restoration failed unexpectedly.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->status === MapBackupAssetStatus::FAILED;
    }
}

<?php

namespace App\Flare\MapGenerator\Values;

/**
 * Immutable state prepared for a Game Map tile replacement lifecycle.
 */
class PreparedMapTileReplacement
{
    /**
     * @param array<int, array<int, string>> $tileMap Generated public tile URLs indexed by tile row and column.
     * @param string $previousFolderName Previously committed folder derived from the original map name.
     * @param string $currentFolderName Committed folder derived from the replacement map name.
     * @param string $replacementFolderName Temporary folder containing generated replacement tiles.
     * @param string $backupFolderName Temporary folder used to preserve the current committed tiles.
     * @param bool $hadCurrentDirectory Whether the current-name committed tile directory existed at preparation time.
     * @param bool $sameCommittedDirectory Whether the previous and replacement names resolve to the same committed tile directory.
     */
    public function __construct(
        public readonly array $tileMap,
        public readonly string $previousFolderName,
        public readonly string $currentFolderName,
        public readonly string $replacementFolderName,
        public readonly string $backupFolderName,
        public readonly bool $hadCurrentDirectory,
        public readonly bool $sameCommittedDirectory,
    ) {}
}

<?php

namespace App\Game\Gems\Progression\Values;

/**
 * Resolves the one authoritative Gem Scroll tier range for a Character's
 * personal Gem progression level. This is the single source of truth for
 * Scroll roll ranges; the generator, Player-facing UI, and tests must all
 * read from here instead of duplicating the numeric ranges.
 */
class GemScrollTierRanges
{
    /**
     * Resolve the Gem Scroll roll ranges for the given personal Gem progression level.
     */
    public function forPersonalLevel(int $personalLevel): GemScrollTierRange
    {
        if ($personalLevel < 200) {
            return new GemScrollTierRange(0.05, 0.15, 0.05, 0.15, 0.01, 0.02, 120, 0.01, 0.02, 0.01, 0.01);
        }

        if ($personalLevel < 300) {
            return new GemScrollTierRange(0.15, 0.20, 0.15, 0.20, 0.02, 0.03, 180, 0.02, 0.03, 0.01, 0.02);
        }

        if ($personalLevel < 400) {
            return new GemScrollTierRange(0.30, 0.45, 0.30, 0.45, 0.03, 0.04, 240, 0.03, 0.05, 0.02, 0.03);
        }

        if ($personalLevel < 500) {
            return new GemScrollTierRange(0.50, 0.60, 0.50, 0.60, 0.04, 0.05, 300, 0.05, 0.07, 0.03, 0.05);
        }

        if ($personalLevel < 600) {
            return new GemScrollTierRange(0.75, 0.90, 0.75, 0.90, 0.05, 0.06, 360, 0.07, 0.10, 0.05, 0.07);
        }

        return new GemScrollTierRange(0.85, 1.10, 0.85, 1.10, 0.06, 0.08, 480, 0.10, 0.15, 0.07, 0.10);
    }
}

<?php

namespace App\Game\Monsters\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Owns the single revision marker for the canonical shared Monster cache.
 * Character-effective derived Monster caches key themselves by this
 * revision so they expire naturally whenever the canonical cache is
 * rebuilt, without enumerating or deleting every Character-derived key.
 */
class MonsterCacheRevisionService
{
    private const string REVISION_CACHE_KEY = 'monster-cache-revision';

    private const int STARTING_REVISION = 1;

    /**
     * The current canonical Monster cache revision, initializing a stable
     * starting value when none has been published yet.
     */
    public function current(): int
    {
        $revision = Cache::get(self::REVISION_CACHE_KEY);

        if (is_null($revision)) {
            Cache::put(self::REVISION_CACHE_KEY, self::STARTING_REVISION);

            return self::STARTING_REVISION;
        }

        return $revision;
    }

    /**
     * Advance the canonical Monster cache revision. Must only be called
     * after every canonical cache build has completed successfully.
     */
    public function bump(): int
    {
        $nextRevision = $this->current() + 1;

        Cache::put(self::REVISION_CACHE_KEY, $nextRevision);

        return $nextRevision;
    }
}

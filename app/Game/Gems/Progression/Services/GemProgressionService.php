<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameLocationGemProgression;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameMapGemProgression;
use App\Game\Gems\Progression\Values\GemProgressionResult;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Owns atomic persistence of global/personal Gem progression XP and level
 * advancement. Every award is applied inside a database transaction with a
 * row-level lock on the single progression row so concurrent kills cannot
 * lose XP or apply the same award twice.
 */
class GemProgressionService
{
    public function __construct(
        private readonly GemProgressionCurveService $gemProgressionCurveService,
    ) {}

    /**
     * Atomically apply global Gem progression XP to the given Map Gem profile.
     */
    public function applyGlobalMapProgressionXp(GameMapGemParamter $gameMapGemParamter, int $xpToApply): GemProgressionResult
    {
        return DB::transaction(function () use ($gameMapGemParamter, $xpToApply): GemProgressionResult {
            $progression = GameMapGemProgression::firstOrCreate(
                ['game_map_gem_paramter_id' => $gameMapGemParamter->id],
                ['level' => 1, 'xp' => 0],
            );

            $progression = GameMapGemProgression::query()->whereKey($progression->id)->lockForUpdate()->firstOrFail();

            return $this->applyXp(
                $progression,
                $xpToApply,
                $this->gemProgressionCurveService->globalMaxLevel(),
                fn (int $level): ?int => $this->gemProgressionCurveService->xpRequiredForGlobalLevel($level),
            );
        });
    }

    /**
     * Atomically apply global Gem progression XP to the given Location Gem profile.
     */
    public function applyGlobalLocationProgressionXp(GameLocationGemParamter $gameLocationGemParamter, int $xpToApply): GemProgressionResult
    {
        return DB::transaction(function () use ($gameLocationGemParamter, $xpToApply): GemProgressionResult {
            $progression = GameLocationGemProgression::firstOrCreate(
                ['game_location_gem_paramter_id' => $gameLocationGemParamter->id],
                ['level' => 1, 'xp' => 0],
            );

            $progression = GameLocationGemProgression::query()->whereKey($progression->id)->lockForUpdate()->firstOrFail();

            return $this->applyXp(
                $progression,
                $xpToApply,
                $this->gemProgressionCurveService->globalMaxLevel(),
                fn (int $level): ?int => $this->gemProgressionCurveService->xpRequiredForGlobalLevel($level),
            );
        });
    }

    /**
     * Atomically apply personal Gem progression XP for the given Character/Map Gem profile.
     */
    public function applyPersonalMapProgressionXp(Character $character, GameMapGemParamter $gameMapGemParamter, int $xpToApply): GemProgressionResult
    {
        return DB::transaction(function () use ($character, $gameMapGemParamter, $xpToApply): GemProgressionResult {
            $progression = CharacterGameMapGemProgression::firstOrCreate(
                ['character_id' => $character->id, 'game_map_gem_paramter_id' => $gameMapGemParamter->id],
                ['level' => 1, 'xp' => 0],
            );

            $progression = CharacterGameMapGemProgression::query()->whereKey($progression->id)->lockForUpdate()->firstOrFail();

            return $this->applyXp(
                $progression,
                $xpToApply,
                $this->gemProgressionCurveService->personalMaxLevel(),
                fn (int $level): ?int => $this->gemProgressionCurveService->xpRequiredForPersonalLevel($level),
            );
        });
    }

    /**
     * Atomically apply personal Gem progression XP for the given Character/Location Gem profile.
     */
    public function applyPersonalLocationProgressionXp(Character $character, GameLocationGemParamter $gameLocationGemParamter, int $xpToApply): GemProgressionResult
    {
        return DB::transaction(function () use ($character, $gameLocationGemParamter, $xpToApply): GemProgressionResult {
            $progression = CharacterGameLocationGemProgression::firstOrCreate(
                ['character_id' => $character->id, 'game_location_gem_paramter_id' => $gameLocationGemParamter->id],
                ['level' => 1, 'xp' => 0],
            );

            $progression = CharacterGameLocationGemProgression::query()->whereKey($progression->id)->lockForUpdate()->firstOrFail();

            return $this->applyXp(
                $progression,
                $xpToApply,
                $this->gemProgressionCurveService->personalMaxLevel(),
                fn (int $level): ?int => $this->gemProgressionCurveService->xpRequiredForPersonalLevel($level),
            );
        });
    }

    /**
     * Add the given XP to the locked progression row, repeatedly consuming
     * level requirements until the remaining XP is below the next threshold
     * or the max level is reached, then persist the result.
     */
    private function applyXp(Model $progression, int $xpToApply, int $maxLevel, Closure $xpRequiredForLevel): GemProgressionResult
    {
        $oldLevel = $progression->level;
        $oldXp = $progression->xp;

        if ($oldLevel >= $maxLevel) {
            return new GemProgressionResult($oldLevel, $oldLevel, $oldXp, $oldXp, 0, false, true);
        }

        $currentLevel = $oldLevel;
        $currentXp = $oldXp + $xpToApply;

        while ($currentLevel < $maxLevel) {
            $requiredXp = $xpRequiredForLevel($currentLevel);

            if (is_null($requiredXp) || $currentXp < $requiredXp) {
                break;
            }

            $currentXp -= $requiredXp;
            $currentLevel++;
        }

        if ($currentLevel >= $maxLevel) {
            $currentLevel = $maxLevel;
            $currentXp = 0;
        }

        $progression->update(['level' => $currentLevel, 'xp' => $currentXp]);

        return new GemProgressionResult(
            $oldLevel,
            $currentLevel,
            $oldXp,
            $currentXp,
            $xpToApply,
            $currentLevel !== $oldLevel,
            $currentLevel >= $maxLevel,
        );
    }
}

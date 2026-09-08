<?php

namespace App\Admin\Services;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\User;
use App\Game\Core\Chance\RandomNumberGenerator;
use InvalidArgumentException;

class AdminGemRollService
{
    public function __construct(private readonly RandomNumberGenerator $randomNumberGenerator) {}

    /**
     * Roll a new Gem for the Map Gem profile.
     */
    public function rollMapGem(GameMapGemParamter $gameMapGemParamter, User $admin): Gem
    {
        return $this->roll(
            $gameMapGemParamter,
            $admin,
            Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id',
        );
    }

    /**
     * Roll a new Gem for the Location Gem profile.
     */
    public function rollLocationGem(GameLocationGemParamter $gameLocationGemParamter, User $admin): Gem
    {
        return $this->roll(
            $gameLocationGemParamter,
            $admin,
            Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id',
        );
    }

    /**
     * Roll and persist a Gem for the supplied Gem profile.
     */
    private function roll(
        GameMapGemParamter|GameLocationGemParamter $profile,
        User $admin,
        string $domain,
        string $sourceForeignKey,
    ): Gem {
        $currentProfile = $profile->newQuery()->findOrFail($profile->getKey());
        $rollNumber = $currentProfile->roll_count + 1;
        $gemData = [
            'name' => $currentProfile->name,
            'domain' => $domain,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => $rollNumber,
            $sourceForeignKey => $currentProfile->id,
            'crafting_skill_ids' => $currentProfile->crafting_skill_ids,
            'monster_atonement' => $currentProfile->monster_atonement,
            'monster_atonement_amount' => $this->rollRange($currentProfile->monster_atonement_range),
        ];

        foreach ($currentProfile->rollableRangeFields() as $rangeField) {
            $gemData[str($rangeField)->beforeLast('_range')->toString()] = $this->rollRange(
                $currentProfile->{$rangeField},
            );
        }

        $gem = Gem::create($gemData);

        $currentProfile->update([
            'rolled_gem_id' => $gem->id,
            'roll_count' => $rollNumber,
        ]);

        return $gem;
    }

    /**
     * Resolve an optional stored Gem roll range to a rolled value.
     */
    private function rollRange(?string $range): ?float
    {
        if (is_null($range) || trim($range) === '') {
            return null;
        }

        $range = trim($range);

        if ($this->isZeroOnlyScalar($range)) {
            return null;
        }

        $rangeValues = explode('-', $range, 2);

        if (count($rangeValues) !== 2) {
            throw new InvalidArgumentException('Invalid gem roll range: '.$range);
        }

        $firstValue = trim($rangeValues[0]);
        $secondValue = trim($rangeValues[1]);

        if (! is_numeric($firstValue) || ! is_numeric($secondValue)) {
            throw new InvalidArgumentException('Invalid gem roll range: '.$range);
        }

        if ($this->isZeroOnlyScalar($firstValue) && $this->isZeroOnlyScalar($secondValue)) {
            return null;
        }

        $lower = min($firstValue, $secondValue);
        $upper = max($firstValue, $secondValue);
        $percentage = $this->randomNumberGenerator->numberBetween(0, 1_000_000) / 1_000_000;

        return round($lower + (($upper - $lower) * $percentage), 8);
    }

    /**
     * Determine whether a trimmed scalar string represents only zero, such as "0", "0.0", or "0.00", with no other digits.
     */
    private function isZeroOnlyScalar(string $value): bool
    {
        return preg_match('/^0+(?:\.0+)?$/', $value) === 1;
    }
}
